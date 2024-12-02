<?php
session_start();

// Проверяем, если нет данных о пользователе в сессии, редиректим на страницу входа
if (!isset($_SESSION['id_teacher'])) {
    header("Location: php/login.php");
    exit;
}

// Если пользователь авторизован, можно выводить контент
echo "Добро пожаловать, " . $_SESSION['full_name'];

// Укажите папку для сгенерированных тестов
$generatedDir = __DIR__ . '/generated_tests';

// Получаем список сгенерированных PHP-файлов
$testFiles = is_dir($generatedDir) ? array_diff(scandir($generatedDir), ['.', '..']) : [];

// Проверка на выборку количества вопросов для теста
if (isset($_POST['num_questions'])) {
    $numQuestions = (int)$_POST['num_questions'];
    $testName = $_POST['test_name'];
    $testFilePath = $generatedDir . '/' . $testName . '.php';

    // Проверяем, существует ли файл с тестом
    if (!file_exists($testFilePath)) {
        die('Тест не найден.');
    }

    // Загружаем тест
    $questions = include $testFilePath;

    // Перемешиваем вопросы и ограничиваем их количеством
    shuffle($questions); // Рандомизируем вопросы
    $selectedQuestions = array_slice($questions, 0, $numQuestions); // Выбираем случайные вопросы

    // Сохраняем вопросы для сессии, ключ для каждого теста уникален
    $_SESSION['selected_questions'][$testName] = $selectedQuestions;
    $_SESSION['test_name'] = $testName; // Сохраняем имя теста
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тесты</title>
    <script>
        // AJAX обработчик для генерации теста
        function generateTest(event, testName) {
            event.preventDefault(); // Отменяем обычное поведение формы

            var numQuestions = document.getElementById('num_questions_' + testName).value;

            // Формируем данные для отправки
            var formData = new FormData();
            formData.append('test_name', testName);
            formData.append('num_questions', numQuestions);

            // Отправляем AJAX запрос
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'index.php', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    // Обновляем ссылку на пройти тест
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        document.getElementById('test_link_' + testName).innerHTML = '<a href="take_test.php?test=' + encodeURIComponent(testName) + '">Пройти тест</a>';
                    }
                }
            };
            xhr.send(formData);
        }
    </script>
</head>
<body>
    <h1>Список тестов</h1>
    <a href="import.php">Импортировать тест</a> <!-- Ссылка на импорт -->
    <table border="1" cellpadding="10" style="margin-top: 10px;">
        <thead>
            <tr>
                <th>Название теста</th>
                <th>Выбрать количество вопросов</th>
                <th>Пройти тест</th>
                <th>Редактировать</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($testFiles)): ?>
                <tr>
                    <td colspan="4">Нет доступных тестов. Импортируйте их через <a href="import.php">Импорт</a>.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($testFiles as $testFile): ?>
                    <?php $testName = pathinfo($testFile, PATHINFO_FILENAME); ?>
                    <tr>
                        <td><?= htmlspecialchars($testName) ?></td>
                        <td>
                            <form onsubmit="generateTest(event, '<?= htmlspecialchars($testName) ?>')">
                                <input type="hidden" name="test_name" value="<?= htmlspecialchars($testName) ?>">
                                <label for="num_questions_<?= $testName ?>">Выберите количество вопросов:</label>
                                <input type="number" name="num_questions" id="num_questions_<?= $testName ?>" min="1" max="100" required>
                                <button type="submit">Сгенерировать тест</button>
                            </form>
                        </td>
                        <td id="test_link_<?= $testName ?>">
                            <?php if (isset($_SESSION['selected_questions'][$testName])): ?>
                                <a href="take_test.php?test=<?= urlencode($testName) ?>">Пройти тест</a>
                            <?php else: ?>
                                <span>Сгенерируйте тест</span>
                            <?php endif; ?>
                        </td>
                        <td><a href="edit_test.php?test=<?= urlencode($testName) ?>">Редактировать</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
