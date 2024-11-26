<?php
if (!isset($_GET['test'])) {
    die("Тест не указан.");
}

$testName = $_GET['test'];
$testPath = __DIR__ . '/tests/' . $testName;

if (!file_exists($testPath)) {
    die("Тест не найден.");
}

$questions = file($testPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$totalQuestions = count($questions);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numQuestions = (int) $_POST['num_questions'];
    if ($numQuestions > $totalQuestions) {
        die("Нельзя выбрать больше вопросов, чем существует.");
    }

    shuffle($questions);
    $randomizedQuestions = array_slice($questions, 0, $numQuestions);

    echo "<h1>Рандомизированные вопросы</h1><pre>" . htmlspecialchars(implode("\n", $randomizedQuestions)) . "</pre>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Рандомизация теста</title>
</head>
<body>
    <h1>Рандомизация теста: <?= htmlspecialchars($testName) ?></h1>
    <form method="post">
        <label>Количество вопросов (максимум <?= $totalQuestions ?>):</label>
        <input type="number" name="num_questions" min="1" max="<?= $totalQuestions ?>" required><br>
        <button type="submit">Рандомизировать</button>
    </form>
</body>
</html>

