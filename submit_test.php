<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_name'], $_POST['answers'])) {
    $testName = $_POST['test_name'];
    $answers = $_POST['answers'];
    $testPath = __DIR__ . '/tests/' . $testName;

    if (!file_exists($testPath)) {
        die("Тест не найден.");
    }

    // Парсинг теста
    $questions = parseTest($testPath);
    $correctCount = 0;
    $totalQuestions = count($questions);

    echo "<h1>Результаты теста</h1>";

    foreach ($questions as $index => $q) {
        echo "<p><strong>{$q['question']}</strong></p>";

        $correctAnswers = array_filter($q['answers'], fn($a) => $a['correct']);
        $correctTexts = array_map(fn($a) => $a['text'], $correctAnswers);
        $userAnswers = $answers[$index] ?? [];

        // Проверяем, совпадают ли ответы
        $isCorrect = empty(array_diff($correctTexts, $userAnswers)) && empty(array_diff($userAnswers, $correctTexts));

        if ($isCorrect) {
            echo "<p style='color: green;'>Ваши ответы: " . implode(', ', $userAnswers) . " (Верно)</p>";
            $correctCount++;
        } else {
            echo "<p style='color: red;'>Ваши ответы: " . implode(', ', $userAnswers) . " (Неверно)</p>";
            echo "<p>Правильные ответы: " . implode(', ', $correctTexts) . "</p>";
        }
        echo "<hr>";
    }

    echo "<h2>Итог: {$correctCount} из {$totalQuestions} правильных ответов.</h2>";
} else {
    die("Некорректные данные.");
}

// Функция для парсинга тестов
function parseTest($filePath) {
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $questions = [];
    $currentQuestion = null;

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            if ($currentQuestion) {
                $questions[] = $currentQuestion;
                $currentQuestion = null;
            }
        } elseif ($line[0] === '+') {
            $currentQuestion['answers'][] = ['text' => substr($line, 1), 'correct' => true];
        } else {
            if (!$currentQuestion) {
                $currentQuestion = ['question' => $line, 'answers' => []];
            } else {
                $currentQuestion['answers'][] = ['text' => $line, 'correct' => false];
            }
        }
    }

    if ($currentQuestion) {
        $questions[] = $currentQuestion;
    }

    return $questions;
}
?>
