<?php
// Database connection settings
$host = 'localhost:3307';  // ADD PORT TO HOST
$db   = 'movie_watchlist';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Data Source Name
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// PDO options (same as above)
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];


?>