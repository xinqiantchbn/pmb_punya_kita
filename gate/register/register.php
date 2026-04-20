<?php
session_start();
require_once '../../config/database.php';

$error = '';
$success = '';

// Daftar prodi pilihan
$prodi_list = [
    'Teknik Informatika',
    'Sistem Informasi', 
    'Manajemen',
    'Akuntansi',
    'Ilmu Komunikasi',
    'Desain Komunikasi Visual',
];

// Daftar jenis kelamin (menggunakan L/P)
$gender_list = [
    'L' => 'Laki-laki',
    'P' => 'Perempuan'
];

// Fungsi untuk upload file
function uploadFile($file, $target_dir = '../../uploads/ktp/') {
    // Buat direktori jika belum ada
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $errors = [];
    $max_size = 2 * 1024 * 1024; // 2MB
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
    
    // Cek apakah file terupload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Error uploading file";
        return ['success' => false, 'errors' => $errors];
    }
    
    // Cek ukuran file
    if ($file['size'] > $max_size) {
        $errors[] = "Ukuran file maksimal 2MB";
        return ['success' => false, 'errors' => $errors];
    }
    
    // Cek tipe file
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        $errors[] = "Tipe file harus JPG, PNG, GIF, atau PDF";
        return ['success' => false, 'errors' => $errors];
    }
    
    // Generate nama file unik
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'KTP_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
    $target_file = $target_dir . $new_filename;
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return [
            'success' => true, 
            'filename' => $new_filename,
            'filepath' => 'uploads/ktp/' . $new_filename
        ];
    } else {
        $errors[] = "Gagal mengupload file";
        return ['success' => false, 'errors' => $errors];
    }
}

// Proses registrasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $no_kk = $_POST['no_kk'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $nama_orang_tua = $_POST['nama_orang_tua'] ?? '';
    $no_hp = $_POST['no_hp'] ?? '';
    $asal_sekolah = $_POST['asal_sekolah'] ?? '';
    $prodi_pilihan = $_POST['prodi_pilihan'] ?? '';
    $kata_motivasi = $_POST['kata_motivasi'] ?? '';
    $alamat = $_POST['alamat'] ?? '';
    
    // Validasi
    $errors = [];
    
    // Cek No KK
    if (empty($no_kk)) {
        $errors[] = "NIK/Nomor KK harus diisi!";
    } elseif (!preg_match('/^[0-9]{16}$/', $no_kk)) {
        $errors[] = "NIK/Nomor KK harus 16 digit angka!";
    }
    
    // Cek email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email tidak valid!";
    }
    
    // Cek password
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter!";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Password dan konfirmasi password tidak cocok!";
    }
    
    // Cek nama
    if (empty($nama_lengkap)) {
        $errors[] = "Nama lengkap harus diisi!";
    }
    
    // Cek tanggal lahir
    if (empty($tanggal_lahir)) {
        $errors[] = "Tanggal lahir harus diisi!";
    } else {
        // Validasi format tanggal (YYYY-MM-DD)
        $date_parts = explode('-', $tanggal_lahir);
        if (count($date_parts) !== 3 || !checkdate($date_parts[1], $date_parts[2], $date_parts[0])) {
            $errors[] = "Format tanggal lahir tidak valid!";
        } else {
            // Cek usia minimal 15 tahun
            $birth_date = new DateTime($tanggal_lahir);
            $today = new DateTime();
            $age = $today->diff($birth_date)->y;
            
            if ($age < 15) {
                $errors[] = "Usia minimal 15 tahun!";
            }
            if ($age > 50) {
                $errors[] = "Usia maksimal 50 tahun!";
            }
        }
    }
    
    // Cek jenis kelamin
    if (empty($jenis_kelamin) || !array_key_exists($jenis_kelamin, $gender_list)) {
        $errors[] = "Pilih jenis kelamin yang valid!";
    }
    
    // Cek nama orang tua
    if (empty($nama_orang_tua)) {
        $errors[] = "Nama orang tua harus diisi!";
    }
    
    // Cek alamat
    if (empty($alamat)) {
        $errors[] = "Alamat harus diisi!";
    }
    
    // Cek prodi
    if (empty($prodi_pilihan) || !in_array($prodi_pilihan, $prodi_list)) {
        $errors[] = "Pilih program studi yang valid!";
    }
    
    // Upload file KTP
    $file_ktp = '';
    if (isset($_FILES['file_ktp']) && $_FILES['file_ktp']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload_result = uploadFile($_FILES['file_ktp']);
        if ($upload_result['success']) {
            $file_ktp = $upload_result['filepath'];
        } else {
            $errors = array_merge($errors, $upload_result['errors']);
        }
    } else {
        $errors[] = "File KTP harus diupload!";
    }
    
    // Cek apakah no_kk sudah terdaftar
    if (empty($errors)) {
        $check_nokk = "SELECT id FROM camaba WHERE no_kk = ?";
        $stmt = $conn->prepare($check_nokk);
        $stmt->bind_param("s", $no_kk);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $errors[] = "Nomor KK sudah terdaftar!";
        }
        $stmt->close();
    }
    
    // Cek email sudah terdaftar
    if (empty($errors)) {
        $check_email = "SELECT id FROM camaba WHERE email = ?";
        $stmt = $conn->prepare($check_email);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $errors[] = "Email sudah terdaftar!";
        }
        $stmt->close();
    }
    
    // Jika tidak ada error, simpan ke database
    if (empty($errors)) {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Generate kode verifikasi
        $kode_verifikasi = rand(100000, 999999);
        
        // Set username default dari email (bagian sebelum @)
        $username = explode('@', $email)[0];
        
        // Insert ke database
        $sql = "INSERT INTO camaba (
            no_kk, file_ktp, email, password, username, nama_lengkap, tanggal_lahir, 
            jenis_kelamin, nama_orang_tua, no_hp, asal_sekolah, prodi_pilihan, 
            kode_verifikasi, status, alamat
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'belum_verifikasi', ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssssss", 
            $no_kk, $file_ktp, $email, $password_hash, $username, $nama_lengkap, 
            $tanggal_lahir, $jenis_kelamin, $nama_orang_tua, $no_hp, $asal_sekolah, 
            $prodi_pilihan, $kode_verifikasi, $alamat
        );
        
        if ($stmt->execute()) {
            $camaba_id = $stmt->insert_id;
            
            // Generate nomor tes (format: PMB-TAHUN-ID)
            $nomor_tes = 'PMB' . date('Y') . str_pad($camaba_id, 4, '0', STR_PAD_LEFT);
            
            $update_sql = "UPDATE camaba SET nomor_tes = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $nomor_tes, $camaba_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Kirim email verifikasi (simulasi)
            $email_subject = "Verifikasi Pendaftaran PMB - " . $nomor_tes;
            $email_message = "Halo $nama_lengkap,\n\n";
            $email_message .= "Terima kasih telah mendaftar di PMB Universitas Kita.\n\n";
            $email_message .= "DATA PENDAFTARAN:\n";
            $email_message .= "Nomor KK: $no_kk\n";
            $email_message .= "Nomor Tes: $nomor_tes\n";
            $email_message .= "Nama Lengkap: $nama_lengkap\n";
            $email_message .= "Tanggal Lahir: " . date('d-m-Y', strtotime($tanggal_lahir)) . "\n";
            $email_message .= "Jenis Kelamin: " . $gender_list[$jenis_kelamin] . "\n";
            $email_message .= "Nama Orang Tua: $nama_orang_tua\n";
            $email_message .= "Alamat: $alamat\n";
            $email_message .= "Program Studi: $prodi_pilihan\n\n";
            
            if (!empty($kata_motivasi)) {
                $email_message .= "Kata Motivasi Anda:\n\"$kata_motivasi\"\n\n";
            }
            
            $email_message .= "KODE VERIFIKASI: $kode_verifikasi\n\n";
            $email_message .= "Silakan login dengan:\n";
            $email_message .= "Nomor Tes: $nomor_tes\n";
            $email_message .= "Password: (yang Anda buat)\n\n";
            $email_message .= "Harap verifikasi akun Anda dengan memasukkan kode di halaman verifikasi.\n\n";
            $email_message .= "Salam,\nPanitia PMB Universitas Kita";
            
            // Simpan email ke file (untuk development)
            file_put_contents("email_verifikasi_$camaba_id.txt", $email_message);
            
            // Simpan data di session untuk verifikasi
            $_SESSION['verification_email'] = $email;
            $_SESSION['verification_camaba_id'] = $camaba_id;
            
            // Redirect ke halaman verifikasi
            header("Location: verifikasi.php");
            exit();
            
        } else {
            $error = "Terjadi kesalahan saat menyimpan data: " . $conn->error;
        }
        
        $stmt->close();
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Calon Mahasiswa - PMB Universitas Kita</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
        }
        
        /* GLOBAL STYLES */
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--dark-red), var(--primary-red));
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        /* REGISTER CONTAINER */
        .register-container {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(183, 28, 28, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 800px;
        }
        
        /* HEADER */
        .register-header {
            background: linear-gradient(135deg, var(--dark-red), var(--primary-red));
            color: var(--white);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .register-header::before {
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
        
        .register-icon {
            font-size: 3.5rem;
            color: var(--accent-red);
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .register-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .register-header p {
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        /* BODY */
        .register-body {
            padding: 40px 30px;
        }
        
        /* ALERT */
        .alert-custom {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            font-size: 0.95rem;
            margin-bottom: 25px;
        }
        
        .alert-error {
            background-color: #ffebee;
            color: var(--primary-red);
            border-left: 4px solid var(--primary-red);
        }
        
        /* FORM */
        .form-label {
            font-weight: 600;
            color: var(--dark-red);
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .form-label.required::after {
            content: ' *';
            color: var(--primary-red);
        }
        
        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-group-custom .input-group-text {
            background-color: #ffebee;
            border: 2px solid #ffcdd2;
            border-right: none;
            color: var(--primary-red);
            padding: 12px 15px;
            border-radius: 10px 0 0 10px;
        }
        
        .input-group-custom .form-control,
        .input-group-custom .form-select,
        .input-group-custom textarea {
            border: 2px solid #ffcdd2;
            border-left: none;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .input-group-custom .form-control,
        .input-group-custom textarea {
            border-radius: 0 10px 10px 0;
        }
        
        .input-group-custom .form-select {
            border-radius: 0 10px 10px 0;
            background-color: var(--white);
        }
        
        .input-group-custom .form-control:focus,
        .input-group-custom .form-select:focus,
        .input-group-custom textarea:focus {
            border-color: var(--light-red);
            box-shadow: 0 0 0 3px rgba(255, 82, 82, 0.2);
        }
        
        /* Custom file input */
        .custom-file-input {
            position: relative;
            width: 100%;
        }
        
        .custom-file-input .form-control {
            border-radius: 0 10px 10px 0 !important;
            background-color: var(--white);
        }
        
        .file-info {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 5px;
        }
        
        /* DATE INPUT */
        .input-group-custom input[type="date"] {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }
        
        /* PASSWORD STRENGTH */
        .password-strength {
            margin-top: 5px;
            font-size: 0.85rem;
        }
        
        .strength-bar {
            height: 6px;
            background-color: #eee;
            border-radius: 3px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            background-color: #ff4444;
            transition: width 0.3s, background-color 0.3s;
            border-radius: 3px;
        }
        
        .password-match {
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        .password-match.valid {
            color: #4caf50;
        }
        
        .password-match.invalid {
            color: var(--primary-red);
        }
        
        /* PASSWORD TOGGLE */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            z-index: 5;
        }
        
        /* BUTTON */
        .btn-register {
            background: linear-gradient(135deg, var(--light-red), var(--primary-red));
            color: var(--white);
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(198, 40, 40, 0.3);
            background: linear-gradient(135deg, var(--primary-red), var(--dark-red));
        }
        
        .btn-register:active {
            transform: translateY(-1px);
        }
        
        /* INFO BOX */
        .info-box {
            background-color: var(--bg-light);
            border-radius: 12px;
            padding: 20px;
            margin-top: 30px;
            border-left: 4px solid var(--accent-red);
        }
        
        .info-box h5 {
            color: var(--dark-red);
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .info-box ul {
            padding-left: 20px;
            margin-bottom: 0;
        }
        
        .info-box li {
            margin-bottom: 8px;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .info-box a {
            color: var(--primary-red);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .info-box a:hover {
            color: var(--dark-red);
            text-decoration: underline;
        }
        
        /* FOOTER */
        .register-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .register-footer a {
            color: var(--primary-red);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .register-footer a:hover {
            color: var(--dark-red);
            text-decoration: underline;
        }
        
        /* ANIMATIONS */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* MOTIVATION SECTION */
        .motivation-suggestions {
            background-color: var(--bg-light);
            padding: 15px;
            border-radius: 12px;
            border: 1px dashed var(--accent-red);
        }
        
        .suggestion-btn {
            background-color: white;
            border: 1px solid var(--light-red);
            color: var(--primary-red);
            padding: 5px 12px;
            font-size: 0.85rem;
            border-radius: 20px;
            transition: all 0.3s;
        }
        
        .suggestion-btn:hover {
            background-color: var(--primary-red);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(198, 40, 40, 0.2);
        }
        
        .suggestion-btn i {
            margin-right: 4px;
            font-size: 0.75rem;
        }
        
        #charCount {
            font-size: 0.8rem;
            transition: color 0.3s;
        }
        
        #charCount.warning {
            color: #ff9800;
        }
        
        #charCount.danger {
            color: var(--primary-red);
        }
        
        textarea#kata_motivasi {
            min-height: 100px;
            resize: vertical;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .register-container {
                border-radius: 15px;
            }
            
            .register-header, .register-body {
                padding: 30px 20px;
            }
            
            .register-header h1 {
                font-size: 1.8rem;
            }
            
            .register-icon {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container fade-in">
        <!-- Header -->
        <div class="register-header">
            <div class="register-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1>Registrasi Calon Mahasiswa</h1>
            <p>Isi form berikut untuk mendaftar PMB</p>
        </div>
        
        <!-- Body -->
        <div class="register-body">
            <!-- Pesan Error -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-custom alert-error fade-in" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-circle me-3"></i>
                        <div><?php echo $error; ?></div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Form Registrasi -->
            <form action="" method="POST" id="registerForm" enctype="multipart/form-data">
                <div class="row">
                    <!-- No KK / NIK -->
                    <div class="col-md-6 mb-3">
                        <label for="no_kk" class="form-label required">NIK/Nomor KK</label>
                        <div class="input-group input-group-custom">
                            <span class="input-group-text">
                                <i class="fas fa-id-card"></i>
                            </span>
                            <input type="text" 
                                   id="no_kk" 
                                   name="no_kk" 
                                   class="form-control" 
                                   placeholder="16 digit angka"
                                   maxlength="16"
                                   pattern="[0-9]{16}"
                                   value="<?php echo htmlspecialchars($_POST['no_kk'] ?? ''); ?>"
                                   required>
                        </div>
                        <small class="text-muted">16 digit angka, contoh: 3173010101010001</small>
                    </div>
                    
                    <!-- File KTP -->
                    <div class="col-md-6 mb-3">
                        <label for="file_ktp" class="form-label required">File KTP Asli</label>
                        <div class="input-group input-group-custom">
                            <span class="input-group-text">
                                <i class="fas fa-image"></i>
                            </span>
                            <input type="file" 
                                   id="file_ktp" 
                                   name="file_ktp" 
                                   class="form-control" 
                                   accept=".jpg,.jpeg,.png,.gif,.pdf"
                                   required>
                        </div>
                        <div class="file-info" id="file-info">
                            <i class="fas fa-info-circle"></i> Maksimal 2MB. Format: JPG, PNG, GIF, PDF
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Nama Lengkap -->
                    <div class="col-md-6 mb-3">
                        <label for="nama_lengkap" class="form-label required">Nama Lengkap</label>
                        <div class="input-group input-group-custom">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" 
                                   id="nama_lengkap" 
                                   name="nama_lengkap" 
                                   class="form-control" 
                                   placeholder="Nama lengkap sesuai ijazah"
                                   value="<?php echo htmlspecialchars($_POST['nama_lengkap'] ?? ''); ?>"
                                   required>
                        </div>
                    </div>
                    
                    <!-- Nama Orang Tua -->
                    <div class="col-md-6 mb-3">
                        <label for="nama_orang_tua" class="form-label required">Nama Orang Tua/Wali</label>
                        <div class="input-group input-group-custom">
                            <span class="input-group-text">
                                <i class="fas fa-user-friends"></i>
                            </span>
                            <input type="text" 
                                   id="nama_orang_tua" 
                                   name="nama_orang_tua" 
                                   class="form-control" 
                                   placeholder="Nama ayah/ibu/wali"
                                   value="<?php echo htmlspecialchars($_POST['nama_orang_tua'] ?? ''); ?>"
                                   required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Tanggal Lahir -->
                    <div class="col-md-4 mb-3">
                        <label for="tanggal_lahir" class="form-label required">Tanggal Lahir</label>
                        <div class="input-group input-group-custom">
                            <span class="input-group-text">
                                <i class="fas fa-calendar-alt"></i>
                            </span>
                            <input type="date" 
                                   id="tanggal_lahir" 
                                   name="tanggal_lahir" 
                                   class="form-control" 
                                   max="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo htmlspecialchars($_POST['tanggal_lahir'] ?? ''); ?>"
                                   required>
                        </div>
                        <small class="text-muted">Minimal 15 tahun</small>
                    </div>
                    
                    <!-- Jenis Kelamin -->
                    <div class="col-md-4 mb-3">
                        <label for="jenis_kelamin" class="form-label required">Jenis Kelamin</label>
                        <div class="input-group input-group-custom">
                            <span class="input-group-text">
                                <i class="fas fa-venus-mars"></i>
                            </span>
                            <select id="jenis_kelamin" name="jenis_kelamin" class="form-select" required>
                                <option value="">Pilih</option>
                                <?php foreach ($gender_list as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" 
                                        <?php echo (($_POST['jenis_kelamin'] ?? '') == $value) ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- No. Handphone -->
                    <div class="col-md-4 mb-3">
                        <label for="no_hp" class="form-label">No. Handphone Aktif</label>
                        <div class="input-group input-group-custom">
                            <span class="input-group-text">
                                <i class="fas fa-phone"></i>
                            </span>
                            <input type="tel" 
                                   id="no_hp" 
                                   name="no_hp" 
                                   class="form-control" 
                                   placeholder="08xxxxxxxxxx"
                                   value="<?php echo htmlspecialchars($_POST['no_hp'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label required">Email</label>
                    <div class="input-group input-group-custom">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               placeholder="email@contoh.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               required>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Password -->
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label required">Password</label>
                        <div class="input-group input-group-custom">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control" 
                                   placeholder="Minimal 6 karakter"
                                   required>
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <span id="strength-text">Kekuatan password</span>
                            <div class="strength-bar">
                                <div class="strength-fill" id="strength-fill"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Konfirmasi Password -->
                    <div class="col-md-6 mb-3">
                        <label for="confirm_password" class="form-label required">Konfirmasi Password</label>
                        <div class="input-group input-group-custom">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   class="form-control" 
                                   placeholder="Ulangi password"
                                   required>
                            <button type="button" class="password-toggle" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-match" id="password-match"></div>
                    </div>
                </div>
                
                <!-- Alamat -->
                <div class="mb-3">
                    <label for="alamat" class="form-label required">Alamat Lengkap</label>
                    <div class="input-group input-group-custom">
                        <span class="input-group-text">
                            <i class="fas fa-home"></i>
                        </span>
                        <textarea id="alamat" name="alamat" class="form-control" rows="3" 
                                  placeholder="Alamat lengkap sesuai KTP" required><?php echo htmlspecialchars($_POST['alamat'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <!-- Asal Sekolah -->
                <div class="mb-3">
                    <label for="asal_sekolah" class="form-label">Asal Sekolah</label>
                    <div class="input-group input-group-custom">
                        <span class="input-group-text">
                            <i class="fas fa-school"></i>
                        </span>
                        <input type="text" 
                               id="asal_sekolah" 
                               name="asal_sekolah" 
                               class="form-control" 
                               placeholder="Nama sekolah sebelumnya"
                               value="<?php echo htmlspecialchars($_POST['asal_sekolah'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Program Studi -->
                <div class="mb-4">
                    <label for="prodi_pilihan" class="form-label required">Program Studi Pilihan</label>
                    <div class="input-group input-group-custom">
                        <span class="input-group-text">
                            <i class="fas fa-graduation-cap"></i>
                        </span>
                        <select id="prodi_pilihan" name="prodi_pilihan" class="form-select" required>
                            <option value="">Pilih Program Studi''</option>
                            <?php foreach ($prodi_list as $prodi): ?>
                                <option value="<?php echo $prodi; ?>" 
                                    <?php echo (($_POST['prodi_pilihan'] ?? '') == $prodi) ? 'selected' : ''; ?>>
                                    <?php echo $prodi; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Kata-kata Motivasi -->
                <div class="mb-4">
                    <label for="kata_motivasi" class="form-label">Kata-kata Motivasi <span class="text-muted">(opsional)</span></label>
                    <div class="input-group input-group-custom">
                        <span class="input-group-text">
                            <i class="fas fa-quote-right"></i>
                        </span>
                        <textarea 
                            id="kata_motivasi" 
                            name="kata_motivasi" 
                            class="form-control" 
                            placeholder="Tuliskan kata-kata motivasi atau semangat untuk perjalanan kuliah Anda nanti..."
                            rows="3"
                            maxlength="500"
                        ><?php echo htmlspecialchars($_POST['kata_motivasi'] ?? ''); ?></textarea>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> Bagikan semangat dan motivasi Anda (maksimal 500 karakter)
                        </small>
                        <small class="text-muted" id="charCount">0/500</small>
                    </div>
                    
                    <!-- Contoh kata-kata motivasi yang bisa diklik -->
                    <div class="motivation-suggestions mt-2">
                        <small class="text-muted d-block mb-1"><i class="fas fa-lightbulb"></i> Contoh motivasi:</small>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-danger suggestion-btn" data-text="Saya ingin menjadi kebanggaan orang tua dan berguna bagi masyarakat.">
                                <i class="fas fa-quote-left"></i> Kebanggaan orang tua
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger suggestion-btn" data-text="Semangat menuntut ilmu untuk masa depan yang lebih cerah!">
                                <i class="fas fa-quote-left"></i> Masa depan cerah
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger suggestion-btn" data-text="Dengan ilmu, saya bisa mengubah dunia menjadi lebih baik.">
                                <i class="fas fa-quote-left"></i> Mengubah dunia
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger suggestion-btn" data-text="Tekadku bulat untuk menjadi profesional di bidang yang kucintai.">
                                <i class="fas fa-quote-left"></i> Profesional
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn btn-register">
                    <i class="fas fa-paper-plane me-2"></i> Daftar Sekarang
                </button>
            </form>
            
            <!-- Informasi -->
            <div class="info-box fade-in">
                <h5><i class="fas fa-info-circle me-2"></i> Informasi Pendaftaran:</h5>
                <ul class="mb-0">
                    <li>Kolom dengan tanda <span class="text-danger">*</span> wajib diisi</li>
                    <li>NIK/Nomor KK harus 16 digit angka dan akan dicek keunikannya</li>
                    <li>Upload file KTP asli (maksimal 2MB, format: JPG, PNG, GIF, PDF)</li>
                    <li>Pastikan email aktif untuk menerima kode verifikasi</li>
                    <li>Tanggal lahir minimal 15 tahun dan maksimal 50 tahun</li>
                    <li>Nama orang tua akan digunakan untuk keperluan administrasi</li>
                    <li>Setelah registrasi, Anda akan diarahkan ke halaman verifikasi</li>
                    <li>Masukkan kode verifikasi yang dikirim ke email Anda</li>
                    <li>Sudah punya akun? <a href="../login/login.php">Login di sini</a></li>
                    <li>Bagikan kata-kata motivasi Anda (opsional, maksimal 500 karakter)</li>
                </ul>
            </div>
            
            <!-- Footer -->
            <div class="register-footer">
                <p class="mb-2">© 2024 PMB Universitas Kita. Semua hak dilindungi.</p>
                <p class="mb-0">
                    <a href="../../index.php">
                        <i class="fas fa-home me-1"></i> Kembali ke Beranda
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Character counter untuk kata motivasi
        const motivasiInput = document.getElementById('kata_motivasi');
        const charCount = document.getElementById('charCount');
        const maxChars = 500;
        
        if (motivasiInput) {
            // Hitung karakter awal jika ada
            updateCharCount();
            
            motivasiInput.addEventListener('input', function() {
                updateCharCount();
            });
            
            function updateCharCount() {
                const currentLength = motivasiInput.value.length;
                charCount.textContent = `${currentLength}/${maxChars}`;
                
                // Visual feedback
                if (currentLength > maxChars) {
                    motivasiInput.value = motivasiInput.value.substring(0, maxChars);
                    charCount.textContent = `${maxChars}/${maxChars}`;
                }
                
                if (currentLength >= maxChars - 50 && currentLength < maxChars) {
                    charCount.className = 'warning';
                } else if (currentLength >= maxChars) {
                    charCount.className = 'danger';
                } else {
                    charCount.className = '';
                }
            }
        }
        
        // Suggestion buttons
        const suggestionBtns = document.querySelectorAll('.suggestion-btn');
        suggestionBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const suggestion = this.getAttribute('data-text');
                
                // Jika textarea kosong, langsung isi
                if (!motivasiInput.value.trim()) {
                    motivasiInput.value = suggestion;
                } else {
                    // Jika tidak kosong, tambahkan dengan opsi
                    if (confirm('Tambahkan motivasi ini ke yang sudah ada?')) {
                        motivasiInput.value += ' ' + suggestion;
                    } else {
                        motivasiInput.value = suggestion;
                    }
                }
                
                // Trigger input event untuk update counter
                motivasiInput.dispatchEvent(new Event('input'));
                
                // Focus ke textarea
                motivasiInput.focus();
                
                // Animasi button
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 200);
            });
        });
        
        // Random motivasi
        function getRandomMotivation() {
            const motivations = [
                "Pendidikan adalah senjata paling ampuh untuk mengubah dunia.",
                "Masa depan adalah milik mereka yang percaya pada mimpi-mimpinya.",
                "Jangan pernah menyerah, karena hari esok selalu membawa harapan baru.",
                "Ilmu tanpa agama adalah buta, agama tanpa ilmu adalah lumpuh.",
                "Sukses adalah jumlah dari usaha kecil yang diulangi hari demi hari.",
                "Percayalah pada dirimu sendiri dan semua yang kamu miliki.",
                "Setiap ahli pernah menjadi pemula. Teruslah belajar!",
                "Kesuksesan bukanlah akhir, kegagalan bukanlah awal.",
                "Jadilah pribadi yang bermanfaat bagi orang lain.",
                "Teruslah melangkah, karena setiap langkah adalah pembelajaran."
            ];
            
            const randomIndex = Math.floor(Math.random() * motivations.length);
            return motivations[randomIndex];
        }
        
        // Tambahkan tombol random
        const randomBtn = document.createElement('button');
        randomBtn.type = 'button';
        randomBtn.className = 'btn btn-sm btn-outline-secondary mt-2';
        randomBtn.innerHTML = '<i class="fas fa-random me-1"></i> Motivasi Random';
        randomBtn.onclick = function() {
            motivasiInput.value = getRandomMotivation();
            motivasiInput.dispatchEvent(new Event('input'));
        };
        document.querySelector('.motivation-suggestions').appendChild(randomBtn);
        
        // Toggle Password Visibility
        function setupPasswordToggle(inputId, toggleId) {
            const passwordInput = document.getElementById(inputId);
            const toggleBtn = document.getElementById(toggleId);
            
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const icon = this.querySelector('i');
                    
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        passwordInput.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            }
        }
        
        // Setup password toggles
        setupPasswordToggle('password', 'togglePassword');
        setupPasswordToggle('confirm_password', 'toggleConfirmPassword');
        
        // Password Strength Meter
        const passwordInput = document.getElementById('password');
        const strengthFill = document.getElementById('strength-fill');
        const strengthText = document.getElementById('strength-text');
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                // Kriteria kekuatan password
                if (password.length >= 6) strength++;
                if (password.length >= 8) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^A-Za-z0-9]/.test(password)) strength++;
                
                // Update tampilan
                let width = (strength / 5) * 100;
                let color = '#ff4444';
                let text = 'Lemah';
                
                if (strength >= 2) {
                    color = '#ffaa00';
                    text = 'Cukup';
                }
                if (strength >= 3) {
                    color = '#ffaa00';
                    text = 'Sedang';
                }
                if (strength >= 4) {
                    color = '#00C851';
                    text = 'Kuat';
                }
                if (strength >= 5) {
                    color = '#007E33';
                    text = 'Sangat Kuat';
                }
                
                if (strengthFill) {
                    strengthFill.style.width = width + '%';
                    strengthFill.style.backgroundColor = color;
                }
                if (strengthText) {
                    strengthText.textContent = 'Kekuatan password: ' + text;
                    strengthText.style.color = color;
                }
            });
        }
        
        // Check Password Match
        const confirmInput = document.getElementById('confirm_password');
        const matchText = document.getElementById('password-match');
        
        function checkPasswordMatch() {
            const password = passwordInput ? passwordInput.value : '';
            const confirm = confirmInput ? confirmInput.value : '';
            
            if (matchText) {
                if (confirm === '') {
                    matchText.textContent = '';
                    matchText.className = 'password-match';
                } else if (password === confirm) {
                    matchText.textContent = '✓ Password cocok';
                    matchText.className = 'password-match valid';
                } else {
                    matchText.textContent = '✗ Password tidak cocok';
                    matchText.className = 'password-match invalid';
                }
            }
        }
        
        if (passwordInput && confirmInput) {
            passwordInput.addEventListener('input', checkPasswordMatch);
            confirmInput.addEventListener('input', checkPasswordMatch);
        }
        
        // Validasi No KK (hanya angka)
        const noKKInput = document.getElementById('no_kk');
        if (noKKInput) {
            noKKInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 16) {
                    this.value = this.value.slice(0, 16);
                }
            });
        }
        
        // Validasi file KTP
        const fileInput = document.getElementById('file_ktp');
        const fileInfo = document.getElementById('file-info');
        
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    // Cek ukuran file (maksimal 2MB)
                    if (file.size > 2 * 1024 * 1024) {
                        fileInfo.innerHTML = '<i class="fas fa-exclamation-circle text-danger"></i> File terlalu besar! Maksimal 2MB';
                        fileInfo.style.color = 'var(--primary-red)';
                        this.value = ''; // Reset file input
                    } 
                    // Cek tipe file
                    else if (!['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'].includes(file.type)) {
                        fileInfo.innerHTML = '<i class="fas fa-exclamation-circle text-danger"></i> Tipe file tidak valid! Harus JPG, PNG, GIF, atau PDF';
                        fileInfo.style.color = 'var(--primary-red)';
                        this.value = ''; // Reset file input
                    }
                    else {
                        fileInfo.innerHTML = '<i class="fas fa-check-circle text-success"></i> File: ' + file.name + ' (' + (file.size / 1024).toFixed(2) + ' KB)';
                        fileInfo.style.color = '#4caf50';
                    }
                } else {
                    fileInfo.innerHTML = '<i class="fas fa-info-circle"></i> Maksimal 2MB. Format: JPG, PNG, GIF, PDF';
                    fileInfo.style.color = 'var(--text-light)';
                }
            });
        }
        
        // Validasi Tanggal Lahir
        const tanggalLahirInput = document.getElementById('tanggal_lahir');
        
        if (tanggalLahirInput) {
            // Set tanggal maksimal hari ini
            tanggalLahirInput.max = new Date().toISOString().split('T')[0];
            
            // Set tanggal minimal (50 tahun lalu)
            const today = new Date();
            const minDate = new Date(today.getFullYear() - 50, today.getMonth(), today.getDate());
            tanggalLahirInput.min = minDate.toISOString().split('T')[0];
        }
        
        // Form Validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const noKK = document.getElementById('no_kk').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            const confirm = document.getElementById('confirm_password').value.trim();
            const nama = document.getElementById('nama_lengkap').value.trim();
            const tanggalLahir = document.getElementById('tanggal_lahir').value;
            const jenisKelamin = document.getElementById('jenis_kelamin').value;
            const namaOrangTua = document.getElementById('nama_orang_tua').value.trim();
            const prodi = document.getElementById('prodi_pilihan').value;
            const alamat = document.getElementById('alamat').value.trim();
            const fileKtp = document.getElementById('file_ktp').files[0];
            
            let errors = [];
            
            // Validasi No KK
            if (!noKK) {
                errors.push('NIK/Nomor KK harus diisi');
            } else if (!/^\d{16}$/.test(noKK)) {
                errors.push('NIK/Nomor KK harus 16 digit angka');
            }
            
            // Validasi file KTP
            if (!fileKtp) {
                errors.push('File KTP harus diupload');
            } else {
                if (fileKtp.size > 2 * 1024 * 1024) {
                    errors.push('Ukuran file KTP maksimal 2MB');
                }
                if (!['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'].includes(fileKtp.type)) {
                    errors.push('Tipe file KTP harus JPG, PNG, GIF, atau PDF');
                }
            }
            
            // Validasi email
            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                errors.push('Email tidak valid');
            }
            
            // Validasi password
            if (password.length < 6) {
                errors.push('Password minimal 6 karakter');
            }
            
            if (password !== confirm) {
                errors.push('Password dan konfirmasi tidak cocok');
            }
            
            // Validasi nama
            if (!nama) {
                errors.push('Nama lengkap harus diisi');
            }
            
            // Validasi tanggal lahir
            if (!tanggalLahir) {
                errors.push('Tanggal lahir harus diisi');
            } else {
                const birthDate = new Date(tanggalLahir);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                
                if (age < 15) {
                    errors.push('Usia minimal 15 tahun');
                }
                if (age > 50) {
                    errors.push('Usia maksimal 50 tahun');
                }
            }
            
            // Validasi jenis kelamin
            if (!jenisKelamin) {
                errors.push('Pilih jenis kelamin');
            }
            
            // Validasi nama orang tua
            if (!namaOrangTua) {
                errors.push('Nama orang tua harus diisi');
            }
            
            // Validasi alamat
            if (!alamat) {
                errors.push('Alamat harus diisi');
            }
            
            // Validasi prodi
            if (!prodi) {
                errors.push('Pilih program studi');
            }
            
            if (errors.length > 0) {
                e.preventDefault();
                alert('Perbaiki kesalahan berikut:\n\n' + errors.join('\n'));
                return false;
            }
            
            return true;
        });
        
        // Auto focus on no_kk field
        document.addEventListener('DOMContentLoaded', function() {
            const noKKField = document.getElementById('no_kk');
            if (noKKField) {
                noKKField.focus();
            }
        });
        
        // Real-time age calculation
        if (tanggalLahirInput) {
            tanggalLahirInput.addEventListener('change', function() {
                const birthDate = new Date(this.value);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                
                // Show age information
                let ageInfo = document.getElementById('age-info');
                if (!ageInfo) {
                    ageInfo = document.createElement('small');
                    ageInfo.className = 'text-muted d-block mt-1';
                    ageInfo.id = 'age-info';
                    this.parentNode.parentNode.appendChild(ageInfo);
                }
                ageInfo.textContent = `Usia: ${age} tahun`;
            });
        }
    </script>
</body>
</html>