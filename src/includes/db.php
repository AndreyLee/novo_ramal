<?php
// src/includes/db.php

// Definir charset padrão para a aplicação PHP
// Usar @ para suprimir erros se ini_set estiver desabilitado em algum ambiente (raro).
if (function_exists('ini_set')) {
    @ini_set('default_charset', 'UTF-8');
}
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

$host = getenv('DB_HOST');
$db   = getenv('DB_DATABASE');
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');
$port = getenv('DB_PORT') ?: '3306'; // Default to 3306 if DB_PORT is not set or empty

// Data Source Name (DSN)
$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

// PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch results as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Disable emulated prepared statements
];

try {
    // Create a new PDO instance
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET NAMES 'utf8mb4'"); // Ensure the connection is utf8mb4
} catch (PDOException $e) {
    // Log the error (in a real application, use a proper logging mechanism)
    error_log("Database Connection Error: " . $e->getMessage());

    // Display a generic error message to the user or handle appropriately
    // For development, displaying the error can be helpful.
    // For production, a more user-friendly message and robust error handling are recommended.
    // Consider throwing the exception to be caught by a global error handler.
    die("Database connection failed. Please check server configuration and logs. Specific error: " . $e->getMessage());
}

// If the script reaches here, the $pdo object is successfully created and configured.
// It can now be used by any script that includes this file.
// e.g., require_once __DIR__ . '/db.php';
// $stmt = $pdo->query("SELECT * FROM some_table");
?>
