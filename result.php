<?php
session_start();

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Проверяем, был ли передан параметр 'test'
if (!isset($_GET['test'])) {
    die('Не указан тест.');
}

$testName = $_GET['test'];
$generatedDir = __DIR__ . '/generated_tests';

// Путь к файлу теста
$testFile = $generatedDir . '/' . $testName . '.php';

// Проверяем, существует ли файл с тестом
if (!file_exists($testFile)) {
    die('Тест не найден.');
}

// Загружаем тест
$questions = include $testFile;

// Проверка наличия результатов
if (!isset($_SESSION['test_results'])) {
    die('Результаты теста не найдены.');
}

$results = $_SESSION['test_results'];

// Генерация Excel файла
if (isset($_GET['download']) && $_GET['download'] == 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Заголовки таблицы
    $sheet->setCellValue('A1', 'Вопрос');
    $sheet->setCellValue('B1', 'Ответ пользователя');
    $sheet->setCellValue('C1', 'Правильный ответ');
    $sheet->setCellValue('D1', 'Правильно/Неверно');
    $sheet->setCellValue('E1', 'Общий балл');
    $sheet->setCellValue('F1', 'Буквенная оценка');
    $sheet->setCellValue('G1', 'Процент');

    // Записываем данные по вопросам
    $row = 2; // начинаем с 2-й строки
    foreach ($questions as $index => $question) {
        // Получаем ответ пользователя (текст ответа)
        $userAnswerIndexes = $_SESSION['user_answers'][$index] ?? [];
        $userAnswerText = [];
        foreach ($userAnswerIndexes as $answerIndex) {
            $userAnswerText[] = $question['answers'][$answerIndex]['text']; // Текст выбранного ответа
        }

        // Получаем правильный ответ (текст)
        $correctAnswer = array_filter($question['answers'], fn($answer) => $answer['correct']);
        $correctAnswerText = array_map(fn($answer) => $answer['text'], $correctAnswer);

        // Проверяем правильность ответа
        $isCorrect = false;
        foreach ($userAnswerIndexes as $answerIndex) {
            if (isset($question['answers'][$answerIndex]) && $question['answers'][$answerIndex]['correct']) {
                $isCorrect = true;
                break;
            }
        }

        // Заполняем таблицу для каждого вопроса
        $sheet->setCellValue("A{$row}", $question['question']);
        $sheet->setCellValue("B{$row}", implode(", ", $userAnswerText)); // Текст выбранных ответов
        $sheet->setCellValue("C{$row}", implode(", ", $correctAnswerText)); // Текст правильных ответов
        $sheet->setCellValue("D{$row}", $isCorrect ? 'Правильно' : 'Неверно');
        $row++;
    }

    // Добавляем общую оценку в последнюю строку
    $sheet->setCellValue('E' . $row, "{$results['score']} из {$results['total']}");
    $sheet->setCellValue('F' . $row, $results['grade']);
    $sheet->setCellValue('G' . $row, round($results['percent'], 2) . '%');

    // Создаем и отправляем файл Excel
    $writer = new Xlsx($spreadsheet);
    $fileName = "test_results.xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"$fileName\"");
    $writer->save('php://output');
    exit;
}

// Если не нужно скачивать Excel, просто показываем результат
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Результаты теста</title>
</head>
<body>
    <h1>Результаты теста</h1>
    <p>Вы набрали <?= $results['score'] ?> из <?= $results['total'] ?> баллов.</p>
    <p>Ваша оценка: <?= $results['grade'] ?></p>
    <p>Процент правильных ответов: <?= round($results['percent'], 2) ?>%</p>

    <p><a href="result.php?test=<?= htmlspecialchars($testName) ?>&download=excel">Скачать результаты в Excel</a></p>

    <a href="index.php">Вернуться к списку тестов</a>
</body>
</html>
