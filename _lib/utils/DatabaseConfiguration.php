<?php

//require_once  '../librerias/phpdotenv/vendor/autoload.php';
require_once __DIR__ . '/../librerias/phpdotenv/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Acceder a las variables
$dbHost = $_ENV['DB_HOST'];
$dbName = $_ENV['DB_NAME'];
$dbUser = $_ENV['DB_USER'];
$dbPass = $_ENV['DB_PASS'];
$dbPort = $_ENV['DB_PORT'];

?>