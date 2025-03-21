<?php
session_start();

// Конфигурация базы данных
$db_config = [
    'host' => 'localhost',
    'dbname' => 'bookstore',
    'username' => 'root',
    'password' => ''
];

try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset=utf8mb4",
        $db_config['username'],
        $db_config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    die('Ошибка подключения к базе данных: ' . $e->getMessage());
}

// Функции для работы с пользователями
function isLoggedIn() {
    return isset($_SESSION['user']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user']['is_admin'];
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /');
        exit;
    }
}

// Вспомогательные функции
function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' ₽';
}

// Функция для безопасного получения значения из массива
function getValue($array, $key, $default = null) {
    return isset($array[$key]) ? $array[$key] : $default;
}

// Функция для генерации случайной строки
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 
        ceil($length/strlen($x)))), 1, $length);
}

// Функция для очистки строки
function sanitizeString($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

// Функция для проверки, является ли запрос AJAX
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// Функция для отправки JSON ответа
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Функция для логирования ошибок
function logError($message, $context = []) {
    $logFile = __DIR__ . '/logs/error.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $contextString = !empty($context) ? json_encode($context) : '';
    $logMessage = "[$timestamp] $message $contextString\n";
    
    error_log($logMessage, 3, $logFile);
}
