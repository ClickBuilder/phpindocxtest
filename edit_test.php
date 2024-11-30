<?php
// Директория с сгенерированными тестами
$generatedDir = __DIR__ . '/generated_tests';

// Проверка, был ли передан файл для редактирования
if (isset($_GET['test'])) {
    $testName = $_GET['test'];
    $testFilePath = $generatedDir . '/' . $testName . '.php';

    // Проверка на существование файла
    if (!file_exists($testFilePath)) {
        die('Тест не найден!');
    }

    // Загружаем текущие вопросы и ответы из файла
    $questions = include($testFilePath);
} else {
    die('Не указан тест для редактирования!');
}

// Функция для сохранения изменений
function saveTestFile($testName, $questions) {
    global $generatedDir;
    $filePath = $generatedDir . '/' . $testName . '.php';
    file_put_contents($filePath, '<?php return ' . var_export($questions, true) . ';');
}

// Если форма отправлена, сохраняем изменения
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обработка отправленных данных
    $newQuestions = [];
    foreach ($_POST['question'] as $index => $question) {
        $newQuestions[$index] = [
            'question' => $question,
            'answers' => []
        ];
        foreach ($_POST['answer'][$index] as $answerIndex => $answer) {
            $newQuestions[$index]['answers'][$answerIndex] = [
                'text' => $answer,
                'correct' => isset($_POST['correct'][$index][$answerIndex]) ? true : false
            ];
        }
    }

    // Сохраняем изменения
    saveTestFile($testName, $newQuestions);
    echo "Тест '$testName' успешно отредактирован!";
    $questions = $newQuestions; // обновление вопросов
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование теста</title>
    <style>
        .answers {
            margin-left: 20px;
        }
        .answer {
            margin-bottom: 10px;
        }
        .add-answer-btn {
            margin-top: 5px;
        }
        .remove-btn {
            color: red;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
        }
        .remove-question-btn {
            color: red;
            cursor: pointer;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <h1>Редактирование теста: <?php echo htmlspecialchars($testName); ?></h1>
    <form action="edit_test.php?test=<?php echo urlencode($testName); ?>" method="post">
        <?php foreach ($questions as $index => $question): ?>
            <fieldset id="question-<?php echo $index; ?>">
                <legend><?php echo htmlspecialchars($question['question']); ?></legend>
                <label for="question-<?php echo $index; ?>">Вопрос:</label>
                <input type="text" name="question[<?php echo $index; ?>]" id="question-<?php echo $index; ?>" value="<?php echo htmlspecialchars($question['question']); ?>"><br><br>

                <div class="answers" id="answers-<?php echo $index; ?>">
                    <?php foreach ($question['answers'] as $answerIndex => $answer): ?>
                        <div class="answer" id="answer-<?php echo $index; ?>-<?php echo $answerIndex; ?>">
                            <label for="answer-<?php echo $index; ?>-<?php echo $answerIndex; ?>">Ответ <?php echo $answerIndex + 1; ?>:</label>
                            <input type="text" name="answer[<?php echo $index; ?>][<?php echo $answerIndex; ?>]" id="answer-<?php echo $index; ?>-<?php echo $answerIndex; ?>" value="<?php echo htmlspecialchars($answer['text']); ?>">
                            <label>
                                <input type="checkbox" name="correct[<?php echo $index; ?>][<?php echo $answerIndex; ?>]" <?php echo $answer['correct'] ? 'checked' : ''; ?>>
                                Правильный ответ
                            </label>
                            <span class="remove-btn" onclick="removeAnswer(<?php echo $index; ?>, <?php echo $answerIndex; ?>)">-</span>
                            <br><br>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" class="add-answer-btn" onclick="addAnswer(<?php echo $index; ?>)">Добавить ответ</button>
                <br><br>

                <span class="remove-question-btn" onclick="removeQuestion(<?php echo $index; ?>)">Удалить вопрос</span>
            </fieldset>
        <?php endforeach; ?>

        <button type="button" onclick="addQuestion()">Добавить вопрос</button><br><br>
        <button type="submit">Сохранить изменения</button>
    </form>

    <script>
        // Функция для добавления нового вопроса
        function addQuestion() {
            const form = document.querySelector('form');
            const newQuestionIndex = form.querySelectorAll('fieldset').length;

            const newFieldset = document.createElement('fieldset');
            newFieldset.innerHTML = `
                <legend>Новый вопрос</legend>
                <label for="question-${newQuestionIndex}">Вопрос:</label>
                <input type="text" name="question[${newQuestionIndex}]" id="question-${newQuestionIndex}" value=""><br><br>
                <div class="answers" id="answers-${newQuestionIndex}">
                    <div class="answer" id="answer-${newQuestionIndex}-0">
                        <label for="answer-${newQuestionIndex}-0">Ответ 1:</label>
                        <input type="text" name="answer[${newQuestionIndex}][0]" id="answer-${newQuestionIndex}-0" value="">
                        <label>
                            <input type="checkbox" name="correct[${newQuestionIndex}][0]">
                            Правильный ответ
                        </label>
                        <span class="remove-btn" onclick="removeAnswer(${newQuestionIndex}, 0)">-</span>
                    </div>
                </div>
                <button type="button" class="add-answer-btn" onclick="addAnswer(${newQuestionIndex})">Добавить ответ</button><br><br>
                <span class="remove-question-btn" onclick="removeQuestion(${newQuestionIndex})">Удалить вопрос</span>
            `;
            form.insertBefore(newFieldset, form.querySelector('button[type="submit"]'));
        }

        // Функция для добавления нового ответа
        function addAnswer(questionIndex) {
            const answersDiv = document.querySelector(`#answers-${questionIndex}`);
            const answerIndex = answersDiv.querySelectorAll('.answer').length;

            const newAnswerDiv = document.createElement('div');
            newAnswerDiv.classList.add('answer');
            newAnswerDiv.innerHTML = `
                <label for="answer-${questionIndex}-${answerIndex}">Ответ ${answerIndex + 1}:</label>
                <input type="text" name="answer[${questionIndex}][${answerIndex}]" id="answer-${questionIndex}-${answerIndex}" value="">
                <label>
                    <input type="checkbox" name="correct[${questionIndex}][${answerIndex}]">
                    Правильный ответ
                </label>
                <span class="remove-btn" onclick="removeAnswer(${questionIndex}, ${answerIndex})">-</span>
                <br><br>
            `;
            answersDiv.appendChild(newAnswerDiv);
        }

        // Функция для удаления ответа
        function removeAnswer(questionIndex, answerIndex) {
            const answerDiv = document.querySelector(`#answer-${questionIndex}-${answerIndex}`);
            answerDiv.remove();
        }

        // Функция для удаления вопроса
        function removeQuestion(questionIndex) {
            const fieldset = document.querySelector(`#question-${questionIndex}`);
            fieldset.remove();
        }
    </script>
</body>
</html>
