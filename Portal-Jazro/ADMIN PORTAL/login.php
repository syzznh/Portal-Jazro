<?php
session_start();

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header('Location: index.php?error=empty');
    exit;
}

try {
   $stmt = $pdo->prepare(
    "SELECT user_id, username, email, password, role
     FROM `user`
     WHERE email = :email OR username = :username
     LIMIT 1"
);

$stmt->execute([
    'email' => $email,
    'username' => $email
]);
    $user = $stmt->fetch();

    if (
        $user &&
        in_array(strtolower($user['role']), ['admin', 'administrator'], true) &&
        (
            password_verify($password, $user['password']) ||
            hash_equals((string)$user['password'], $password)
        )
    ) {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        header('Location: dashboard.php');
        exit;
    }

    header('Location: index.php?error=invalid');
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Login could not be completed. Please check the database configuration.');
}