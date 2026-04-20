<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['nomor_tes'])) {
    die("Akses ditolak");
}

$nomor_tes = $_SESSION['nomor_tes'];

// ambil data camaba
$camaba = $conn->query("
    SELECT nama_lengkap, prodi_pilihan, status, id 
    FROM camaba 
    WHERE nomor_tes = '$nomor_tes'
")->fetch_assoc();

if (!$camaba) {
    http_response_code(403);
    exit('Akses ditolak');
}

// CEK APAKAH USER SUDAH PERNAH UJIAN
$cek_sudah_ujian = $conn->query("
    SELECT * FROM ujian_log 
    WHERE camaba_id = '$nomor_tes' 
    AND status = 'completed'
    ORDER BY id DESC LIMIT 1
");

if ($cek_sudah_ujian->num_rows > 0) {
    // Jika sudah pernah ujian, redirect ke index (tetap login)
    header('Location: index.php?status=sudah_ujian');
    exit;
}

// ambil ujian aktif
$ujian = $conn->query("
    SELECT * FROM ujian_setting 
    WHERE status = 'active' 
    LIMIT 1
")->fetch_assoc();

if (!$ujian) {
    die("Ujian belum aktif");
}

$kategori = $camaba['prodi_pilihan'] ?: 'Umum';

// CEK APAKAH UDAH ADA UJIAN_LOG
$log_check = $conn->query("
    SELECT * FROM ujian_log 
    WHERE camaba_id = '$nomor_tes' 
    AND ujian_id = '{$ujian['id']}'
");
$log = $log_check->fetch_assoc();

$waktu_sekarang = time();
$durasi_total = $ujian['durasi_menit'] * 60; // konversi ke detik

if ($log) {
    // KALAU UDAH ADA, CEK WAKTUNYA
    if ($log['status'] == 'completed') {
        // Redirect ke index jika sudah completed (tindakan pengamanan)
        header('Location: index.php?status=sudah_ujian');
        exit;
    }
    
    if ($log['waktu_mulai']) {
        $waktu_mulai = strtotime($log['waktu_mulai']);
        $waktu_berlalu = $waktu_sekarang - $waktu_mulai;
        $sisa_waktu = max(0, $durasi_total - $waktu_berlalu);
        
        // Update status jadi active
        $conn->query("
            UPDATE ujian_log 
            SET status = 'active' 
            WHERE id = '{$log['id']}'
        ");
    } else {
        // KALAU BELUM ADA WAKTU MULAI, SET SEKARANG
        $conn->query("
            UPDATE ujian_log 
            SET status = 'active', 
                waktu_mulai = NOW(),
                waktu_sisa = $durasi_total
            WHERE id = '{$log['id']}'
        ");
        $sisa_waktu = $durasi_total;
    }
    
    // AMBIL URUTAN SOAL YANG SUDAH DISIMPAN
    $soal_order = $log['soal_order'];
    if ($soal_order) {
        $soal_ids = json_decode($soal_order, true);
        if (is_array($soal_ids) && count($soal_ids) > 0) {
            $ids_string = implode(',', array_map('intval', $soal_ids));
            $soal_q = $conn->query("
                SELECT * FROM soal 
                WHERE id IN ($ids_string)
                ORDER BY FIELD(id, $ids_string)
            ");
            
            $soal = [];
            while ($s = $soal_q->fetch_assoc()) {
                $soal[] = $s;
            }
        } else {
            // FALLBACK: generate ulang
            $soal = generateSoalBaru($conn, $kategori, $log['id'], $nomor_tes, $ujian['id']);
        }
    } else {
        // BELUM ADA SOAL ORDER, GENERATE BARU
        $soal = generateSoalBaru($conn, $kategori, $log['id'], $nomor_tes, $ujian['id']);
    }
} else {
    // BUAT LOG BARU
    $conn->query("
        INSERT INTO ujian_log (
            camaba_id, 
            nama_lengkap, 
            ujian_id, 
            status, 
            waktu_mulai, 
            waktu_sisa,
            ip_address
        ) VALUES (
            '$nomor_tes',
            '{$camaba['nama_lengkap']}',
            '{$ujian['id']}',
            'active',
            NOW(),
            $durasi_total,
            '{$_SERVER['REMOTE_ADDR']}'
        )
    ");
    
    $log_id = $conn->insert_id;
    $sisa_waktu = $durasi_total;
    
    // GENERATE SOAL BARU
    $soal = generateSoalBaru($conn, $kategori, $log_id, $nomor_tes, $ujian['id']);
}

// FUNGSI UNTUK GENERATE SOAL
function generateSoalBaru($conn, $kategori, $log_id, $nomor_tes, $ujian_id) {
    // Ambil semua soal sesuai jurusan
    $soal_q = $conn->query("
        SELECT * FROM soal 
        WHERE kategori = '$kategori'
    ");
    
    $soal_list = [];
    while ($s = $soal_q->fetch_assoc()) {
        $soal_list[] = $s;
    }
    
    // Acak soal
    shuffle($soal_list);
    
    // Simpan urutan ID soal ke database
    $soal_ids = array_column($soal_list, 'id');
    $soal_order_json = json_encode($soal_ids);
    
    $conn->query("
        UPDATE ujian_log 
        SET soal_order = '$soal_order_json' 
        WHERE id = '$log_id'
    ");
    
    return $soal_list;
}

$total_soal = count($soal);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian Online - PMB Universitas Kita</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* (CSS ANDA TETAP SAMA) */
        :root {
            --primary-red: #c62828;
            --dark-red: #b71c1c;
            --light-red: #ff5252;
            --accent-red: #ff8a80;
            --bg-light: #fff5f5;
            --text-dark: #212121;
            --text-light: #757575;
            --white: #ffffff;
            --success: #4CAF50;
            --warning: #ff9800;
            --info: #2196F3;
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            color: var(--text-dark);
            overflow-x: hidden;
        }
        
        .exam-header {
            background: linear-gradient(135deg, var(--dark-red), var(--primary-red));
            color: var(--white);
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(183, 28, 28, 0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .exam-title {
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0;
        }
        
        .exam-title i {
            color: var(--accent-red);
            margin-right: 10px;
        }
        
        .user-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .user-info p {
            margin: 0;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .timer-container {
            background: linear-gradient(135deg, #ff5252, #ff8a80);
            color: var(--white);
            padding: 15px 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(255, 82, 82, 0.3);
        }
        
        .timer-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .timer-display {
            font-size: 2rem;
            font-weight: 700;
            font-family: monospace;
            letter-spacing: 2px;
        }
        
        .timer-warning {
            color: #ffeb3b;
            animation: pulse 1s infinite;
        }
        
        .timer-danger {
            color: #ff5252;
            animation: pulse 0.5s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .main-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        
        .question-container {
            flex: 1;
            background: var(--white);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid #e0e0e0;
            min-height: calc(100vh - 200px);
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--bg-light);
        }
        
        .question-number {
            color: var(--dark-red);
            font-weight: 700;
            font-size: 1.4rem;
            margin: 0;
        }
        
        .nav-btn-container {
            position: relative;
        }
        
        .btn-quick-nav {
            background: linear-gradient(135deg, var(--primary-red), var(--light-red));
            color: var(--white);
            width: 50px;
            height: 50px;
            border-radius: 12px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(198, 40, 40, 0.3);
            transition: all 0.3s;
            position: relative;
        }
        
        .btn-quick-nav:hover {
            transform: translateY(-3px) rotate(5deg);
            box-shadow: 0 8px 25px rgba(198, 40, 40, 0.4);
        }
        
        .nav-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--warning);
            color: white;
            font-size: 0.8rem;
            min-width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 6px;
            font-weight: 700;
            border: 2px solid var(--white);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .question-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .question-text {
            font-size: 1.15rem;
            line-height: 1.7;
            margin-bottom: 30px;
            color: var(--text-dark);
            flex-shrink: 0;
        }
        
        .question-image-container {
            text-align: center;
            margin-bottom: 30px;
            flex-shrink: 0;
        }
        
        .question-image {
            max-width: 100%;
            max-height: 400px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            object-fit: contain;
            border: 1px solid #e0e0e0;
            background: #f8f9fa;
            padding: 10px;
        }
        
        .options-container {
            margin-top: auto;
        }
        
        .option-item {
            margin-bottom: 15px;
            padding: 18px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .option-item:hover {
            border-color: var(--light-red);
            background-color: #fff5f5;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(198, 40, 40, 0.1);
        }
        
        .option-item.selected {
            border-color: var(--success);
            background-color: #e8f5e9;
        }
        
        .option-radio {
            margin-top: 3px;
            flex-shrink: 0;
        }
        
        .option-radio input {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .option-content {
            flex: 1;
        }
        
        .option-label {
            font-weight: 600;
            color: var(--dark-red);
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .option-text {
            color: var(--text-dark);
            line-height: 1.6;
            font-size: 1.05rem;
            margin-bottom: 10px;
        }
        
        .option-image-container {
            margin-top: 10px;
        }
        
        .option-image {
            max-width: 100%;
            max-height: 250px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            object-fit: contain;
            border: 1px solid #e0e0e0;
            background: #f8f9fa;
            padding: 8px;
        }
        
        .action-buttons {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid var(--bg-light);
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }
        
        .btn-exam {
            padding: 14px 30px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 1rem;
            min-width: 160px;
        }
        
        .btn-prev {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: var(--white);
        }
        
        .btn-prev:hover {
            background: linear-gradient(135deg, #495057, #343a40);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(108, 117, 125, 0.3);
        }
        
        .btn-next {
            background: linear-gradient(135deg, var(--primary-red), var(--light-red));
            color: var(--white);
        }
        
        .btn-next:hover {
            background: linear-gradient(135deg, var(--dark-red), var(--primary-red));
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(198, 40, 40, 0.3);
        }
        
        .btn-mark {
            background: linear-gradient(135deg, #ff9800, #ffb74d);
            color: var(--white);
        }
        
        .btn-mark.marked {
            background: linear-gradient(135deg, #ffb74d, #ff9800);
            box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.3);
        }
        
        .btn-mark:hover {
            background: linear-gradient(135deg, #f57c00, #ff9800);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 152, 0, 0.3);
        }
        
        .btn-mark.marked:hover {
            box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.3), 0 8px 20px rgba(255, 152, 0, 0.3);
        }
        
        .btn-nav {
            background: linear-gradient(135deg, var(--primary-red), var(--light-red));
            color: var(--white);
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .btn-nav:hover {
            background: linear-gradient(135deg, var(--dark-red), var(--primary-red));
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(198, 40, 40, 0.3);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #4CAF50, #66bb6a);
            color: var(--white);
            padding: 14px 35px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            margin-top: 20px;
            width: 100%;
            justify-content: center;
        }
        
        .btn-submit:hover {
            background: linear-gradient(135deg, #388E3C, #4CAF50);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(76, 175, 80, 0.3);
        }
        
        /* Navigation Modal */
        .nav-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(5px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .nav-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .nav-modal {
            background: var(--white);
            border-radius: 20px;
            padding: 30px;
            max-width: 900px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            border: 5px solid var(--light-red);
            transform: translateY(20px);
            transition: transform 0.3s;
        }
        
        .nav-modal.active {
            transform: translateY(0);
        }
        
        .nav-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--bg-light);
        }
        
        .nav-title {
            color: var(--dark-red);
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0;
        }
        
        .nav-close {
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .nav-close:hover {
            color: var(--primary-red);
        }
        
        .question-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .nav-btn {
            width: 70px;
            height: 70px;
            border-radius: 12px;
            border: 2px solid #e0e0e0;
            background: var(--white);
            color: var(--text-dark);
            font-weight: 700;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        
        .nav-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .nav-btn.current {
            background: linear-gradient(135deg, var(--primary-red), var(--light-red));
            color: var(--white);
            border-color: var(--primary-red);
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(198, 40, 40, 0.3);
        }
        
        .nav-btn.answered {
            background: linear-gradient(135deg, #4CAF50, #66bb6a);
            color: var(--white);
            border-color: #4CAF50;
        }
        
        .nav-btn.marked {
            background: linear-gradient(135deg, #ff9800, #ffb74d);
            color: var(--white);
            border-color: #ff9800;
        }
        
        .nav-btn.empty {
            background: #f5f5f5;
            color: #999;
            border-color: #ddd;
        }
        
        .nav-btn-mark {
            position: absolute;
            top: 5px;
            right: 5px;
            background: var(--warning);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
        }
        
        .status-indicator {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid var(--bg-light);
        }
        
        .status-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
            color: var(--text-dark);
        }
        
        .status-color {
            width: 25px;
            height: 25px;
            border-radius: 6px;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .color-current { background: linear-gradient(135deg, var(--primary-red), var(--light-red)); }
        .color-answered { background: linear-gradient(135deg, #4CAF50, #66bb6a); }
        .color-marked { background: linear-gradient(135deg, #ff9800, #ffb74d); }
        .color-not-answered { background: #f5f5f5; }
        
        .warning-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3000;
            backdrop-filter: blur(5px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .warning-modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .warning-content {
            background: var(--white);
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            border: 5px solid var(--light-red);
            transform: translateY(20px);
            transition: transform 0.3s;
        }
        
        .warning-modal.active .warning-content {
            transform: translateY(0);
        }
        
        .warning-icon {
            font-size: 4rem;
            color: var(--light-red);
            margin-bottom: 20px;
        }
        
        .warning-title {
            color: var(--dark-red);
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        
        .warning-text {
            color: var(--text-dark);
            margin-bottom: 30px;
            line-height: 1.6;
            font-size: 1.05rem;
        }
        
        .warning-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .btn-warning {
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            border: none;
            transition: all 0.3s;
            min-width: 120px;
        }
        
        .btn-warning-cancel {
            background: #e0e0e0;
            color: var(--text-dark);
        }
        
        .btn-warning-cancel:hover {
            background: #bdbdbd;
            transform: translateY(-2px);
        }
        
        .btn-warning-confirm {
            background: linear-gradient(135deg, var(--primary-red), var(--light-red));
            color: var(--white);
        }
        
        .btn-warning-confirm:hover {
            background: linear-gradient(135deg, var(--dark-red), var(--primary-red));
            transform: translateY(-2px);
        }
        
        .mobile-nav-btn {
            display: none;
        }
        
        @media (max-width: 992px) {
            .main-container {
                flex-direction: column;
            }
            
            .question-container {
                min-height: auto;
                margin-bottom: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-exam {
                width: 100%;
            }
            
            .mobile-nav-btn {
                display: flex;
                margin: 20px auto;
                width: 100%;
                max-width: 300px;
            }
        }
        
        @media (max-width: 768px) {
            .exam-title {
                font-size: 1.2rem;
                text-align: center;
                margin-bottom: 15px;
            }
            
            .user-info {
                text-align: center;
                margin-bottom: 15px;
            }
            
            .timer-display {
                font-size: 1.8rem;
            }
            
            .question-container {
                padding: 20px;
            }
            
            .question-text {
                font-size: 1.1rem;
            }
            
            .question-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-btn-container {
                align-self: flex-end;
            }
            
            .option-item {
                padding: 15px;
            }
            
            .question-grid {
                grid-template-columns: repeat(auto-fill, minmax(55px, 1fr));
            }
            
            .nav-btn {
                width: 55px;
                height: 55px;
                font-size: 1rem;
            }
            
            .warning-content {
                padding: 30px 20px;
            }
        }
        
        .progress-container {
            margin-top: 20px;
            background: var(--white);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #e0e0e0;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: var(--text-dark);
            font-weight: 600;
        }
        
        .progress-bar {
            height: 12px;
            background: #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--light-red), var(--primary-red));
            border-radius: 6px;
            transition: width 0.5s ease;
        }
        
        .progress-count {
            font-weight: 700;
            color: var(--primary-red);
        }
        
        @media (max-width: 576px) {
            .timer-container {
                padding: 12px 20px;
            }
            
            .timer-display {
                font-size: 1.5rem;
            }
            
            .question-grid {
                grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
                gap: 10px;
            }
            
            .nav-btn {
                width: 50px;
                height: 50px;
            }
            
            .btn-quick-nav {
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
            }
            
            .nav-badge {
                width: 22px;
                height: 22px;
                font-size: 0.7rem;
                top: -6px;
                right: -6px;
            }
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .loading-spinner {
            width: 80px;
            height: 80px;
            border: 8px solid var(--bg-light);
            border-top: 8px solid var(--primary-red);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Header -->
    <div class="exam-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h1 class="exam-title">
                        <i class="fas fa-graduation-cap"></i>
                        UJIAN ONLINE PMB
                    </h1>
                </div>
                <div class="col-md-4">
                    <div class="user-info">
                        <p><i class="fas fa-user me-2"></i><?= htmlspecialchars($camaba['nama_lengkap']) ?></p>
                        <p><i class="fas fa-book me-2"></i><?= htmlspecialchars($kategori) ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="timer-container">
                        <div class="timer-label">SISA WAKTU</div>
                        <div class="timer-display" id="timer">--:--:--</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Progress Bar -->
        <div class="progress-container">
            <div class="progress-label">
                <span>Progress Pengerjaan</span>
                <span id="progress-text">0/<?= $total_soal ?></span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
            </div>
            <div class="progress-count mt-2 text-center">
                <span id="answered-count">0</span> dari <?= $total_soal ?> soal terjawab
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-container">
            <div class="question-container">
                <!-- Header dengan nomor soal dan tombol navigasi -->
                <div class="question-header">
                    <h2 class="question-number" id="question-number">Soal #1 dari <?= $total_soal ?></h2>
                    
                    <div class="nav-btn-container">
                        <button class="btn-quick-nav" onclick="showNavigation()" title="Navigasi Soal">
                            <i class="fas fa-list-ol"></i>
                            <span class="nav-badge" id="nav-badge"><?= $total_soal ?></span>
                        </button>
                    </div>
                </div>
                
                <div class="question-content">
                    <div id="soal"></div>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-exam btn-prev" onclick="prev()">
                        <i class="fas fa-arrow-left"></i> Soal Sebelumnya
                    </button>
                    
                    <button class="btn btn-exam btn-mark" id="mark-button" onclick="toggleMark()">
                        <i class="fas fa-flag"></i> Tandai Soal
                    </button>
                    
                    <button class="btn btn-exam btn-next" onclick="next()">
                        Soal Berikutnya <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
                
                <button class="btn btn-submit" onclick="showSubmitConfirmation()">
                    <i class="fas fa-paper-plane"></i> Selesaikan Ujian
                </button>
                
                <!-- Mobile Navigation Button -->
                <button class="btn btn-nav mobile-nav-btn" onclick="showNavigation()">
                    <i class="fas fa-list-ol"></i> Lihat Navigasi Soal
                </button>
            </div>
        </div>
    </div>

    <!-- Navigation Modal -->
    <div class="nav-modal-overlay" id="nav-modal">
        <div class="nav-modal">
            <div class="nav-modal-header">
                <h3 class="nav-title">
                    <i class="fas fa-list-ol me-2"></i>Navigasi Soal
                    <span class="ms-2" style="color: var(--text-light); font-size: 1rem;">
                        (Total: <?= $total_soal ?> soal)
                    </span>
                </h3>
                <button class="nav-close" onclick="hideNavigation()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="question-grid" id="nav-grid"></div>
            
            <div class="status-indicator">
                <div class="status-item">
                    <div class="status-color color-current"></div>
                    <span>Sedang dikerjakan</span>
                </div>
                <div class="status-item">
                    <div class="status-color color-answered"></div>
                    <span>Sudah dijawab</span>
                </div>
                <div class="status-item">
                    <div class="status-color color-marked"></div>
                    <span>Ditandai</span>
                </div>
                <div class="status-item">
                    <div class="status-color color-not-answered"></div>
                    <span>Belum dijawab</span>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <button class="btn btn-nav" onclick="hideNavigation()">
                    <i class="fas fa-check me-2"></i> Kembali ke Soal
                </button>
            </div>
        </div>
    </div>

    <!-- Warning Modal -->
    <div class="warning-modal" id="warning-modal">
        <div class="warning-content">
            <div class="warning-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="warning-title">Konfirmasi Pengumpulan</h3>
            <p class="warning-text" id="warning-text">
                Apakah Anda yakin ingin mengumpulkan jawaban? Pastikan Anda telah memeriksa semua soal sebelum mengumpulkan. Tindakan ini tidak dapat dibatalkan.
            </p>
            <div class="warning-actions">
                <button class="btn-warning btn-warning-cancel" onclick="hideSubmitConfirmation()">
                    <i class="fas fa-times me-2"></i> Batal
                </button>
                <button class="btn-warning btn-warning-confirm" onclick="submitUjian()">
                    <i class="fas fa-check me-2"></i> Ya, Kumpulkan
                </button>
            </div>
        </div>
    </div>

    <script>
        // Data dari PHP
        const soal = <?= json_encode($soal) ?>;
        const totalSoal = <?= $total_soal ?>;
        const nomorTes = '<?= $nomor_tes ?>';
        const ujianId = '<?= $ujian['id'] ?>';
        const BASE_IMG = '/admin/uploads/soal/';
        
        // State
        let jawaban = {};
        let ditandai = {};
        let index = 0;
        let sisaWaktu = <?= $sisa_waktu ?>;
        let timerInterval;
        let isSubmitting = false;
        let lastSyncTime = Date.now();

        // Load jawaban dari localStorage
        function loadJawaban() {
            const saved = localStorage.getItem(`jawaban_${nomorTes}_${ujianId}`);
            if (saved) {
                try {
                    const parsed = JSON.parse(saved);
                    jawaban = parsed;
                } catch (e) {
                    console.error('Error loading jawaban:', e);
                }
            }
        }

        // Simpan jawaban ke localStorage
        function saveJawaban() {
            localStorage.setItem(`jawaban_${nomorTes}_${ujianId}`, JSON.stringify(jawaban));
            updateProgress();
            renderNavigationGrid();
            
            // Debounce sync ke server
            clearTimeout(window.syncTimeout);
            window.syncTimeout = setTimeout(syncToServer, 2000);
        }

        // Load soal yang ditandai
        function loadMarked() {
            const saved = localStorage.getItem(`marked_${nomorTes}_${ujianId}`);
            if (saved) {
                try {
                    const parsed = JSON.parse(saved);
                    ditandai = parsed;
                } catch (e) {
                    console.error('Error loading marked:', e);
                }
            }
        }

        // Simpan soal yang ditandai
        function saveMarked() {
            localStorage.setItem(`marked_${nomorTes}_${ujianId}`, JSON.stringify(ditandai));
            renderNavigationGrid();
            updateMarkButton();
            
            // Debounce sync ke server
            clearTimeout(window.syncTimeout);
            window.syncTimeout = setTimeout(syncToServer, 2000);
        }

        // Sync ke server
        async function syncToServer() {
            try {
                const response = await fetch('sync_ujian.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        jawaban: jawaban,
                        ditandai: ditandai,
                        nomor_tes: nomorTes,
                        ujian_id: ujianId
                    })
                });
                
                if (!response.ok) {
                    throw new Error('Sync failed');
                }
            } catch (error) {
                console.error('Sync error:', error);
            }
        }

        // Update timer dari server
        async function syncTime() {
            try {
                const response = await fetch(`get_waktu.php?nomor_tes=${nomorTes}&ujian_id=${ujianId}`);
                const data = await response.json();
                
                if (data.waktu !== undefined) {
                    sisaWaktu = data.waktu;
                    updateTimerDisplay();
                    
                    if (sisaWaktu <= 0) {
                        submitUjian();
                    }
                }
            } catch (error) {
                console.error('Time sync error:', error);
            }
        }

        function updateTimerDisplay() {
            let hours = Math.floor(sisaWaktu / 3600);
            let minutes = Math.floor((sisaWaktu % 3600) / 60);
            let seconds = sisaWaktu % 60;
            
            let timerDisplay = document.getElementById('timer');
            timerDisplay.textContent = 
                `${hours.toString().padStart(2,'0')}:` +
                `${minutes.toString().padStart(2,'0')}:` +
                `${seconds.toString().padStart(2,'0')}`;
            
            // Add warning class when less than 5 minutes
            timerDisplay.classList.remove('timer-warning', 'timer-danger');
            if (sisaWaktu <= 300 && sisaWaktu > 60) {
                timerDisplay.classList.add('timer-warning');
            } else if (sisaWaktu <= 60) {
                timerDisplay.classList.add('timer-danger');
            }
        }

        function updateTimer() {
            if (sisaWaktu > 0) {
                sisaWaktu--;
                updateTimerDisplay();
            }
            
            if (sisaWaktu <= 0) {
                clearInterval(timerInterval);
                submitUjian();
            }
            
            // Sync waktu ke server setiap 30 detik
            if (Date.now() - lastSyncTime > 30000) {
                syncTime();
                lastSyncTime = Date.now();
            }
        }

        function updateProgress() {
            let answeredCount = Object.keys(jawaban).length;
            let progress = (answeredCount / soal.length) * 100;
            document.getElementById('progress-text').textContent = `${answeredCount}/${soal.length}`;
            document.getElementById('answered-count').textContent = answeredCount;
            document.getElementById('progress-fill').style.width = `${progress}%`;
            
            let unansweredCount = soal.length - answeredCount;
            let badge = document.getElementById('nav-badge');
            badge.textContent = unansweredCount > 0 ? unansweredCount : '✓';
            
            if (unansweredCount === 0) {
                badge.style.background = 'var(--success)';
            } else if (unansweredCount <= 5) {
                badge.style.background = 'var(--warning)';
            } else {
                badge.style.background = 'var(--light-red)';
            }
        }

        function updateMarkButton() {
            const markButton = document.getElementById('mark-button');
            if (soal.length > 0 && ditandai[soal[index]?.id]) {
                markButton.classList.add('marked');
                markButton.innerHTML = '<i class="fas fa-flag-checkered"></i> Hapus Tanda';
            } else {
                markButton.classList.remove('marked');
                markButton.innerHTML = '<i class="fas fa-flag"></i> Tandai Soal';
            }
        }

        function render() {
            if (!soal || soal.length === 0) {
                document.getElementById('soal').innerHTML = '<div class="alert alert-info">Tidak ada soal tersedia.</div>';
                return;
            }

            let s = soal[index];
            if (!s) return;

            // Update question number
            document.getElementById('question-number').textContent = `Soal #${index + 1} dari ${soal.length}`;

            let html = '';

            // ====== PERTANYAAN ======
            html += `<div class="question-text">${s.pertanyaan || ''}</div>`;

            if (s.pertanyaan_gambar) {
                html += `
                <div class="question-image-container">
                    <img src="${BASE_IMG + s.pertanyaan_gambar}" 
                         class="question-image" 
                         alt="Gambar Soal"
                         onerror="this.src='https://via.placeholder.com/800x400/ffebee/c62828?text=Gambar+Tidak+Tersedia'"
                         onclick="this.classList.toggle('zoomed')">
                    <div class="text-muted mt-2" style="font-size: 0.9rem;">
                        <i class="fas fa-search-plus me-1"></i> Klik gambar untuk memperbesar
                    </div>
                </div>`;
            }

            // ====== OPSI ======
            html += `<div class="options-container">`;
            
            ['a','b','c','d'].forEach(o => {
                let checked = jawaban[s.id] === o ? 'checked' : '';
                let imgField = 'opsi_' + o + '_gambar';
                let optionText = s['opsi_' + o] || '';
                
                let optionClass = 'option-item';
                if (jawaban[s.id] === o) {
                    optionClass += ' selected';
                }
                
                html += `
                <div class="${optionClass}" onclick="selectOption('${s.id}', '${o}')">
                    <div class="option-radio">
                        <input type="radio" name="opsi_${s.id}" value="${o}" ${checked}>
                    </div>
                    <div class="option-content">
                        <div class="option-text">
                            <span class="option-label">${o.toUpperCase()}.</span>
                            ${optionText}
                        </div>`;
                
                if (s[imgField]) {
                    html += `
                        <div class="option-image-container">
                            <img src="${BASE_IMG + s[imgField]}" 
                                 class="option-image" 
                                 alt="Gambar Opsi ${o.toUpperCase()}"
                                 onerror="this.src='https://via.placeholder.com/600x300/ffebee/c62828?text=Gambar+Tidak+Tersedia'"
                                 onclick="event.stopPropagation(); this.classList.toggle('zoomed')">
                        </div>`;
                }
                
                html += `</div></div>`;
            });
            
            html += `</div>`;

            document.getElementById('soal').innerHTML = html;
            renderNavigationGrid();
            updateProgress();
            updateMarkButton();
        }

        function selectOption(soalId, opsi) {
            jawaban[soalId] = opsi;
            saveJawaban();
            render();
        }

        function renderNavigationGrid() {
            if (!soal || soal.length === 0) return;
            
            let navHtml = '';
            soal.forEach((s, i) => {
                if (!s) return;
                
                let cls = 'nav-btn';
                if (i === index) cls += ' current';
                else if (ditandai[s.id]) cls += ' marked';
                else if (jawaban[s.id]) cls += ' answered';
                else cls += ' empty';
                
                navHtml += `
                <button class="${cls}" onclick="goToQuestion(${i})" title="Soal ${i + 1}">
                    ${i + 1}
                    ${ditandai[s.id] ? '<span class="nav-btn-mark"><i class="fas fa-flag"></i></span>' : ''}
                </button>`;
            });
            document.getElementById('nav-grid').innerHTML = navHtml;
        }

        function goToQuestion(i) { 
            if (i >= 0 && i < soal.length) {
                index = i; 
                render(); 
                hideNavigation();
            }
        }
        
        function next() { 
            if (index < soal.length - 1) {
                index++; 
                render();
            }
        }
        
        function prev() { 
            if (index > 0) {
                index--; 
                render();
            }
        }
        
        function toggleMark() { 
            if (soal.length > 0 && soal[index]) {
                ditandai[soal[index].id] = !ditandai[soal[index].id]; 
                saveMarked();
            }
        }

        function showNavigation() {
            document.getElementById('nav-modal').classList.add('active');
            setTimeout(() => {
                document.querySelector('.nav-modal').classList.add('active');
            }, 10);
        }

        function hideNavigation() {
            document.querySelector('.nav-modal').classList.remove('active');
            setTimeout(() => {
                document.getElementById('nav-modal').classList.remove('active');
            }, 300);
        }

        function showSubmitConfirmation() {
            let unanswered = soal.length - Object.keys(jawaban).length;
            let marked = Object.keys(ditandai).filter(id => ditandai[id]).length;
            let warningText = "Apakah Anda yakin ingin mengumpulkan jawaban? ";
            
            if (unanswered > 0) {
                warningText += `<br><br><strong>Peringatan:</strong> Masih ada <span style="color: #c62828; font-weight: 700;">${unanswered} soal</span> yang belum terjawab. `;
            }
            
            if (marked > 0) {
                warningText += `<br>Ada <span style="color: #ff9800; font-weight: 700;">${marked} soal</span> yang ditandai untuk diperiksa ulang.`;
            }
            
            warningText += "<br><br>Pastikan Anda telah memeriksa semua soal sebelum mengumpulkan. Tindakan ini tidak dapat dibatalkan.";
            
            document.getElementById('warning-text').innerHTML = warningText;
            document.getElementById('warning-modal').classList.add('active');
            setTimeout(() => {
                document.querySelector('.warning-content').classList.add('active');
            }, 10);
        }

        function hideSubmitConfirmation() {
            document.querySelector('.warning-content').classList.remove('active');
            setTimeout(() => {
                document.getElementById('warning-modal').classList.remove('active');
            }, 300);
        }

        async function submitUjian() {
            if (isSubmitting) return;
            isSubmitting = true;
            
            clearInterval(timerInterval);
            hideSubmitConfirmation();
            
            document.getElementById('loading-overlay').classList.add('active');
            
            try {
                const response = await fetch('submit_ujian.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        jawaban: jawaban,
                        waktu_sisa: sisaWaktu,
                        ditandai: ditandai,
                        nomor_tes: nomorTes,
                        ujian_id: ujianId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Hapus data localStorage
                    localStorage.removeItem(`jawaban_${nomorTes}_${ujianId}`);
                    localStorage.removeItem(`marked_${nomorTes}_${ujianId}`);
                    
                    // Redirect
                    window.location.href = result.redirect || '/index.php';
                } else {
                    alert(result.message || 'Terjadi kesalahan saat mengumpulkan jawaban.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengumpulkan jawaban. Silakan coba lagi.');
            } finally {
                document.getElementById('loading-overlay').classList.remove('active');
                isSubmitting = false;
            }
        }

        // Prevent accidental refresh or closing
        window.addEventListener('beforeunload', function (e) {
            if (sisaWaktu > 0 && Object.keys(jawaban).length > 0) {
                e.preventDefault();
                e.returnValue = 'Anda sedang mengerjakan ujian. Apakah Anda yakin ingin meninggalkan halaman ini?';
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Arrow keys for navigation
            if (e.key === 'ArrowRight') {
                e.preventDefault();
                next();
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                prev();
            } else if (e.key === 'm' || e.key === 'M') {
                e.preventDefault();
                toggleMark();
            } else if (e.key === 'n' || e.key === 'N') {
                e.preventDefault();
                showNavigation();
            } else if (e.key === 'Escape') {
                hideNavigation();
                hideSubmitConfirmation();
            }
            
            // Number keys for quick navigation (1-9)
            if (e.key >= '1' && e.key <= '9') {
                let num = parseInt(e.key);
                if (num <= soal.length) {
                    goToQuestion(num - 1);
                }
            }
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadJawaban();
            loadMarked();
            render();
            updateTimerDisplay();
            
            timerInterval = setInterval(updateTimer, 1000);
            
            // Sync waktu dari server
            syncTime();
            setInterval(syncTime, 60000); // Sync setiap 1 menit
            
            // Close modals when clicking outside
            document.getElementById('nav-modal').addEventListener('click', function(e) {
                if (e.target === this) {
                    hideNavigation();
                }
            });
            
            document.getElementById('warning-modal').addEventListener('click', function(e) {
                if (e.target === this) {
                    hideSubmitConfirmation();
                }
            });
            
            // Simpan jawaban sebelum meninggalkan halaman
            window.addEventListener('pagehide', function() {
                syncToServer();
            });
        });
    </script>
</body>
</html>