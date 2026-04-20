<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require_once '../config/database.php';

// Fungsi untuk menyalin data camaba ke ujian_log
function copyCamabaToUjianLog($conn, $ujian_id) {
    
    // Ambil token ujian
    $token_query = $conn->query("SELECT token_ujian FROM ujian_setting WHERE id = '$ujian_id'");
    $token_data = $token_query->fetch_assoc();
    $token = $token_data['token_ujian'] ?? '';
    
    // Ambil data camaba yang eligible
    $sql = "
        INSERT INTO ujian_log 
        (ujian_id, camaba_id, nama_lengkap, status, token_ujian)
        SELECT 
            '$ujian_id' as ujian_id,
            c.nomor_tes as camaba_id,
            c.nama_lengkap,
            'waiting' as status,
            '$token' as token_ujian
        FROM camaba c
        WHERE c.status IN ('aktif', 'baru', 'belum_verifikasi')
        AND NOT EXISTS (
            SELECT 1 FROM ujian_log ul 
            WHERE ul.ujian_id = '$ujian_id' 
            AND ul.camaba_id = c.nomor_tes
        )
    ";
    
    // Eksekusi batch insert
    $result = $conn->query($sql);
    
    if ($result) {
        return $conn->affected_rows;
    } else {
        error_log("Gagal batch insert ke ujian_log: " . $conn->error);
        return 0;
    }
}

// HANYA handle AJAX request untuk ujian
if (isset($_GET['action']) && isset($_GET['page']) && $_GET['page'] == 'ujian') {
    
    // Bersihkan semua output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set header JSON dengan charset yang benar
    header('Content-Type: application/json; charset=utf-8');
    
    $action = $_GET['action'];
    $response = [];
    
    try {
        if ($action == 'start') {
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
                        // Salin data camaba ke ujian_log
                        $copied = copyCamabaToUjianLog($conn, $setting['id']);
                        
                        $response = [
                            'success' => true, 
                            'message' => 'Ujian berhasil dimulai! ' . $copied . ' peserta ditambahkan.',
                            'token' => $setting['token_ujian'],
                            'count' => $copied
                        ];
                    } else {
                        $response = ['success' => false, 'message' => 'Gagal mengupdate database.'];
                    }
                }
            }
            
        } elseif ($action == 'stop') {
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
                
                $response = [
                    'success' => true, 
                    'message' => 'Ujian berhasil dihentikan.'
                ];
            }
            
        } elseif ($action == 'get_ujian_time') {
            // Get current exam time and participant status
            $setting = $conn->query("SELECT * FROM ujian_setting WHERE status = 'active' LIMIT 1")->fetch_assoc();
            
            if ($setting) {
                // Hitung waktu tersisa berdasarkan durasi
                $start_time = strtotime($setting['tanggal_ujian'] . ' ' . $setting['jam_mulai']);
                $current_time = time();
                $durasi_menit = $setting['durasi_menit'] ?? 120;
                
                // Waktu akhir ujian = waktu mulai + durasi
                $end_time = $start_time + ($durasi_menit * 60);
                $remaining = $end_time - $current_time;
                
                if ($remaining > 0) {
                    $hours = floor($remaining / 3600);
                    $minutes = floor(($remaining % 3600) / 60);
                    $seconds = $remaining % 60;
                    $remaining_time = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                } else {
                    $remaining_time = '00:00:00';
                }
                
                $response = [
                    'success' => true,
                    'remaining' => $remaining_time
                ];
            } else {
                $response = ['success' => false, 'message' => 'Tidak ada ujian aktif'];
            }
            
        } elseif ($action == 'send_tokens') {
            // Kirim token ke semua peserta
            $setting = $conn->query("SELECT * FROM ujian_setting ORDER BY id DESC LIMIT 1")->fetch_assoc();
            
            if (!$setting) {
                $response = ['success' => false, 'message' => 'Setting ujian belum ada'];
            } else {
                // Ambil semua peserta dari ujian_log
                $peserta = $conn->query("SELECT COUNT(*) as total FROM ujian_log WHERE ujian_id = '{$setting['id']}'");
                $count = $peserta->fetch_assoc()['total'];
                
                $response = [
                    'success' => true,
                    'message' => 'Token berhasil dikirim',
                    'count' => $count
                ];
            }
            
        } elseif ($action == 'reset') {
            // Reset ujian untuk peserta tertentu
            $camaba_id = $conn->real_escape_string($_GET['camaba_id'] ?? '');
            
            if (empty($camaba_id)) {
                $response = ['success' => false, 'message' => 'ID peserta tidak valid'];
            } else {
                // Ambil ujian aktif
                $ujian_aktif = $conn->query("SELECT id FROM ujian_setting WHERE status = 'active' LIMIT 1")->fetch_assoc();
                
                if ($ujian_aktif) {
                    // Mulai transaksi untuk memastikan kedua update berhasil atau gagal bersama
                    $conn->begin_transaction();
                    
                    try {
                        // 1. Reset data di tabel ujian_log (LENGKAP dengan semua field)
                        $reset_log = $conn->query("
                            UPDATE ujian_log 
                            SET status = 'waiting', 
                                start_time = NULL,
                                waktu_mulai = NULL,
                                waktu_sisa = '0',
                                end_time = NULL,    
                                ip_address = NULL,
                                updated_at = NOW(),
                                jawaban_salah = '0',
                                jawaban_benar = '0', 
                                nilai_ujian = '0',
                                soal_order = NULL,
                                jawaban_json = NULL,
                                ditandai_json = NULL
                            WHERE ujian_id = '{$ujian_aktif['id']}' 
                            AND camaba_id = '$camaba_id'
                        ");
                        
                        if (!$reset_log) {
                            throw new Exception("Gagal mereset ujian_log: " . $conn->error);
                        }
                        
                        // 2. Reset data di tabel camaba (LENGKAP dengan semua field)
                        $reset_camaba = $conn->query("
                            UPDATE camaba 
                            SET status = 'aktif',
                                nilai_ujian = '0',
                                jawaban_benar = '0',
                                jawaban_salah = '0',
                                tanggal_ujian = NULL
                            WHERE nomor_tes = '$camaba_id'
                        ");
                        
                        if (!$reset_camaba) {
                            throw new Exception("Gagal mereset camaba: " . $conn->error);
                        }
                        
                        // Jika semua berhasil, commit transaksi
                        $conn->commit();
                        
                        // Cek berapa baris yang terpengaruh
                        $affected_log = $conn->affected_rows; // Untuk update ujian_log
                        
                        $response = [
                            'success' => true, 
                            'message' => "Ujian peserta berhasil direset. Data ujian_log dan camaba telah dikembalikan ke status awal.",
                            'affected' => $affected_log
                        ];
                        
                    } catch (Exception $e) {
                        // Jika terjadi error, rollback transaksi
                        $conn->rollback();
                        throw $e; // Akan ditangkap oleh catch block utama
                    }
                    
                } else {
                    $response = ['success' => false, 'message' => 'Tidak ada ujian aktif'];
                }
            }
            
        } elseif ($action == 'send_token' && isset($_GET['id'])) {
            // Kirim token ke peserta tertentu
            $camaba_id = $conn->real_escape_string($_GET['id'] ?? '');
            
            if (empty($camaba_id)) {
                $response = ['success' => false, 'message' => 'ID peserta tidak valid'];
            } else {
                // Ambil data peserta dari ujian_log
                $peserta = $conn->query("
                    SELECT nama_lengkap FROM ujian_log 
                    WHERE camaba_id = '$camaba_id' 
                    LIMIT 1
                ")->fetch_assoc();
                
                if ($peserta) {
                    $response = [
                        'success' => true,
                        'message' => 'Token berhasil dikirim ke ' . $peserta['nama_lengkap']
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Peserta tidak ditemukan'];
                }
            }
            
        } else {
            $response = ['success' => false, 'message' => 'Invalid action: ' . $action];
        }
        
    } catch (Exception $e) {
        // Pastikan rollback jika ada transaksi yang masih berjalan
        if (isset($conn) && $conn->connect_errno == 0) {
            $conn->rollback();
        }
        
        $response = [
            'success' => false,
            'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
        ];
    }
    
    echo json_encode($response);
    exit();
}

// Jika tidak ada parameter yang sesuai, kembalikan error
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>