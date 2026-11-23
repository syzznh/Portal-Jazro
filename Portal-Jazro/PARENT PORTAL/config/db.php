
<?php
/*
|--------------------------------------------------------------------------
| JAZRO PARENT PORTAL
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

$host = "127.0.0.1";
$port = 3306;
$username = "root";
$password = "";
$database = "jazro_portal_v2latest"; 

// Connect to MySQL
$conn = new mysqli(
    $host,
    $username,
    $password,
    $database,
    $port
);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    exit("Database connection failed. Please check your XAMPP settings.");
}

// Set character encoding
$conn->set_charset("utf8mb4");

?>
