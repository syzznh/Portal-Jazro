<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/db.php';

function respond($code, $data) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true ||
    ($_SESSION['role'] ?? '') !== 'PARENT' || empty($_SESSION['parent_id'])) {
    respond(401, ['success' => false, 'message' => 'Please log in as a parent.']);
}

$studentId = (int)($_GET['student_id'] ?? 0);
$assessmentId = (int)($_GET['assessment_id'] ?? 12); // TEMPORARY: Junior assessment for any child of the logged-in parent.
if ($studentId < 1) respond(400, ['success' => false, 'message' => 'Please select a child first.']);

// Verify the selected child belongs to the currently logged-in parent.
$studentCheck = $conn->prepare('SELECT student_id, student_name FROM student WHERE student_id = ? AND parent_id = ? LIMIT 1');
$studentCheck->bind_param('ii', $studentId, $_SESSION['parent_id']);
$studentCheck->execute();
$student = $studentCheck->get_result()->fetch_assoc();
$studentCheck->close();
if (!$student) respond(403, ['success' => false, 'message' => 'This child is not linked to your parent account.']);

// TEMPORARY TEST MODE: allow the assessment even while its status is DRAFT.
$assessmentQuery = $conn->prepare("SELECT assessment_id, title, description, total_marks, status FROM assessment WHERE assessment_id = ? LIMIT 1");
$assessmentQuery->bind_param('i', $assessmentId);
$assessmentQuery->execute();
$assessment = $assessmentQuery->get_result()->fetch_assoc();
$assessmentQuery->close();
if (!$assessment) respond(404, ['success' => false, 'message' => 'Assessment not found.']);

$questionsQuery = $conn->prepare('SELECT question_id, question_text, marks, question_order FROM assessment_question WHERE assessment_id = ? ORDER BY question_order ASC, question_id ASC');
$questionsQuery->bind_param('i', $assessmentId);
$questionsQuery->execute();
$questionResult = $questionsQuery->get_result();
$questions = [];

while ($q = $questionResult->fetch_assoc()) {
    $questionId = (int)$q['question_id'];
    $optionsQuery = $conn->prepare('SELECT option_id, option_label, option_text FROM assessment_option WHERE question_id = ? ORDER BY option_label ASC');
    $optionsQuery->bind_param('i', $questionId);
    $optionsQuery->execute();
    $optionsResult = $optionsQuery->get_result();
    $options = [];
    while ($o = $optionsResult->fetch_assoc()) {
        $options[] = [
            'option_id' => (int)$o['option_id'],
            'option_label' => $o['option_label'],
            'option_text' => $o['option_text']
        ];
    }
    $optionsQuery->close();

    $imageQuery = $conn->prepare('SELECT image_path, alt_text FROM assessment_question_image WHERE question_id = ? ORDER BY display_order ASC LIMIT 1');
    $imageQuery->bind_param('i', $questionId);
    $imageQuery->execute();
    $image = $imageQuery->get_result()->fetch_assoc();
    $imageQuery->close();

    $questions[] = [
        'question_id' => $questionId,
        'question_text' => $q['question_text'],
        'marks' => (float)$q['marks'],
        'question_order' => (int)$q['question_order'],
        'options' => $options,
        'image_path' => $image['image_path'] ?? '',
        'image_alt' => $image['alt_text'] ?? 'Assessment question image'
    ];
}
$questionsQuery->close();
if (!$questions) respond(404, ['success' => false, 'message' => 'No questions have been added to this assessment yet.']);

$attemptQuery = $conn->prepare('SELECT attempt_number, score, percentage, status, completed_date FROM result_assessment WHERE assessment_id = ? AND student_id = ? ORDER BY attempt_number DESC LIMIT 1');
$attemptQuery->bind_param('ii', $assessmentId, $studentId);
$attemptQuery->execute();
$latestAttempt = $attemptQuery->get_result()->fetch_assoc();
$attemptQuery->close();

$passed = $latestAttempt && (float)$latestAttempt['score'] >= 50;
respond(200, [
    'success' => true,
    'student' => ['student_id' => $studentId, 'student_name' => trim($student['student_name'])],
    'assessment' => [
        'assessment_id' => (int)$assessment['assessment_id'],
        'title' => $assessment['title'],
        'description' => $assessment['description'],
        'total_marks' => (float)$assessment['total_marks'],
        'status' => $assessment['status'],
        'passing_marks' => 50
    ],
    'questions' => $questions,
    'latest_attempt' => $latestAttempt ? [
        'attempt_number' => (int)$latestAttempt['attempt_number'],
        'score' => (float)$latestAttempt['score'],
        'percentage' => (float)$latestAttempt['percentage'],
        'passed' => $passed,
        'completed_date' => $latestAttempt['completed_date']
    ] : null,
    'can_attempt' => !$passed
]);
?>
