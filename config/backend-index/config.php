<?php
require_once 'config/session.php';
require_once 'config/database.php';

$userData = null;
if (isLoggedIn()) {
    $camaba_id = $_SESSION['camaba_id'];
    $sql = "SELECT nama_lengkap, nomor_tes, status, nilai_ujian FROM camaba WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $camaba_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();
}
?>