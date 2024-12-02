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
    $stmt->execute([$fullName, $email, $hashedPassword]);

    // Уведомление о том, что регистрация прошла успешно
    echo 'Регистрация прошла успешно. Вы можете войти в систему.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizMaster</title>
</head>
<body Class="Body">
    <div class="Block">
        <div class="Sections">
        <div Class = "Section">
            <a href="index.php">Главная</a>
        </div>
        <div Class = "Section">
            <a href="php/Profile.php">Профиль</a>
        </div>
        <div Class = "Section">
            <a href="#Instruction">Инструкция</a>
        </div>
        <?php
            if (isset($_SESSION['user'])) {
            
            echo '<div Class="Section"><a href="php/Unset.php">Выход</a></div>';
            } else {
         
            echo '<div Class="Section"><a href="php/register.php">Регистрация</a></div>';
            }
        ?>
        </div>
        <div class="icons">
        <div Class = "Home"></div>
        <div Class = "ProfileIcon" href="php/Profile.php"></div>
        <div Class = "Instructions" href="#Instruction"></div>
        <?php
            if (isset($_SESSION['user'])) {
            echo '<div Class="LogOut" href="php/Unset.php"></div>';
            } else {
            echo '<div Class="LogIn"href="php/register.php"></div>';
            }
        ?>
        </div>
    </div>
    <div class="Navigation">
        <div class="blockfortext">
            <p class="title">QUIZ MASTER</p>
            <p class="Info">Проведение онлайн тестирования</p>            
        </div>
        <div class="PictureForNavigation"></div>
    </div>
    <div class ="AboutUs">
        <div class="PictureForAboutUs">
        </div>
        <div class = "information">
            <p class="t">QuizMaster</p>
            <p class = "t2">- платформа, предоставляющая возможность проведения быстрого и эффективного тестирования студентов. С ее помощью вы легко создадите тесты и моментально загрузите их для использования.</p>
            <p class ="t2"> Электронные тесты позволяют студентам моментально получить результаты и обратную связь</p>
            <p class ="t2"> Экономия времени и улучшение учебного процесса</p>
            <p class ="t2"> Никаких бумажных проблем или сложных процессов - все под контролем в виртуальном пространстве</p>
            <p class = "t2">QuizMaster автоматически преобразует информацию из текстового файла в готовые тесты, обеспечивая быструю и удобную подготовку материалов для обучения.</p>
        </div>
    </div>
  <div class="Instruction" id="Instruction">
    </div>
</body>
</html>

