<?php
session_start();
require 'vendor/autoload.php'; // Подключаем автозагрузчик Composer для PhpSpreadsheet и PHPWord

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;

// Укажите папку с тестами
$testsDir = __DIR__ . '/tests';

// Функция для парсинга DOCX файлов
function parseTestDocx($filePath) {
    $phpWord = IOFactory::load($filePath); // Загружаем DOCX файл
    $questions = [];
    $currentQuestion = null;

    // Читаем все параграфы из файла DOCX
    foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {
            if (method_exists($element, 'getText')) {
                $line = $element->getText();
                $line = trim($line);

                // Пустая строка означает конец текущего вопроса
                if ($line === '') {
                    if ($currentQuestion) {
                        $questions[] = $currentQuestion;
                        $currentQuestion = null;
                    }
                } elseif ($line[0] === '+') {
                    // Если строка начинается с "+", это правильный вариант ответа
                    $currentQuestion['answers'][] = ['text' => substr($line, 1), 'correct' => true];
                } elseif (isset($currentQuestion)) {
                    // Это вариант ответа, который не начинается с "+"
                    $currentQuestion['answers'][] = ['text' => $line, 'correct' => false];
                } else {
                    // Если текущий вопрос ещё не определён, это новая строка с вопросом
                    $currentQuestion = ['question' => $line, 'answers' => []];
                }
            }
        }
    }

    // Добавить последний вопрос, если он не был добавлен
    if ($currentQuestion) {
        $questions[] = $currentQuestion;
    }

    return $questions;
}

// Функция для сохранения изменений в DOCX файле
function saveDocx($filePath, $questions) {
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $section = $phpWord->addSection();

    // Формируем вопросы и ответы
    foreach ($questions as $question) {
        $section->addText($question['question']);
        foreach ($question['answers'] as $answer) {
            $section->addText(($answer['correct'] ? '+' : '') . $answer['text']);
        }
        $section->addText(''); // Пустая строка после каждого вопроса
    }

    // Сохраняем обновленный .docx
    $phpWord->save($filePath, 'Word2007');
}

// Проверка правильности ответов
function checkAnswers($userAnswers, $correctAnswers) {
    if (empty($correctAnswers)) {
        return false;
    }

    $correctAnswersText = array_map(function($answer) {
        return $answer['text'];
    }, $correctAnswers);

    return empty(array_diff($userAnswers, $correctAnswersText)) && empty(array_diff($correctAnswersText, $userAnswers));
}

// Экспорт результатов в Excel
if (isset($_GET['start_test'])) {
    $testName = $_GET['start_test'];
    $testExtension = pathinfo($testName, PATHINFO_EXTENSION);

    // В зависимости от расширения файла парсим его
    if ($testExtension === 'txt') {
        $questions = parseTestTxt($testsDir . '/' . $testName);
    } elseif ($testExtension === 'docx') {
        $questions = parseTestDocx($testsDir . '/' . $testName);
    }

    // Подсчитаем количество правильных ответов
    $correctAnswersCount = 0;
    $results = [];
    
    foreach ($questions as $index => $question) {
        if (isset($_SESSION['answers'][$testName][$index])) {
            $userAnswers = $_SESSION['answers'][$testName][$index];
            $correctAnswers = array_filter($question['answers'], fn($answer) => $answer['correct']);
            $correct = checkAnswers($userAnswers, $correctAnswers);
            $results[] = [
                'question' => $question['question'],
                'user_answers' => implode(', ', $userAnswers),
                'correct_answers' => implode(', ', array_map(fn($a) => $a['text'], $correctAnswers)),
                'result' => $correct ? 'Правильно' : 'Неправильно'
            ];
            if ($correct) {
                $correctAnswersCount++;
            }
        }
    }

    // Получим общее количество вопросов
    $totalQuestions = count($questions);

    // Создание нового Excel файла
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Заголовки таблицы
    $sheet->setCellValue('A1', 'Вопрос');
    $sheet->setCellValue('B1', 'Ответ');
    $sheet->setCellValue('C1', 'Правильный ответ');
    $sheet->setCellValue('D1', 'Результат');
    $sheet->setCellValue('E1', 'Из скольких правильных всего');

    // Заполнение таблицы результатами
    $row = 2;
    foreach ($results as $result) {
        $sheet->setCellValue('A' . $row, $result['question']);
        $sheet->setCellValue('B' . $row, $result['user_answers']);
        $sheet->setCellValue('C' . $row, $result['correct_answers']);
        $sheet->setCellValue('D' . $row, $result['result']);
        $sheet->setCellValue('E' . $row, "$correctAnswersCount/$totalQuestions");
        $row++;
    }

    // Запись файла Excel
    $writer = new Xlsx($spreadsheet);
    $filename = 'results_' . $testName . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $writer->save('php://output');
    exit;
}
?>
