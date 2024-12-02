<?php
session_start();
require_once 'db.php'; // Подключаем файл с настройками подключения к базе данных

// Проверяем, была ли отправлена форма
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $fullName = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Проверяем, что email уникален
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        die('Пользователь с таким email уже существует.');
    }

    // Хешируем пароль
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Подготавливаем SQL-запрос на добавление пользователя
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, pass, role) VALUES (?, ?, ?, 'teacher')");
    if ($stmt->execute([$fullName, $email, $hashedPassword])) {
        echo 'Регистрация прошла успешно. Вы можете войти в систему.';
    } else {
        echo 'Произошла ошибка при регистрации. Попробуйте снова.';
    }
}
?>

