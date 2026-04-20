<?php
// profile.php
require_once '../config/session.php';
require_once '../config/database.php';
requireLogin();

$camaba_id = $_SESSION['camaba_id'];
$message = '';
$message_type = '';

// Status text mapping
$statusText = [
    'belum_verifikasi' => 'Belum Verifikasi',
    'baru' => 'Pendaftaran Baru',
    'aktif' => 'Aktif',
    'sudah_ujian' => 'Sudah Ujian',
    'lulus' => 'Lulus Seleksi',
    'gagal' => 'Tidak Lulus',
    'daftar_ulang' => 'Daftar Ulang',
    'pending' => 'Menunggu Verifikasi'
];

$statusBayarText = [
    'belum' => 'Belum Bayar',
    'menunggu' => 'Menunggu Verifikasi',
    'lunas' => 'Lunas'
];

// Ambil data camaba
$sql = "SELECT * FROM camaba WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $camaba_id);
$stmt->execute();
$result = $stmt->get_result();
$camaba = $result->fetch_assoc();
$stmt->close();

// Proses Update Data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update data dasar
        $username = $_POST['username'];
        $no_hp = $_POST['no_hp'];
        
        // Cek jika username sudah digunakan oleh orang lain
        $check_sql = "SELECT id FROM camaba WHERE username = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $username, $camaba_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $message = "Username sudah digunakan! Silakan pilih username lain.";
            $message_type = "danger";
        } else {
            $update_sql = "UPDATE camaba SET username = ?, no_hp = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssi", $username, $no_hp, $camaba_id);
            
            if ($update_stmt->execute()) {
                $message = "Profile berhasil diperbarui!";
                $message_type = "success";
                // Refresh data
                $sql = "SELECT * FROM camaba WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $camaba_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $camaba = $result->fetch_assoc();
                $stmt->close();
            } else {
                $message = "Gagal memperbarui profile!";
                $message_type = "danger";
            }
            $update_stmt->close();
        }
        $check_stmt->close();
    }
    
    elseif (isset($_POST['update_email'])) {
        // Update email dengan verifikasi
        $new_email = $_POST['email'];
        
        // Validasi email
        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $message = "Format email tidak valid!";
            $message_type = "danger";
        } else {
            // Cek jika email sudah digunakan oleh orang lain
            $check_sql = "SELECT id FROM camaba WHERE email = ? AND id != ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("si", $new_email, $camaba_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $message = "Email sudah digunakan! Silakan gunakan email lain.";
                $message_type = "danger";
            } else {
                // Simpan status asli di SESSION
                $_SESSION['status_asli'] = $camaba['status'];
                $_SESSION['email_lama'] = $camaba['email'];
                
                // Generate kode verifikasi 6 digit
                $kode_verifikasi = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                
                // Update email dan set status sementara
                $update_sql = "UPDATE camaba SET email = ?, kode_verifikasi = ?, status = 'belum_verifikasi' WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssi", $new_email, $kode_verifikasi, $camaba_id);
                
                if ($update_stmt->execute()) {
                    $_SESSION['verification_code'] = $kode_verifikasi;
                    $message = "Email berhasil diubah! Kode verifikasi telah dikirim ke email baru Anda. (Kode: $kode_verifikasi)";
                    $message_type = "success";
                    
                    // Refresh data
                    $sql = "SELECT * FROM camaba WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $camaba_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $camaba = $result->fetch_assoc();
                    $stmt->close();
                } else {
                    $message = "Gagal mengubah email!";
                    $message_type = "danger";
                }
                $update_stmt->close();
            }
            $check_stmt->close();
        }
    }
    
    elseif (isset($_POST['verifikasi_email'])) {
        // Verifikasi email dengan kode
        $kode_input = $_POST['kode_verifikasi'];
        
        if ($kode_input == $camaba['kode_verifikasi']) {
            // Kembalikan ke status asli yang disimpan di session
            $status_asli = isset($_SESSION['status_asli']) ? $_SESSION['status_asli'] : 'baru';
            
            $update_sql = "UPDATE camaba SET status = ?, kode_verifikasi = NULL WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $status_asli, $camaba_id);
            
            if ($update_stmt->execute()) {
                // Hapus data session
                unset($_SESSION['status_asli']);
                unset($_SESSION['email_lama']);
                if (isset($_SESSION['verification_code'])) {
                    unset($_SESSION['verification_code']);
                }
                
                $message = "Email berhasil diverifikasi!";
                $message_type = "success";
                
                // Refresh data
                $sql = "SELECT * FROM camaba WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $camaba_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $camaba = $result->fetch_assoc();
                $stmt->close();
            }
            $update_stmt->close();
        } else {
            $message = "Kode verifikasi salah!";
            $message_type = "danger";
        }
    }
    
    elseif (isset($_POST['batal_verifikasi'])) {
        // Batalkan perubahan email dan kembali ke email lama
        if (isset($_SESSION['email_lama'])) {
            $email_lama = $_SESSION['email_lama'];
            $status_asli = isset($_SESSION['status_asli']) ? $_SESSION['status_asli'] : 'baru';
            
            $update_sql = "UPDATE camaba SET email = ?, status = ?, kode_verifikasi = NULL WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssi", $email_lama, $status_asli, $camaba_id);
            
            if ($update_stmt->execute()) {
                // Hapus data session
                unset($_SESSION['status_asli']);
                unset($_SESSION['email_lama']);
                unset($_SESSION['verification_code']);
                
                $message = "Perubahan email dibatalkan!";
                $message_type = "success";
                
                // Refresh data
                $sql = "SELECT * FROM camaba WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $camaba_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $camaba = $result->fetch_assoc();
                $stmt->close();
            }
            $update_stmt->close();
        }
    }
    
    elseif (isset($_POST['resend_verifikasi'])) {
        // Kirim ulang kode verifikasi
        if ($camaba['status'] == 'belum_verifikasi') {
            // Generate kode verifikasi baru
            $kode_verifikasi = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            $update_sql = "UPDATE camaba SET kode_verifikasi = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $kode_verifikasi, $camaba_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['verification_code'] = $kode_verifikasi;
                $message = "Kode verifikasi baru telah dikirim! (Kode: $kode_verifikasi)";
                $message_type = "success";
                
                // Refresh data
                $sql = "SELECT * FROM camaba WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $camaba_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $camaba = $result->fetch_assoc();
                $stmt->close();
            }
            $update_stmt->close();
        } else {
            $message = "Tidak dapat mengirim ulang kode verifikasi!";
            $message_type = "danger";
        }
    }
    
    elseif (isset($_POST['upload_foto'])) {
        // Upload foto profil
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (in_array($_FILES['foto_profil']['type'], $allowed_types)) {
                if ($_FILES['foto_profil']['size'] <= $max_size) {
                    // Generate nama file unik
                    $ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
                    $new_filename = 'profile_' . $camaba_id . '_' . time() . '.' . $ext;
                    $upload_dir = '../uploads/profile/';
                    
                    // Buat folder jika belum ada
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $target_file = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $target_file)) {
                        // Hapus foto lama jika ada
                        if (!empty($camaba['foto_profil']) && file_exists('../' . $camaba['foto_profil'])) {
                            unlink('../' . $camaba['foto_profil']);
                        }
                        
                        // Update database
                        $foto_path = 'uploads/profile/' . $new_filename;
                        $update_sql = "UPDATE camaba SET foto_profil = ? WHERE id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("si", $foto_path, $camaba_id);
                        
                        if ($update_stmt->execute()) {
                            $message = "Foto profil berhasil diupload!";
                            $message_type = "success";
                            // Refresh data
                            $sql = "SELECT * FROM camaba WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $camaba_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $camaba = $result->fetch_assoc();
                            $stmt->close();
                        }
                        $update_stmt->close();
                    } else {
                        $message = "Gagal mengupload foto!";
                        $message_type = "danger";
                    }
                } else {
                    $message = "Ukuran file terlalu besar (maksimal 2MB)!";
                    $message_type = "danger";
                }
            } else {
                $message = "Format file tidak didukung (hanya JPG, PNG, GIF)!";
                $message_type = "danger";
            }
        } else {
            $message = "Silakan pilih file foto!";
            $message_type = "danger";
        }
    }
    
    elseif (isset($_POST['hapus_foto'])) {
        // Hapus foto profil
        if (!empty($camaba['foto_profil']) && file_exists('../' . $camaba['foto_profil'])) {
            unlink('../' . $camaba['foto_profil']);
        }
        
        $update_sql = "UPDATE camaba SET foto_profil = NULL WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $camaba_id);
        
        if ($update_stmt->execute()) {
            $message = "Foto profil berhasil dihapus!";
            $message_type = "success";
            // Refresh data
            $sql = "SELECT * FROM camaba WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $camaba_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $camaba = $result->fetch_assoc();
            $stmt->close();
        }
        $update_stmt->close();
    }
}
?>