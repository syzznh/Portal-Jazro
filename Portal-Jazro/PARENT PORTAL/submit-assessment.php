<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/db.php';

function respond($code, $data) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['success' => false, 'message' => 'Invalid request method.']);
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true ||
    ($_SESSION['role'] ?? '') !== 'PARENT' || empty($_SESSION['parent_id'])) {
    respond(401, ['success' => false, 'message' => 'Your parent session has expired. Please log in again.']);
}

$payload = json_decode(file_get_contents('php://input'), true);
$studentId = (int)($payload['student_id'] ?? 0);
$assessmentId = (int)($payload['assessment_id'] ?? 12); // TEMPORARY test assessment.
$answers = $payload['answers'] ?? null;
if ($studentId < 1 || !is_array($answers) || !$answers) respond(400, ['success' => false, 'message' => 'Assessment answers are missing.']);

$studentCheck = $conn->prepare('SELECT student_id FROM student WHERE student_id = ? AND parent_id = ? LIMIT 1');
$studentCheck->bind_param('ii', $studentId, $_SESSION['parent_id']);
$studentCheck->execute();
$studentExists = $studentCheck->get_result()->fetch_assoc();
$studentCheck->close();
if (!$studentExists) respond(403, ['success' => false, 'message' => 'This child is not linked to your parent account.']);

try {
    $conn->begin_transaction();

    $assessmentQuery = $conn->prepare('SELECT assessment_id, total_marks FROM assessment WHERE assessment_id = ? LIMIT 1 FOR UPDATE');
    $assessmentQuery->bind_param('i', $assessmentId);
    $assessmentQuery->execute();
    $assessment = $assessmentQuery->get_result()->fetch_assoc();
    $assessmentQuery->close();
    if (!$assessment) throw new Exception('Assessment not found.');

    $latestQuery = $conn->prepare('SELECT attempt_number, score FROM result_assessment WHERE assessment_id = ? AND student_id = ? ORDER BY attempt_number DESC LIMIT 1');
    $latestQuery->bind_param('ii', $assessmentId, $studentId);
    $latestQuery->execute();
    $latest = $latestQuery->get_result()->fetch_assoc();
    $latestQuery->close();
    if ($latest && (float)$latest['score'] >= 50) throw new Exception('This student has already passed this assessment.');

    $questionQuery = $conn->prepare('SELECT question_id, marks FROM assessment_question WHERE assessment_id = ? ORDER BY question_order ASC');
    $questionQuery->bind_param('i', $assessmentId);
    $questionQuery->execute();
    $questionResult = $questionQuery->get_result();
    $questionRows = [];
    while ($row = $questionResult->fetch_assoc()) $questionRows[(int)$row['question_id']] = (float)$row['marks'];
    $questionQuery->close();
    if (!$questionRows) throw new Exception('This assessment has no questions.');

    // Require one valid answer for every question. Correct answers are read only on the server.
    if (count($answers) !== count($questionRows)) throw new Exception('Please answer every question before submitting.');
    $score = 0.0;
    $answerRows = [];
    foreach ($questionRows as $questionId => $marks) {
        $selectedOptionId = (int)($answers[(string)$questionId] ?? $answers[$questionId] ?? 0);
        if ($selectedOptionId < 1) throw new Exception('Please answer every question before submitting.');
        $correctQuery = $conn->prepare('SELECT option_id FROM assessment_option WHERE question_id = ? AND is_correct = 1 LIMIT 1');
        $correctQuery->bind_param('i', $questionId);
        $correctQuery->execute();
        $correctRow = $correctQuery->get_result()->fetch_assoc();
        $correctQuery->close();
        if (!$correctRow) throw new Exception('A correct answer is missing for one of the questions. Please contact JAZRO.');

        $validOptionQuery = $conn->prepare('SELECT option_id FROM assessment_option WHERE option_id = ? AND question_id = ? LIMIT 1');
        $validOptionQuery->bind_param('ii', $selectedOptionId, $questionId);
        $validOptionQuery->execute();
        $validOption = $validOptionQuery->get_result()->fetch_assoc();
        $validOptionQuery->close();
        if (!$validOption) throw new Exception('An invalid answer was submitted. Please refresh and try again.');

        $awarded = ($selectedOptionId === (int)$correctRow['option_id']) ? $marks : 0.0;
        $score += $awarded;
        $answerRows[] = [$questionId, $selectedOptionId, $awarded];
    }

    $totalMarks = 0.0;
    foreach ($questionRows as $marks) $totalMarks += $marks;
    if ($totalMarks <= 0) throw new Exception('Assessment total marks are invalid.');
    $percentage = ($score / $totalMarks) * 100;
    $attemptNumber = $latest ? ((int)$latest['attempt_number'] + 1) : 1;

    // The table's status enum is Not Started / In Progress / Completed.
    $resultStatus = 'Completed';
    $insertResult = $conn->prepare('INSERT INTO result_assessment (assessment_id, student_id, attempt_number, score, percentage, status, completed_date) VALUES (?, ?, ?, ?, ?, ?, CURDATE())');
    $insertResult->bind_param('iiidds', $assessmentId, $studentId, $attemptNumber, $score, $percentage, $resultStatus);
    $insertResult->execute();
    $resultId = $conn->insert_id;
    $insertResult->close();

    $insertAnswer = $conn->prepare('INSERT INTO student_answer (result_id, question_id, selected_option_id, marks_awarded) VALUES (?, ?, ?, ?)');
    foreach ($answerRows as [$questionId, $optionId, $awarded]) {
        $insertAnswer->bind_param('iiid', $resultId, $questionId, $optionId, $awarded);
        $insertAnswer->execute();
    }
    $insertAnswer->close();

    $conn->commit();
    respond(200, [
        'success' => true,
        'message' => 'Assessment submitted successfully.',
        'result' => [
            'result_id' => $resultId,
            'attempt_number' => $attemptNumber,
            'score' => round($score, 2),
            'total_marks' => round($totalMarks, 2),
            'percentage' => round($percentage, 2),
            'passed' => $score >= 50,
            'passing_marks' => 50
        ]
    ]);
} catch (Throwable $e) {
    if ($conn->errno === 0 || $conn->connect_errno === 0) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
    }
    respond(400, ['success' => false, 'message' => $e->getMessage()]);
}
?>
