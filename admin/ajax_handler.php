<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require_once '../config/database.php';

// Cek login
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

function copyCamabaToUjianLog($conn, $ujian_id) {
    // Ambil data camaba yang eligible
    $camaba_query = $conn->query("
        SELECT id, nomor_tes, nama_lengkap 
        FROM camaba 
        WHERE status IN ('aktif', 'baru', 'belum_verifikasi')
    ");
    
    $inserted = 0;
    
    while ($camaba = $camaba_query->fetch_assoc()) {
        // Cek apakah sudah ada di log untuk ujian ini (berdasarkan nomor_tes sebagai camaba_id)
        $check_query = $conn->query("
            SELECT id FROM ujian_log 
            WHERE ujian_id = '$ujian_id' 
            AND camaba_id = '" . $conn->real_escape_string($camaba['nomor_tes']) . "'
            LIMIT 1
        ");
        
        if ($check_query->num_rows == 0) {
            // Insert ke ujian_log (simpan juga user_id numeric untuk kemudahan lookup)
            $user_id = $conn->real_escape_string($camaba['id']);
            $sql = "INSERT INTO ujian_log 
                    (ujian_id, user_id, camaba_id, nama_lengkap, status) 
                    VALUES (
                        '$ujian_id', 
                        '$user_id',
                        '" . $conn->real_escape_string($camaba['nomor_tes']) . "', 
                        '" . $conn->real_escape_string($camaba['nama_lengkap']) . "', 
                        'waiting'
                    )";

            if ($conn->query($sql)) {
                $inserted++;
            } else {
                error_log("Gagal insert ke ujian_log: " . $conn->error);
            }
        }
    }
    
    return $inserted;
}

// Bersihkan output buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Set header JSON
header('Content-Type: application/json; charset=utf-8');

// Tentukan action dari GET atau POST
$action = $_GET['ajax_action'] ?? $_POST['action'] ?? '';
$response = [];

try {
    // ================ HANDLER UNTUK SETTING UJIAN ================
    if ($action == 'start_ujian') {
        // Cek apakah sudah ada ujian aktif
        $check_active = $conn->query("SELECT * FROM ujian_setting WHERE status = 'active' LIMIT 1");
        
        if ($check_active->num_rows > 0) {
            $response = [
                'success' => false, 
                'message' => 'Masih ada ujian aktif. Hentikan dulu ujian sebelumnya.'
            ];
        } else {
            // Ambil setting terbaru
            $setting = $conn->query("SELECT * FROM ujian_setting ORDER BY id DESC LIMIT 1")->fetch_assoc();
            
            if (!$setting) {
                $response = ['success' => false, 'message' => 'Setting ujian belum ada. Buat setting terlebih dahulu.'];
            } else if (empty($setting['nama_ujian']) || empty($setting['tanggal_ujian']) || 
                    empty($setting['jam_mulai']) || empty($setting['jam_selesai'])) {
                $response = ['success' => false, 'message' => 'Setting ujian belum lengkap. Lengkapi semua field.'];
            } else {
                // Generate token jika belum ada
                if (empty($setting['token_ujian'])) {
                    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                    $token = '';
                    for ($i = 0; $i < 6; $i++) {
                        $token .= $chars[rand(0, strlen($chars) - 1)];
                    }
                    
                    $conn->query("UPDATE ujian_setting SET token_ujian = '$token' WHERE id = '{$setting['id']}'");
                    $setting['token_ujian'] = $token;
                }
                
                // Update status ujian menjadi active
                $update_result = $conn->query("UPDATE ujian_setting SET status = 'active' WHERE id = '{$setting['id']}'");
                
                if ($update_result) {
                    // 1. Update status camaba
                    $conn->query("UPDATE camaba SET status = 'aktif' WHERE status IN ('baru')");
                    
                    // 2. HITUNG JUMLAH PESERTA
                    $peserta_result = $conn->query("SELECT * FROM camaba WHERE status IN ('aktif', 'baru')");
                    $count = $peserta_result->num_rows;
                    
                    // 3. COPY DATA CAMABA KE UJIAN_LOG (FUNGSI BARU)
                    copyCamabaToUjianLog($conn, $setting['id']);
                    
                    $response = [
                        'success' => true, 
                        'message' => 'Ujian berhasil dimulai!', 
                        'token' => $setting['token_ujian'],
                        'count' => $count
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Gagal mengupdate database: ' . $conn->error];
                }
            }
        }
        
    } elseif ($action == 'stop_ujian') {
        // Cek apakah ada ujian aktif
        $check_active = $conn->query("SELECT * FROM ujian_setting WHERE status = 'active' LIMIT 1");
        
        if ($check_active->num_rows == 0) {
            $response = ['success' => false, 'message' => 'Tidak ada ujian aktif'];
        } else {
            $active_ujian = $check_active->fetch_assoc();
            
            // Update status ujian menjadi completed
            $conn->query("UPDATE ujian_setting SET status = 'completed' WHERE id = '{$active_ujian['id']}'");
            
            // Update semua log ujian yang masih active untuk ujian ini
            $conn->query("UPDATE ujian_log SET status = 'expired', end_time = NOW() 
                        WHERE ujian_id = '{$active_ujian['id']}' AND status = 'active'");
            
            // Auto-submit jawaban peserta yang masih aktif ujian
            $active_peserta = $conn->query("
                SELECT ul.* 
                FROM ujian_log ul 
                WHERE ul.ujian_id = '{$active_ujian['id']}' 
                AND ul.status = 'active'
            ");
            
            while ($p = $active_peserta->fetch_assoc()) {
                if (!empty($p['jawaban_data'])) {
                    $jawaban_data = json_decode($p['jawaban_data'], true);
                    $jawaban_benar = 0;
                    $jawaban_salah = 0;
                    
                    // Ambil semua soal
                    $soal_result = $conn->query("SELECT id, jawaban_benar FROM soal");
                    $soal_data = [];
                    while ($soal = $soal_result->fetch_assoc()) {
                        $soal_data[$soal['id']] = $soal['jawaban_benar'];
                    }
                    
                    // Hitung nilai
                    foreach ($jawaban_data as $index => $jawaban) {
                        if ($jawaban !== null && isset($soal_data[$index])) {
                            if ($jawaban == $soal_data[$index]) {
                                $jawaban_benar++;
                            } else {
                                $jawaban_salah++;
                            }
                        }
                    }
                    
                    $total_soal = count($soal_data);
                    $nilai = $total_soal > 0 ? ($jawaban_benar / $total_soal) * 100 : 0;
                    
                    // Update nilai peserta
                    $conn->query("UPDATE camaba SET 
                                status = 'sudah_ujian', 
                                nilai_ujian = '$nilai',
                                jawaban_benar = '$jawaban_benar',
                                jawaban_salah = '$jawaban_salah',
                                tanggal_ujian = NOW()
                                WHERE id = '{$p['user_id']}'");
                }
            }
            
            $response = [
                'success' => true, 
                'message' => 'Ujian berhasil dihentikan. Semua peserta telah dikeluarkan.'
            ];
        }
        
    } elseif ($action == 'get_ujian_status') {
        // Get current exam status for real-time updates
        $setting = $conn->query("SELECT * FROM ujian_setting ORDER BY id DESC LIMIT 1")->fetch_assoc();
        
        if ($setting) {
            $peserta_active = $conn->query("SELECT COUNT(*) as total FROM ujian_log WHERE ujian_id = '{$setting['id']}' AND status = 'active'")->fetch_assoc();
            $peserta_completed = $conn->query("SELECT COUNT(*) as total FROM ujian_log WHERE ujian_id = '{$setting['id']}' AND status = 'completed'")->fetch_assoc();
            
            $response = [
                'success' => true,
                'setting' => $setting,
                'stats' => [
                    'active' => (int)$peserta_active['total'],
                    'completed' => (int)$peserta_completed['total']
                ]
            ];
        } else {
            $response = ['success' => false, 'message' => 'No exam setting found'];
        }
        
    } elseif ($action == 'reset_ujian') {
        // Reset semua data ujian
        $conn->query("UPDATE ujian_setting SET status = 'pending' WHERE status IN ('active', 'completed')");
        $conn->query("DELETE FROM ujian_log");
        $conn->query("UPDATE camaba SET 
                    status = 'aktif', 
                    nilai_ujian = 0,
                    jawaban_benar = 0,
                    jawaban_salah = 0,
                    tanggal_ujian = NULL
                    WHERE status = 'sudah_ujian'");
        
        $response = ['success' => true, 'message' => 'Semua data ujian telah direset'];
        
        
    } elseif ($action == 'delete_soal') {
    $id = intval($_POST['id']);
    
    // Get gambar untuk dihapus
    $sql = "SELECT pertanyaan_gambar, opsi_a_gambar, opsi_b_gambar, opsi_c_gambar, opsi_d_gambar 
            FROM soal WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $soal = $result->fetch_assoc();
    
    // PERBAIKAN: Path yang benar untuk gambar
    $upload_dir = '../uploads/soal/';
    $gambar_fields = ['pertanyaan_gambar', 'opsi_a_gambar', 'opsi_b_gambar', 'opsi_c_gambar', 'opsi_d_gambar'];
    
    foreach ($gambar_fields as $field) {
        if (!empty($soal[$field])) {
            $file_path = $upload_dir . $soal[$field];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }
    
    // Hapus dari database
    $sql = "DELETE FROM soal WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $response = ['success' => true];
    } else {
        $response = ['success' => false, 'message' => $stmt->error];
    }
    
} elseif ($action == 'tambah_kategori') {
    // Terima baik 'kategori' maupun 'nama_kategori' dari form
    $kategori = trim($_POST['kategori'] ?? $_POST['nama_kategori'] ?? '');

    if ($kategori === '') {
        $response = [
            'success' => false,
            'message' => 'Nama kategori tidak boleh kosong'
        ];
    } else {
        // Cek apakah kategori sudah ada
        $check = $conn->prepare("SELECT 1 FROM soal WHERE kategori = ? LIMIT 1");
        $check->bind_param("s", $kategori);
        $check->execute();
        $check_res = $check->get_result();

        if ($check_res && $check_res->num_rows > 0) {
            $response = [
                'success' => false,
                'message' => 'Kategori sudah ada'
            ];
        } else {
            // Buat 1 soal contoh untuk kategori baru
            $sample_question = 'Soal contoh untuk ' . $kategori;
            $opsA = 'Opsi A';
            $opsB = 'Opsi B';
            $opsC = 'Opsi C';
            $opsD = 'Opsi D';
            $jawaban = 'A';
            $dibuat_oleh = intval($_SESSION['admin_id'] ?? 0);

            $stmt = $conn->prepare(
                "INSERT INTO soal (kategori, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban_benar, dibuat_oleh, dibuat_pada) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->bind_param("sssssssi", $kategori, $sample_question, $opsA, $opsB, $opsC, $opsD, $jawaban, $dibuat_oleh);

            if ($stmt->execute()) {
                $response = [
                    'success' => true,
                    'message' => 'Kategori berhasil ditambahkan',
                    'kategori' => $kategori
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => $conn->error
                ];
            }
        }
    }
} elseif ($action == 'edit_kategori') {
    $kategori_lama = trim($_POST['kategori_lama'] ?? '');
    $kategori_baru = trim($_POST['kategori_baru'] ?? '');

    if ($kategori_lama === '' || $kategori_baru === '') {
        $response = ['success' => false, 'message' => 'Data kategori tidak lengkap'];
    } else {
        $stmt = $conn->prepare("UPDATE soal SET kategori = ? WHERE kategori = ?");
        $stmt->bind_param("ss", $kategori_baru, $kategori_lama);
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Kategori berhasil diubah'];
        } else {
            $response = ['success' => false, 'message' => $stmt->error];
        }
    }

    } elseif ($action == 'hapus_kategori') {
    $kategori = trim($_POST['kategori']);
    
    // Hapus semua gambar dari kategori ini
    $sql = "SELECT pertanyaan_gambar, opsi_a_gambar, opsi_b_gambar, opsi_c_gambar, opsi_d_gambar 
            FROM soal WHERE kategori = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $kategori);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // PERBAIKAN: Path yang benar
    $upload_dir = '../uploads/soal/';
    while ($soal = $result->fetch_assoc()) {
        $gambar_fields = ['pertanyaan_gambar', 'opsi_a_gambar', 'opsi_b_gambar', 'opsi_c_gambar', 'opsi_d_gambar'];
        foreach ($gambar_fields as $field) {
            if (!empty($soal[$field])) {
                $file_path = $upload_dir . $soal[$field];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        }
    }
    
    // Hapus semua soal dari kategori
    $sql = "DELETE FROM soal WHERE kategori = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $kategori);
    
    if ($stmt->execute()) {
        $response = ['success' => true];
    } else {
        $response = ['success' => false, 'message' => $stmt->error];
    }
    
        } elseif ($action == 'hapus_gambar') {
            // Hapus gambar segera via AJAX
            $soal_id = intval($_POST['id'] ?? 0);
            $field = $_POST['field'] ?? '';

            $allowed = ['pertanyaan_gambar','opsi_a_gambar','opsi_b_gambar','opsi_c_gambar','opsi_d_gambar'];
            if ($soal_id <= 0 || !in_array($field, $allowed)) {
                $response = ['success' => false, 'message' => 'Parameter tidak valid'];
            } else {
                $sql = "SELECT $field FROM soal WHERE id = ? LIMIT 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $soal_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res->fetch_assoc();
                $filename = $row[$field] ?? '';

                $upload_dir = __DIR__ . '/uploads/soal/';
                $deleted = false;
                if (!empty($filename) && file_exists($upload_dir . $filename)) {
                    if (@unlink($upload_dir . $filename)) {
                        $deleted = true;
                    }
                }

                // Logging delete attempt
                $logfile = $upload_dir . 'delete_debug.log';
                @file_put_contents($logfile, date('c') . " delete_gambar id={$soal_id} field={$field} file={$filename} deleted=" . ($deleted? '1':'0') . PHP_EOL, FILE_APPEND);

                // Update DB column (set to empty)
                $upd = $conn->prepare("UPDATE soal SET $field = '' WHERE id = ?");
                $upd->bind_param("i", $soal_id);
                if ($upd->execute()) {
                    $response = ['success' => true, 'deleted' => $deleted];
                } else {
                    $response = ['success' => false, 'message' => $conn->error];
                }
            }

        } else {
            $response = ['success' => false, 'message' => 'Invalid action: ' . $action];
        }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
exit();