<?php
$host = '192.168.0.102'; // Сервер базы данных
$dbname = 'quizmaster'; // Имя базы данных
$user = 'u0_a1287'; // Имя пользователя базы данных
$pass = 'root'; // Пароль пользователя базы данных

try {
    // Создание подключения с использованием PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    // Устанавливаем атрибуты для обработки ошибок
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Ошибка подключения к базе данных: ' . $e->getMessage());
}
?>
