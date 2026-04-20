<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['soal_ids']) || !isset($_SESSION['current_question']) || !isset($_SESSION['jawaban'])) {
    echo json_encode([]);
    exit();
}

$total_soal = count($_SESSION['soal_ids']);
$current = $_SESSION['current_question'];
$answered = count(array_filter($_SESSION['jawaban']));
$progress = (($current + 1) / $total_soal) * 100;

echo json_encode([
    'total' => $total_soal,
    'current' => $current,
    'answered' => $answered,
    'progress' => $progress
]);
?>