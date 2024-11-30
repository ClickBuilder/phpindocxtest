<?php
require_once 'vendor/autoload.php'; // Подключаем автозагрузчик Composer

use PhpOffice\PhpWord\IOFactory;

// Указываем директорию для сохранения сгенерированных тестов
$generatedDir = __DIR__ . '/generated_tests';

// Проверяем, существует ли директория, если нет — создаем
if (!is_dir($generatedDir)) {
    mkdir($generatedDir, 0777, true);
}

// Функция для генерации файла теста
function generateTestFile($testName, $questions) {
    global $generatedDir;
    // Путь для сохранения теста
    $filePath = $generatedDir . '/' . $testName . '.php';

    // Сохраняем массив данных в PHP-файл
    file_put_contents($filePath, '<?php return ' . var_export($questions, true) . ';');
}

// Функция для парсинга .docx файла
function parseDocxFile($filePath) {
    $phpWord = IOFactory::load($filePath, 'Word2007');
    $questions = [];
    $currentQuestion = null;

    // Извлекаем текстовые элементы
    foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {
            $text = '';
            if (method_exists($element, 'getText')) {
                $text = $element->getText();
                // Преобразуем текст в UTF-8, если это необходимо
                $text = mb_convert_encoding($text, 'UTF-8', 'auto');
            }

            // Пропускаем пустые строки (считаем их разделителями между вопросами)
            if (trim($text) === "") {
                if ($currentQuestion) {
                    // Если текущий вопрос не пустой, добавляем его в массив
                    $questions[] = $currentQuestion;
                    $currentQuestion = null; // Сброс текущего вопроса
                }
                continue;
            }

            // Если текст начинается с "+" это правильный ответ
            if (strpos($text, '+') === 0 && $currentQuestion) {
                $currentQuestion['answers'][] = ['text' => substr($text, 1), 'correct' => true];
            } elseif ($currentQuestion) {
                // Остальные строки считаем неправильными ответами
                $currentQuestion['answers'][] = ['text' => $text, 'correct' => false];
            }

            // Если текст не начинается с "+" и текущий вопрос еще не определен, то это новый вопрос
            if (!$currentQuestion && !empty($text)) {
                $currentQuestion = ['question' => $text, 'answers' => []];
            }
        }
    }

    // Добавляем последний вопрос, если он есть
    if ($currentQuestion) {
        $questions[] = $currentQuestion;
    }

    return $questions;
}

// Обработка загрузки файла
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    $uploadedFile = $_FILES['test_file'];

    // Проверка на ошибки загрузки
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        die('Ошибка загрузки файла!');
    }

    // Получаем имя файла без расширения
    $testName = pathinfo($uploadedFile['name'], PATHINFO_FILENAME);

    // Временный путь к загруженному файлу
    $tempFilePath = $uploadedFile['tmp_name'];

    // Проверка существования файла
    if (!file_exists($tempFilePath)) {
        die("Файл не найден: $tempFilePath");
    }

    // Парсим содержимое файла
    $questions = parseDocxFile($tempFilePath);

    // Проверка, что вопросы были извлечены
    if (empty($questions)) {
        die("Не удалось извлечь вопросы из файла!");
    }

    // Генерируем PHP-файл с тестами
    generateTestFile($testName, $questions);

    echo "Тест '$testName' успешно импортирован!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Импорт теста</title>
</head>
<body>
    <h1>Импорт теста</h1>
    <form action="import.php" method="post" enctype="multipart/form-data">
        <label for="test_file">Выберите .docx файл с тестом:</label><br>
        <input type="file" name="test_file" id="test_file" required><br><br>
        <button type="submit">Импортировать</button>
    </form>
</body>
</html>
