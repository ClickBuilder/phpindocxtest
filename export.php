<?php
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=test_export.xls");

if (!isset($_GET['test'])) {
    die("Тест не указан.");
}

$testName = $_GET['test'];
$testPath = __DIR__ . '/tests/' . $testName;

if (!file_exists($testPath)) {
    die("Тест не найден.");
}

$content = file_get_contents($testPath);
echo "Название теста\tКонтент\n";
echo "$testName\t$content\n";

