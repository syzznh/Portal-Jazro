
<?php
session_start();

header("Content-Type: application/json");

require_once "config/db.php";

// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
    exit;
}

// Get login details
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($email === "" || $password === "") {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Please enter your email and password."
    ]);
    exit;
}

// Find parent account
$sql = "
    SELECT
        u.user_id,
        u.email,
        u.password,
        u.role,
        p.parent_id,
        p.parent_name
    FROM `user` u
    INNER JOIN `parent` p
        ON p.user_id = u.user_id
    WHERE TRIM(u.email) = ?
      AND u.role = 'PARENT'
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Unable to process login."
    ]);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();
$parent = $result->fetch_assoc();

$stmt->close();

// Check account and password
if (!$parent) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Incorrect email or password. Please try again."
    ]);
    exit;
}

$storedPassword = $parent["password"];

// Support existing password hashes and legacy
// plain-text passwords in the imported development database.
$isHashed = password_get_info($storedPassword)["algo"] !== null;

$passwordValid = $isHashed
    ? password_verify($password, $storedPassword)
    : hash_equals($storedPassword, $password);

if (!$passwordValid) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Incorrect email or password. Please try again."
    ]);
    exit;
}

// Upgrade legacy plain-text password to a secure hash
if (!$isHashed || password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {

    $newHash = password_hash($password, PASSWORD_DEFAULT);

    $update = $conn->prepare(
        "UPDATE `user` SET `password` = ? WHERE `user_id` = ?"
    );

    if ($update) {
        $update->bind_param("si", $newHash, $parent["user_id"]);
        $update->execute();
        $update->close();
    }
}

// Create authenticated session
session_regenerate_id(true);

$_SESSION["logged_in"] = true;
$_SESSION["user_id"] = (int) $parent["user_id"];
$_SESSION["parent_id"] = (int) $parent["parent_id"];
$_SESSION["parent_name"] = trim($parent["parent_name"]);
$_SESSION["parent_email"] = trim($parent["email"]);
$_SESSION["role"] = "PARENT";

// Successful login
echo json_encode([
    "success" => true,
    "message" => "Login successful.",
    "parentName" => trim($parent["parent_name"])
]);

$stmt = null;
$conn->close();
?>
