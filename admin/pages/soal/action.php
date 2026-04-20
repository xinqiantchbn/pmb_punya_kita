<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login/login.php");
    exit();
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kategori = $_POST['kategori'];
    $pertanyaan = $_POST['pertanyaan'];
    $opsi_a = $_POST['opsi_a'];
    $opsi_b = $_POST['opsi_b'];
    $opsi_c = $_POST['opsi_c'];
    $opsi_d = $_POST['opsi_d'];
    $jawaban_benar = $_POST['jawaban_benar'];
    $dibuat_oleh = $_SESSION['admin_id'];

    if ($action == 'add') {
        $sql = "INSERT INTO soal (kategori, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban_benar, dibuat_oleh, dibuat_pada) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", $kategori, $pertanyaan, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $jawaban_benar, $dibuat_oleh);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Soal berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "Error: " . $stmt->error;
        }
        $stmt->close();
    } elseif ($action == 'edit') {
        $id = $_POST['id'];
        $sql = "UPDATE soal SET kategori = ?, pertanyaan = ?, opsi_a = ?, opsi_b = ?, opsi_c = ?, opsi_d = ?, jawaban_benar = ? WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", $kategori, $pertanyaan, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $jawaban_benar, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Soal berhasil diperbarui!";
        } else {
            $_SESSION['error'] = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
    
    header("Location: ../index.php?page=soal");
    exit();
}
?>