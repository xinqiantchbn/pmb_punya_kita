<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once '../config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['camaba_id'])) {
    header("Location: ../gate/login.php");
    exit;
}

$camaba_id = $_SESSION['camaba_id'];

// Ambil data camaba
$query = "SELECT * FROM camaba WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $camaba_id);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

if (!$userData) {
    header("Location: ../gate/login.php");
    exit;
}

// Status user saat ini
$status = $userData['status'];
$isLulus = ($status == 'lulus');
$isGagal = ($status == 'gagal');

// Jika status bukan lulus atau gagal, redirect ke home
if (!$isLulus && !$isGagal) {
    header("Location: ../index.php");
    exit;
}

// Ambil nilai ujian dari tabel camaba
$nilai = $userData['nilai_ujian'] ?? 0;
$jawabanBenar = $userData['jawaban_benar'] ?? 0;
$jawabanSalah = $userData['jawaban_salah'] ?? 0;
$totalSoal = $jawabanBenar + $jawabanSalah;

// Hitung persentase
$persentase = $totalSoal > 0 ? round(($jawabanBenar / $totalSoal) * 100) : 0;

// Ambil semua nilai ujian untuk perhitungan rangking (dari tabel camaba)
$rankingQuery = "SELECT id, nilai_ujian FROM camaba WHERE status IN ('lulus', 'gagal', 'sudah_ujian') AND nilai_ujian IS NOT NULL ORDER BY nilai_ujian DESC";
$rankingResult = $conn->query($rankingQuery);
$rank = 1;
$userRank = null;
$totalParticipants = 0;

while ($row = $rankingResult->fetch_assoc()) {
    $totalParticipants++;
    if ($row['id'] == $camaba_id) {
        $userRank = $rank;
    }
    $rank++;
}

// Jika tidak menemukan rangking, set default
if ($userRank === null) {
    $userRank = $totalParticipants > 0 ? $totalParticipants : 1;
}

// Proses update status jika tombol ditekan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $newStatus = null;
    $redirectTo = null;
    
    if ($_POST['action'] == 'daftar_ulang' && $isLulus) {
        // Tombol "Lanjutkan ke Daftar Ulang" - ubah status jadi daftar_ulang
        $newStatus = 'daftar_ulang';
        $redirectTo = 'daftar_ulang.php';
    } 
    elseif ($_POST['action'] == 'kembali_beranda') {
        // Tombol "Kembali ke Beranda" - bedakan berdasarkan status
        if ($isLulus) {
            $newStatus = 'daftar_ulang';
            $redirectTo = '../index.php';
        } elseif ($isGagal) {
            $newStatus = 'not_selected';
            $redirectTo = '../index.php';
        }
    }
    
    // Execute update jika ada status baru
    if ($newStatus !== null) {
        $updateQuery = "UPDATE camaba SET status = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("si", $newStatus, $camaba_id);
        if ($updateStmt->execute()) {
            header("Location: " . $redirectTo);
            exit;
        } else {
            $error = "Gagal mengupdate status: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Seleksi - PMB Universitas Kita 2026</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* VARIABLES WARNA MERAH */
        :root {
            --primary-red: #c62828;
            --dark-red: #b71c1c;
            --light-red: #ff5252;
            --accent-red: #ff8a80;
            --bg-light: #fff5f5;
            --text-dark: #212121;
            --text-light: #757575;
            --white: #ffffff;
            --success-green: #2e7d32;
            --danger-red: #c62828;
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--dark-red), var(--primary-red));
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        /* RESULT CARD */
        .result-card {
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(183, 28, 28, 0.3);
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* HEADER */
        .result-header {
            padding: 50px 30px;
            text-align: center;
            color: var(--white);
            position: relative;
            overflow: hidden;
        }
        
        .result-header.lulus {
            background: linear-gradient(135deg, var(--dark-red), var(--primary-red));
        }
        
        .result-header.gagal {
            background: linear-gradient(135deg, #8b0000, var(--dark-red));
        }
        
        .result-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
            opacity: 0.3;
        }
        
        .status-icon {
            font-size: 80px;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
            animation: bounce 1s ease;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .result-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }
        
        .result-header p {
            font-size: 1rem;
            opacity: 0.95;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .status-badge {
            display: inline-block;
            padding: 10px 30px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 1;
        }
        
        /* CONTENT */
        .result-content {
            padding: 40px;
        }
        
        /* INFO CARD */
        .info-card {
            background: var(--bg-light);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid rgba(198, 40, 40, 0.2);
        }
        
        .info-card h3 {
            color: var(--dark-red);
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-card h3 i {
            color: var(--primary-red);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(198, 40, 40, 0.1);
        }
        
        .info-label {
            font-weight: 500;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .info-value {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.95rem;
        }
        
        /* SCORE CARD */
        .score-card {
            background: linear-gradient(135deg, #fff5f5, #ffffff);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            border: 2px solid rgba(198, 40, 40, 0.2);
            transition: all 0.3s;
        }
        
        .score-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(198, 40, 40, 0.15);
        }
        
        .score-card h3 {
            color: var(--dark-red);
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        
        .score-value {
            font-size: 72px;
            font-weight: 800;
            margin: 20px 0;
            background: linear-gradient(135deg, var(--dark-red), var(--light-red));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .score-details {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .score-detail {
            text-align: center;
            padding: 10px 20px;
            background: var(--bg-light);
            border-radius: 12px;
            min-width: 100px;
        }
        
        .score-detail .label {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-bottom: 5px;
        }
        
        .score-detail .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .score-detail .value.benar {
            color: var(--success-green);
        }
        
        .score-detail .value.salah {
            color: var(--danger-red);
        }
        
        /* RANKING CARD */
        .ranking-card {
            background: linear-gradient(135deg, var(--bg-light), #ffffff);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
            border: 2px solid rgba(198, 40, 40, 0.2);
        }
        
        .ranking-card h3 {
            color: var(--dark-red);
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 15px;
        }
        
        .ranking-number {
            font-size: 56px;
            font-weight: 800;
            color: var(--primary-red);
            margin: 15px 0;
            text-shadow: 2px 2px 4px rgba(198, 40, 40, 0.2);
        }
        
        .ranking-text {
            font-size: 1rem;
            color: var(--text-dark);
            font-weight: 500;
        }
        
        .total-peserta {
            margin-top: 10px;
            font-size: 0.85rem;
            color: var(--text-light);
        }
        
        /* BUTTONS */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn-custom {
            padding: 14px 32px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--light-red), var(--primary-red));
            color: var(--white);
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(198, 40, 40, 0.3);
            background: linear-gradient(135deg, var(--primary-red), var(--dark-red));
        }
        
        .btn-secondary-custom {
            background: #6c757d;
            color: var(--white);
        }
        
        .btn-secondary-custom:hover {
            background: #5a6268;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(108, 117, 125, 0.3);
        }
        
        .btn-danger-custom {
            background: linear-gradient(135deg, #b22222, var(--dark-red));
            color: var(--white);
        }
        
        .btn-danger-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(178, 34, 34, 0.3);
        }
        
        /* NOTE */
        .note {
            margin-top: 30px;
            padding: 20px;
            border-radius: 12px;
            font-size: 0.9rem;
        }
        
        .note-lulus {
            background: #e8f5e9;
            color: var(--success-green);
            border-left: 4px solid var(--success-green);
        }
        
        .note-gagal {
            background: #ffebee;
            color: var(--danger-red);
            border-left: 4px solid var(--danger-red);
        }
        
        .note ul {
            margin-top: 8px;
            padding-left: 20px;
        }
        
        .note li {
            margin-bottom: 5px;
        }
        
        /* BACK LINK */
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: var(--white);
            text-decoration: none;
            font-size: 0.9rem;
            opacity: 0.8;
            transition: opacity 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-link a:hover {
            opacity: 1;
            text-decoration: underline;
        }
        
        /* ERROR MESSAGE */
        .error-message {
            background: #ffebee;
            color: var(--danger-red);
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid var(--danger-red);
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .result-header h1 {
                font-size: 1.5rem;
            }
            
            .result-content {
                padding: 25px;
            }
            
            .score-value {
                font-size: 48px;
            }
            
            .score-details {
                gap: 15px;
            }
            
            .score-detail {
                padding: 8px 15px;
                min-width: 80px;
            }
            
            .score-detail .value {
                font-size: 1.2rem;
            }
            
            .ranking-number {
                font-size: 40px;
            }
            
            .btn-custom {
                padding: 10px 24px;
                font-size: 0.9rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="result-card">
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Error:</strong> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="result-header <?php echo $isLulus ? 'lulus' : 'gagal'; ?>">
                <div class="status-icon">
                    <?php echo $isLulus ? '<i class="fas fa-trophy"></i>' : '<i class="fas fa-sad-tear"></i>'; ?>
                </div>
                <h1>
                    <?php echo $isLulus ? 'Selamat! Anda Lulus Seleksi' : 'Mohon Maaf, Anda Belum Berhasil'; ?>
                </h1>
                <p>
                    <?php echo $isLulus 
                        ? 'Selamat atas pencapaian Anda! Silakan lanjutkan ke tahap daftar ulang.'
                        : 'Tetap semangat! Kesempatan masih terbuka di masa mendatang.'; 
                    ?>
                </p>
                <div class="status-badge">
                    <i class="<?php echo $isLulus ? 'fas fa-check-circle' : 'fas fa-times-circle'; ?> me-2"></i>
                    <?php echo $isLulus ? 'LULUS SELEKSI' : 'BELUM BERHASIL'; ?>
                </div>
            </div>

            <div class="result-content">
                <!-- Informasi Personal -->
                <div class="info-card">
                    <h3>
                        <i class="fas fa-user-graduate"></i>
                        Informasi Peserta
                    </h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Nama Lengkap</span>
                            <span class="info-value"><?php echo htmlspecialchars($userData['nama_lengkap']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Nomor Peserta Tes</span>
                            <span class="info-value"><?php echo $userData['nomor_tes'] ?? '-'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Program Studi Pilihan</span>
                            <span class="info-value"><?php echo htmlspecialchars($userData['prodi_pilihan'] ?? '-'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tanggal Ujian</span>
                            <span class="info-value"><?php echo $userData['tanggal_ujian'] ? date('d M Y H:i', strtotime($userData['tanggal_ujian'])) : '-'; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Nilai Ujian -->
                <div class="score-card">
                    <h3>
                        <i class="fas fa-chart-line"></i> Hasil Ujian
                    </h3>
                    <div class="score-value">
                        <?php echo $nilai; ?>
                        <span style="font-size: 24px; background: none; -webkit-text-fill-color: var(--text-light);">/ 100</span>
                    </div>
                    <div class="score-details">
                        <div class="score-detail">
                            <div class="label">
                                <i class="fas fa-check-circle"></i> Jawaban Benar
                            </div>
                            <div class="value benar"><?php echo $jawabanBenar; ?> soal</div>
                        </div>
                        <div class="score-detail">
                            <div class="label">
                                <i class="fas fa-times-circle"></i> Jawaban Salah
                            </div>
                            <div class="value salah"><?php echo $jawabanSalah; ?> soal</div>
                        </div>
                        <div class="score-detail">
                            <div class="label">
                                <i class="fas fa-list-ol"></i> Total Soal
                            </div>
                            <div class="value"><?php echo $totalSoal; ?> soal</div>
                        </div>
                        <div class="score-detail">
                            <div class="label">
                                <i class="fas fa-percent"></i> Persentase
                            </div>
                            <div class="value"><?php echo $persentase; ?>%</div>
                        </div>
                    </div>
                </div>

                <!-- Ranking -->
                <div class="ranking-card">
                    <h3>
                        <i class="fas fa-ranking-star"></i> Peringkat Seleksi
                    </h3>
                    <div class="ranking-number">
                        #<?php echo $userRank; ?>
                    </div>
                    <div class="ranking-text">
                        dari <?php echo $totalParticipants; ?> peserta seleksi
                    </div>
                    <div class="total-peserta">
                        <i class="fas fa-info-circle"></i> Berdasarkan hasil ujian seluruh peserta
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <?php if ($isLulus): ?>
                        <!-- Tombol untuk Lanjut ke Daftar Ulang -->
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="daftar_ulang">
                            <button type="submit" class="btn-custom btn-primary-custom">
                                <i class="fas fa-file-signature"></i> Lanjutkan ke Daftar Ulang
                            </button>
                        </form>
                        <!-- Tombol Kembali ke Beranda (untuk lulus juga bisa) -->
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="kembali_beranda">
                            <button type="submit" class="btn-custom btn-secondary-custom">
                                <i class="fas fa-home"></i> Kembali ke Beranda
                            </button>
                        </form>
                    <?php elseif ($isGagal): ?>
                        <!-- Tombol Kembali ke Beranda (untuk gagal) -->
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="kembali_beranda">
                            <button type="submit" class="btn-custom btn-danger-custom">
                                <i class="fas fa-home"></i> Kembali ke Beranda
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Catatan -->
                <?php if ($isLulus): ?>
                <div class="note note-lulus">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Informasi Penting:</strong>
                    <ul>
                        <li>Klik <strong>"Lanjutkan ke Daftar Ulang"</strong> untuk melanjutkan ke proses daftar ulang.</li>
                        <li>Klik <strong>"Kembali ke Beranda"</strong> jika ingin melihat informasi lain, status Anda akan berubah menjadi "Daftar Ulang".</li>
                    </ul>
                </div>
                <?php elseif ($isGagal): ?>
                <div class="note note-gagal">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Informasi:</strong> Klik tombol "Kembali ke Beranda" untuk kembali ke halaman utama. Status pendaftaran Anda akan diperbarui menjadi "Not Selected".
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="back-link">
            <a href="../index.php">
                <i class="fas fa-arrow-left"></i> Kembali ke Halaman Utama
            </a>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>