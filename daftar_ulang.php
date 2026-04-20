<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once 'config/database.php';

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

// Cek apakah status sudah sesuai untuk daftar ulang
if ($userData['status'] != 'daftar_ulang') {
    header("Location: ../index.php");
    exit;
}

// Cek apakah sudah pernah mengisi daftar ulang
$isComplete = $userData['is_daftar_ulang_complete'] ?? 0;

// Jika sudah lengkap, langsung arahkan ke halaman pembayaran
if ($isComplete == 1) {
    header("Location: pembayaran.php");
    exit;
}

// Fungsi untuk generate NIM dengan format: KODE PRODI + TAHUN + NOMOR URUT
function generateNIM($conn, $prodi_pilihan) {
    $kode_prodi = [
        'Teknik Informatika' => 'TI',
        'Sistem Informasi' => 'SI',
        'Manajemen' => 'MN',
        'Akuntansi' => 'AK',
        'Ilmu Komunikasi' => 'IK',
        'Desain Komunikasi Visual' => 'DKV'
    ];
    
    $kode = $kode_prodi[$prodi_pilihan] ?? 'XX';
    $tahun = date('Y');
    
    $query = "SELECT nim FROM camaba WHERE nim LIKE ? ORDER BY nim DESC LIMIT 1";
    $like_pattern = $kode . $tahun . '%';
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $like_pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $lastNIM = $result->fetch_assoc();
    
    if ($lastNIM && !empty($lastNIM['nim'])) {
        $last_number = (int)substr($lastNIM['nim'], -4);
        $new_number = str_pad($last_number + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $new_number = '0001';
    }
    
    return $kode . $tahun . $new_number;
}

// Proses simpan data daftar ulang
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $nama_ayah = trim($_POST['nama_ayah'] ?? '');
    $nama_ibu = trim($_POST['nama_ibu'] ?? '');
    $pekerjaan_ayah = trim($_POST['pekerjaan_ayah'] ?? '');
    $pekerjaan_ibu = trim($_POST['pekerjaan_ibu'] ?? '');
    $penghasilan_ayah = $_POST['penghasilan_ayah'] ?? '';
    $penghasilan_ibu = $_POST['penghasilan_ibu'] ?? '';
    $alamat_rumah = trim($_POST['alamat_rumah'] ?? '');
    $no_kk = trim($_POST['no_kk'] ?? '');
    
    if (empty($nama_ayah)) {
        $error = "Nama ayah wajib diisi.";
    } elseif (empty($nama_ibu)) {
        $error = "Nama ibu wajib diisi.";
    } elseif (empty($pekerjaan_ayah)) {
        $error = "Pekerjaan ayah wajib diisi.";
    } elseif (empty($pekerjaan_ibu)) {
        $error = "Pekerjaan ibu wajib diisi.";
    } elseif (empty($penghasilan_ayah)) {
        $error = "Penghasilan ayah wajib dipilih.";
    } elseif (empty($penghasilan_ibu)) {
        $error = "Penghasilan ibu wajib dipilih.";
    } elseif (empty($alamat_rumah)) {
        $error = "Alamat rumah wajib diisi.";
    } elseif (empty($no_kk)) {
        $error = "Nomor Kartu Keluarga (KK) wajib diisi.";
    }
    
    $upload_dir = '../uploads/dokumen/' . $userData['nomor_tes'] . '/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_kk = $userData['file_kk'] ?? '';
    if (empty($error) && isset($_FILES['file_kk']) && $_FILES['file_kk']['error'] == 0) {
        $result = uploadFile($_FILES['file_kk'], $upload_dir, 'kk');
        if ($result === false) {
            $error = "Gagal upload file KK. Pastikan format file JPG/PNG/PDF dan ukuran maksimal 2MB.";
        } else {
            $file_kk = $result;
        }
    } elseif (empty($userData['file_kk']) && empty($error)) {
        $error = "File Kartu Keluarga (KK) wajib diupload.";
    }
    
    $file_ktp = $userData['file_ktp'] ?? '';
    if (empty($error) && isset($_FILES['file_ktp']) && $_FILES['file_ktp']['error'] == 0) {
        $result = uploadFile($_FILES['file_ktp'], $upload_dir, 'ktp');
        if ($result === false) {
            $error = "Gagal upload file KTP. Pastikan format file JPG/PNG/PDF dan ukuran maksimal 2MB.";
        } else {
            $file_ktp = $result;
        }
    } elseif (empty($userData['file_ktp']) && empty($error)) {
        $error = "File KTP wajib diupload.";
    }
    
    $file_ijazah = $userData['file_ijazah'] ?? '';
    if (empty($error) && isset($_FILES['file_ijazah']) && $_FILES['file_ijazah']['error'] == 0) {
        $result = uploadFile($_FILES['file_ijazah'], $upload_dir, 'ijazah');
        if ($result === false) {
            $error = "Gagal upload file Ijazah/SKL. Pastikan format file JPG/PNG/PDF dan ukuran maksimal 2MB.";
        } else {
            $file_ijazah = $result;
        }
    } elseif (empty($userData['file_ijazah']) && empty($error)) {
        $error = "File Ijazah/SKL wajib diupload.";
    }
    
    if (empty($error)) {
        $nim_baru = generateNIM($conn, $userData['prodi_pilihan']);
        
        $updateQuery = "UPDATE camaba SET 
            nama_ayah = ?, nama_ibu = ?, 
            pekerjaan_ayah = ?, pekerjaan_ibu = ?,
            penghasilan_ayah = ?, penghasilan_ibu = ?,
            alamat_rumah = ?, no_kk = ?,
            file_kk = ?, file_ktp = ?, file_ijazah = ?,
            nim = ?,
            is_daftar_ulang_complete = 1,
            tanggal_daftar_ulang = NOW()
            WHERE id = ?";
        
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ssssssssssssi", 
            $nama_ayah, $nama_ibu, 
            $pekerjaan_ayah, $pekerjaan_ibu,
            $penghasilan_ayah, $penghasilan_ibu,
            $alamat_rumah, $no_kk,
            $file_kk, $file_ktp, $file_ijazah,
            $nim_baru,
            $camaba_id
        );
        
        if ($updateStmt->execute()) {
            header("Location: pembayaran.php");
            exit;
        } else {
            $error = "Gagal menyimpan data. Error: " . $conn->error;
        }
    }
}

function uploadFile($file, $upload_dir, $prefix) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    $file_type = $file['type'];
    $file_size = $file['size'];
    $max_size = 2 * 1024 * 1024;
    
    if (!in_array($file_type, $allowed_types)) {
        return false;
    }
    
    if ($file_size > $max_size) {
        return false;
    }
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = $prefix . '_' . date('YmdHis') . '_' . rand(1000, 9999) . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return $new_filename;
    }
    
    return false;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Ulang - PMB Universitas Kita 2026</title>
    
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
        
        /* CARD */
        .card-custom {
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
        .card-header-custom {
            background: linear-gradient(135deg, var(--dark-red), var(--primary-red));
            color: var(--white);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .card-header-custom::before {
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
        
        .header-icon {
            font-size: 3.5rem;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }
        
        .card-header-custom h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .card-header-custom p {
            font-size: 0.95rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        /* BODY */
        .card-body-custom {
            padding: 40px;
        }
        
        /* SECTION */
        .section-custom {
            background: var(--bg-light);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid rgba(198, 40, 40, 0.2);
            transition: all 0.3s;
        }
        
        .section-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(198, 40, 40, 0.1);
        }
        
        .section-title {
            color: var(--dark-red);
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid var(--primary-red);
            padding-left: 15px;
        }
        
        .section-title i {
            color: var(--primary-red);
            font-size: 1.3rem;
        }
        
        /* FORM GRID */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 5px;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.85rem;
        }
        
        .form-label.required::after {
            content: ' *';
            color: var(--primary-red);
        }
        
        .form-control-custom,
        .form-select-custom {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ffcdd2;
            border-radius: 10px;
            font-size: 0.9rem;
            transition: all 0.3s;
            background: var(--white);
        }
        
        .form-control-custom:focus,
        .form-select-custom:focus {
            outline: none;
            border-color: var(--light-red);
            box-shadow: 0 0 0 3px rgba(255, 82, 82, 0.2);
        }
        
        .form-control-custom:disabled {
            background: #f5f5f5;
            cursor: not-allowed;
        }
        
        textarea.form-control-custom {
            resize: vertical;
            min-height: 80px;
        }
        
        /* FILE INPUT */
        .file-input-wrapper {
            position: relative;
            margin-top: 5px;
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            z-index: 2;
        }
        
        .file-label-custom {
            display: block;
            padding: 12px 15px;
            border: 2px dashed #ffcdd2;
            border-radius: 10px;
            text-align: center;
            background: var(--white);
            cursor: pointer;
            transition: all 0.3s;
            color: var(--text-light);
        }
        
        .file-label-custom:hover {
            border-color: var(--light-red);
            background: var(--bg-light);
        }
        
        .file-label-custom i {
            margin-right: 8px;
            color: var(--primary-red);
        }
        
        .file-name {
            font-size: 0.75rem;
            color: var(--text-light);
            margin-top: 5px;
            word-break: break-all;
        }
        
        .file-name i {
            color: var(--success-green);
            margin-right: 4px;
        }
        
        /* INFO TEXT */
        .info-text {
            font-size: 0.75rem;
            color: var(--text-light);
            margin-top: 8px;
        }
        
        .info-text i {
            margin-right: 5px;
            font-size: 0.7rem;
        }
        
        /* ALERT */
        .alert-custom {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-danger-custom {
            background: #ffebee;
            color: var(--primary-red);
            border-left: 4px solid var(--primary-red);
        }
        
        .alert-success-custom {
            background: #e8f5e9;
            color: var(--success-green);
            border-left: 4px solid var(--success-green);
        }
        
        /* BUTTON */
        .btn-submit-custom {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--light-red), var(--primary-red));
            color: var(--white);
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-submit-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(198, 40, 40, 0.4);
            background: linear-gradient(135deg, var(--primary-red), var(--dark-red));
        }
        
        .btn-submit-custom:active {
            transform: translateY(-1px);
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
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .card-header-custom {
                padding: 30px 20px;
            }
            
            .card-header-custom h1 {
                font-size: 1.5rem;
            }
            
            .card-body-custom {
                padding: 25px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
            
            .section-title {
                font-size: 1rem;
            }
        }
        
        /* ANIMATIONS */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card-custom fade-in">
            <div class="card-header-custom">
                <div class="header-icon">
                    <i class="fas fa-file-signature"></i>
                </div>
                <h1>Formulir Daftar Ulang</h1>
                <p>Lengkapi data diri dan upload dokumen persyaratan untuk melanjutkan ke tahap pembayaran</p>
            </div>
            
            <div class="card-body-custom">
                <?php if ($error): ?>
                    <div class="alert-custom alert-danger-custom">
                        <i class="fas fa-exclamation-circle fa-lg"></i>
                        <div><strong>Error!</strong> <?php echo $error; ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <!-- Data Diri -->
                    <div class="section-custom">
                        <div class="section-title">
                            <i class="fas fa-user-graduate"></i>
                            <span>Data Diri</span>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control-custom" value="<?php echo htmlspecialchars($userData['nama_lengkap']); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nomor Peserta Tes</label>
                                <input type="text" class="form-control-custom" value="<?php echo $userData['nomor_tes'] ?? '-'; ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Program Studi Pilihan</label>
                                <input type="text" class="form-control-custom" value="<?php echo htmlspecialchars($userData['prodi_pilihan'] ?? '-'); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="text" class="form-control-custom" value="<?php echo htmlspecialchars($userData['email'] ?? '-'); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label class="form-label">No. HP</label>
                                <input type="text" class="form-control-custom" value="<?php echo htmlspecialchars($userData['no_hp'] ?? '-'); ?>" disabled>
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label required">Alamat Rumah</label>
                                <textarea name="alamat_rumah" class="form-control-custom" placeholder="Masukkan alamat lengkap"><?php echo htmlspecialchars($userData['alamat_rumah'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Data Orang Tua -->
                    <div class="section-custom">
                        <div class="section-title">
                            <i class="fas fa-users"></i>
                            <span>Data Orang Tua</span>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label required">Nama Ayah Kandung</label>
                                <input type="text" name="nama_ayah" class="form-control-custom" value="<?php echo htmlspecialchars($userData['nama_ayah'] ?? ''); ?>" placeholder="Nama lengkap ayah">
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Nama Ibu Kandung</label>
                                <input type="text" name="nama_ibu" class="form-control-custom" value="<?php echo htmlspecialchars($userData['nama_ibu'] ?? ''); ?>" placeholder="Nama lengkap ibu">
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Pekerjaan Ayah</label>
                                <input type="text" name="pekerjaan_ayah" class="form-control-custom" value="<?php echo htmlspecialchars($userData['pekerjaan_ayah'] ?? ''); ?>" placeholder="Pekerjaan ayah">
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Pekerjaan Ibu</label>
                                <input type="text" name="pekerjaan_ibu" class="form-control-custom" value="<?php echo htmlspecialchars($userData['pekerjaan_ibu'] ?? ''); ?>" placeholder="Pekerjaan ibu">
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Penghasilan Ayah</label>
                                <select name="penghasilan_ayah" class="form-select-custom">
                                    <option value="">-- Pilih Penghasilan --</option>
                                    <option value="< 1.000.000" <?php echo ($userData['penghasilan_ayah'] ?? '') == '< 1.000.000' ? 'selected' : ''; ?>>Kurang dari Rp 1.000.000</option>
                                    <option value="1.000.000 - 2.500.000" <?php echo ($userData['penghasilan_ayah'] ?? '') == '1.000.000 - 2.500.000' ? 'selected' : ''; ?>>Rp 1.000.000 - Rp 2.500.000</option>
                                    <option value="2.500.000 - 5.000.000" <?php echo ($userData['penghasilan_ayah'] ?? '') == '2.500.000 - 5.000.000' ? 'selected' : ''; ?>>Rp 2.500.000 - Rp 5.000.000</option>
                                    <option value="5.000.000 - 10.000.000" <?php echo ($userData['penghasilan_ayah'] ?? '') == '5.000.000 - 10.000.000' ? 'selected' : ''; ?>>Rp 5.000.000 - Rp 10.000.000</option>
                                    <option value="> 10.000.000" <?php echo ($userData['penghasilan_ayah'] ?? '') == '> 10.000.000' ? 'selected' : ''; ?>>Lebih dari Rp 10.000.000</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Penghasilan Ibu</label>
                                <select name="penghasilan_ibu" class="form-select-custom">
                                    <option value="">-- Pilih Penghasilan --</option>
                                    <option value="< 1.000.000" <?php echo ($userData['penghasilan_ibu'] ?? '') == '< 1.000.000' ? 'selected' : ''; ?>>Kurang dari Rp 1.000.000</option>
                                    <option value="1.000.000 - 2.500.000" <?php echo ($userData['penghasilan_ibu'] ?? '') == '1.000.000 - 2.500.000' ? 'selected' : ''; ?>>Rp 1.000.000 - Rp 2.500.000</option>
                                    <option value="2.500.000 - 5.000.000" <?php echo ($userData['penghasilan_ibu'] ?? '') == '2.500.000 - 5.000.000' ? 'selected' : ''; ?>>Rp 2.500.000 - Rp 5.000.000</option>
                                    <option value="5.000.000 - 10.000.000" <?php echo ($userData['penghasilan_ibu'] ?? '') == '5.000.000 - 10.000.000' ? 'selected' : ''; ?>>Rp 5.000.000 - Rp 10.000.000</option>
                                    <option value="> 10.000.000" <?php echo ($userData['penghasilan_ibu'] ?? '') == '> 10.000.000' ? 'selected' : ''; ?>>Lebih dari Rp 10.000.000</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Dokumen Persyaratan -->
                    <div class="section-custom">
                        <div class="section-title">
                            <i class="fas fa-folder-open"></i>
                            <span>Dokumen Persyaratan</span>
                        </div>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label class="form-label required">Nomor Kartu Keluarga (KK)</label>
                                <input type="text" name="no_kk" class="form-control-custom" value="<?php echo htmlspecialchars($userData['no_kk'] ?? ''); ?>" placeholder="Contoh: 3273123412345678">
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Upload Kartu Keluarga (KK)</label>
                                <div class="file-input-wrapper">
                                    <div class="file-label-custom" onclick="document.getElementById('file_kk').click()">
                                        <i class="fas fa-upload"></i> Klik untuk upload file KK
                                    </div>
                                    <input type="file" id="file_kk" name="file_kk" accept=".jpg,.jpeg,.png,.pdf">
                                </div>
                                <div class="file-name" id="file_kk_name">
                                    <?php if ($userData['file_kk']): ?>
                                        <i class="fas fa-check-circle"></i> File terupload: <?php echo $userData['file_kk']; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Upload KTP</label>
                                <div class="file-input-wrapper">
                                    <div class="file-label-custom" onclick="document.getElementById('file_ktp').click()">
                                        <i class="fas fa-upload"></i> Klik untuk upload file KTP
                                    </div>
                                    <input type="file" id="file_ktp" name="file_ktp" accept=".jpg,.jpeg,.png,.pdf">
                                </div>
                                <div class="file-name" id="file_ktp_name">
                                    <?php if ($userData['file_ktp']): ?>
                                        <i class="fas fa-check-circle"></i> File terupload: <?php echo $userData['file_ktp']; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Upload Ijazah / SKL</label>
                                <div class="file-input-wrapper">
                                    <div class="file-label-custom" onclick="document.getElementById('file_ijazah').click()">
                                        <i class="fas fa-upload"></i> Klik untuk upload file Ijazah/SKL
                                    </div>
                                    <input type="file" id="file_ijazah" name="file_ijazah" accept=".jpg,.jpeg,.png,.pdf">
                                </div>
                                <div class="file-name" id="file_ijazah_name">
                                    <?php if ($userData['file_ijazah']): ?>
                                        <i class="fas fa-check-circle"></i> File terupload: <?php echo $userData['file_ijazah']; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="info-text">
                            <i class="fas fa-info-circle"></i> Format file: JPG, PNG, PDF (Maks. 2MB per file)
                        </div>
                    </div>

                    <button type="submit" name="submit" class="btn-submit-custom">
                        <i class="fas fa-save"></i> Simpan & Lanjut ke Pembayaran
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
        
        <div class="back-link">
            <a href="../index.php">
                <i class="fas fa-arrow-left"></i> Kembali ke Halaman Utama
            </a>
        </div>
    </div>

    <script>
        // Tampilkan nama file yang dipilih
        document.getElementById('file_kk').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || '';
            const nameDisplay = document.getElementById('file_kk_name');
            if (fileName) {
                nameDisplay.innerHTML = '<i class="fas fa-file"></i> File dipilih: ' + fileName;
            } else if ('<?php echo $userData['file_kk']; ?>') {
                nameDisplay.innerHTML = '<i class="fas fa-check-circle"></i> File terupload: <?php echo $userData['file_kk']; ?>';
            } else {
                nameDisplay.innerHTML = '';
            }
        });
        
        document.getElementById('file_ktp').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || '';
            const nameDisplay = document.getElementById('file_ktp_name');
            if (fileName) {
                nameDisplay.innerHTML = '<i class="fas fa-file"></i> File dipilih: ' + fileName;
            } else if ('<?php echo $userData['file_ktp']; ?>') {
                nameDisplay.innerHTML = '<i class="fas fa-check-circle"></i> File terupload: <?php echo $userData['file_ktp']; ?>';
            } else {
                nameDisplay.innerHTML = '';
            }
        });
        
        document.getElementById('file_ijazah').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || '';
            const nameDisplay = document.getElementById('file_ijazah_name');
            if (fileName) {
                nameDisplay.innerHTML = '<i class="fas fa-file"></i> File dipilih: ' + fileName;
            } else if ('<?php echo $userData['file_ijazah']; ?>') {
                nameDisplay.innerHTML = '<i class="fas fa-check-circle"></i> File terupload: <?php echo $userData['file_ijazah']; ?>';
            } else {
                nameDisplay.innerHTML = '';
            }
        });
        
        // Validasi input nomor KK hanya angka
        const noKKInput = document.querySelector('input[name="no_kk"]');
        if (noKKInput) {
            noKKInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 16) {
                    this.value = this.value.slice(0, 16);
                }
            });
        }
    </script>
</body>
</html>