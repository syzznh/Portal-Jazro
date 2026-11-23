<?php
/*
 * JAZRO PARENT PORTAL
 * Retrieve learning materials for the selected child.
 */
session_start();
header("Content-Type: application/json; charset=utf-8");

function sendJson(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (
    !isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true ||
    !isset($_SESSION["role"]) || $_SESSION["role"] !== "PARENT" ||
    !isset($_SESSION["parent_id"])
) {
    sendJson(401, ["success" => false, "message" => "Please log in to view class notes."]);
}

$studentId = filter_input(INPUT_GET, "student_id", FILTER_VALIDATE_INT);
if (!$studentId || $studentId < 1) {
    sendJson(400, ["success" => false, "message" => "No valid student was selected. Please return to My Children."]);
}

$parentId = (int) $_SESSION["parent_id"];
require_once __DIR__ . "/../config/db.php";

// Find the selected child and their latest active class.
$studentSql = "
    SELECT
        s.student_id,
        TRIM(s.student_name) AS student_name,
        s.age,
        TRIM(s.gender) AS gender,
        c.class_id,
        TRIM(c.class_name) AS class_name,
        TRIM(c.class_type) AS class_type,
        TRIM(c.class_category) AS class_category
    FROM student s
    LEFT JOIN (
        SELECT e1.student_id, e1.class_id
        FROM enrollment e1
        INNER JOIN (
            SELECT student_id, MAX(enrollment_id) AS latest_enrollment
            FROM enrollment
            WHERE status = 'Active'
            GROUP BY student_id
        ) latest ON latest.latest_enrollment = e1.enrollment_id
    ) active_enrollment ON active_enrollment.student_id = s.student_id
    LEFT JOIN classes c ON c.class_id = active_enrollment.class_id
    WHERE s.student_id = ? AND s.parent_id = ?
    LIMIT 1
";

$studentStmt = $conn->prepare($studentSql);
if (!$studentStmt) {
    sendJson(500, ["success" => false, "message" => "Unable to prepare student information query."]);
}
$studentStmt->bind_param("ii", $studentId, $parentId);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$student = $studentResult->fetch_assoc();
$studentStmt->close();

if (!$student) {
    sendJson(404, ["success" => false, "message" => "Student not found under this parent account."]);
}

if (empty($student["class_id"]) || empty($student["class_type"])) {
    sendJson(200, [
        "success" => true,
        "student" => $student,
        "class_type" => null,
        "materials" => [],
        "message" => "This student does not have an active class assigned yet."
    ]);
}

$classType = strtolower(trim($student["class_type"]));
if ($classType !== "online" && $classType !== "physical") {
    sendJson(422, ["success" => false, "message" => "The student's class type is not recognised."]);
}

// Online students receive JTC materials. Physical students receive non-JTC materials.
$categoryOperator = ($classType === "online") ? "=" : "<>";
$materialSql = "
    SELECT
        lm.material_id,
        lm.material_title,
        lm.file_url,
        lm.description,
        lm.upload_date,
        m.module_id,
        m.module_code,
        m.module_name,
        m.module_category,
        m.level
    FROM learning_material lm
    INNER JOIN module m ON m.module_id = lm.module_id
    WHERE UPPER(TRIM(m.module_category)) $categoryOperator 'JTC'
    ORDER BY m.level ASC, m.module_category ASC, m.module_name ASC, lm.material_title ASC
";

$materialStmt = $conn->prepare($materialSql);
if (!$materialStmt) {
    sendJson(500, ["success" => false, "message" => "Unable to prepare learning materials query."]);
}
$materialStmt->execute();
$materialResult = $materialStmt->get_result();
$materials = [];

while ($row = $materialResult->fetch_assoc()) {
    // Only expose files stored in the academy's learning-material upload directory.
    $fileUrl = str_replace("\\", "/", trim((string)($row["file_url"] ?? "")));
    $fileUrl = ltrim($fileUrl, "/");
    if (
        $fileUrl === "" ||
        strpos($fileUrl, "uploads/learning_materials/") !== 0 ||
        strpos($fileUrl, "..") !== false
    ) {
        $row["file_url"] = null;
    } else {
        $row["file_url"] = $fileUrl;
    }
    $materials[] = $row;
}

$materialStmt->close();
$conn->close();

sendJson(200, [
    "success" => true,
    "student" => $student,
    "class_type" => $classType,
    "materials" => $materials,
    "filter" => ($classType === "online") ? "JTC" : "NON-JTC"
]);
?>
