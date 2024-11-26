<?php
session_start();

// Проверка на наличие выбранных вопросов
if (!isset($_SESSION['selected_questions']) || !isset($_SESSION['selected_questions'][$_GET['test']])) {
    die('Не выбран набор вопросов для теста.');
}

$testName = $_GET['test']; // Получаем имя теста
$questions = $_SESSION['selected_questions'][$testName]; // Получаем вопросы из сессии

// Обрабатываем отправку формы с ответами
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userAnswers = $_POST['answers'] ?? [];
    $score = 0;
    $total = count($questions);

    // Подсчет баллов
    foreach ($questions as $index => $question) {
        foreach ($question['answers'] as $answerIndex => $answer) {
            if (isset($userAnswers[$index]) && in_array($answerIndex, $userAnswers[$index]) && $answer['correct']) {
                $score++;
            }
        }
    }

    // Оценка
    $percent = ($score / $total) * 100;
    if ($percent >= 90) {
        $grade = 'A';
    } elseif ($percent >= 80) {
        $grade = 'B';
    } elseif ($percent >= 70) {
        $grade = 'C';
    } elseif ($percent >= 60) {
        $grade = 'D';
    } else {
        $grade = 'F';
    }

    // Сохраняем результаты в сессии
    $_SESSION['test_results'] = [
        'score' => $score,
        'total' => $total,
        'grade' => $grade,
        'percent' => $percent,
    ];

    $_SESSION['user_answers'] = $userAnswers;

    // Перенаправляем на страницу результатов, добавляя параметр test
    header("Location: result.php?test=" . urlencode($testName));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пройти тест: <?= htmlspecialchars($testName) ?></title>
</head>
<body>
    <h1>Тест: <?= htmlspecialchars($testName) ?></h1>
    <form method="post">
        <?php foreach ($questions as $index => $question): ?>
            <fieldset>
                <legend>Вопрос <?= $index + 1 ?>: <?= htmlspecialchars($question['question']) ?></legend>
                <?php foreach ($question['answers'] as $answerIndex => $answer): ?>
                    <label>
                        <input type="checkbox" name="answers[<?= $index ?>][]" value="<?= $answerIndex ?>">
                        <?= htmlspecialchars($answer['text']) ?>
                    </label><br>
                <?php endforeach; ?>
            </fieldset>
        <?php endforeach; ?>
        <button type="submit">Отправить ответы</button>
    </form>
</body>
</html>
