
<?php
session_start();

header("Content-Type: application/json");

require_once "config/db.php";

// Check parent session
if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "PARENT" ||
    !isset($_SESSION["parent_id"])
) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Please log in to view your children."
    ]);

    exit;
}

$parentId = (int) $_SESSION["parent_id"];

// Get children and their latest active class
$sql = "
    SELECT
        s.student_id,
        TRIM(s.student_name) AS student_name,
        s.age,
        TRIM(s.gender) AS gender,

        TRIM(c.class_name) AS class_name,
        c.class_type,

        CASE
            WHEN c.class_type = 'Online'
                THEN 'Online/Virtual'

            ELSE COALESCE(
                NULLIF(TRIM(b.branch_name), ''),
                NULLIF(TRIM(b.branch_location), ''),
                NULLIF(TRIM(o.organization_name), ''),
                NULLIF(TRIM(o.location), ''),
                'Location not available'
            )
        END AS location

    FROM student s

    LEFT JOIN (
        SELECT e1.student_id, e1.class_id
        FROM enrollment e1

        INNER JOIN (
            SELECT
                student_id,
                MAX(enrollment_id) AS latest_enrollment
            FROM enrollment
            WHERE status = 'Active'
            GROUP BY student_id
        ) latest
        ON e1.enrollment_id = latest.latest_enrollment
    ) e ON e.student_id = s.student_id

    LEFT JOIN classes c
        ON c.class_id = e.class_id

    LEFT JOIN branch b
        ON b.branch_id = c.branch_id

    LEFT JOIN organization o
        ON o.organization_id = c.organization_id

    WHERE s.parent_id = ?

    ORDER BY s.student_name ASC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve student information."
    ]);

    exit;
}

$stmt->bind_param("i", $parentId);
$stmt->execute();

$result = $stmt->get_result();

$children = [];

while ($row = $result->fetch_assoc()) {
    $children[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "children" => $children
]);
?>
