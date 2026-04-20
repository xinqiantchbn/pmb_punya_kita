<?php
session_start();
require_once '../../config/database.php'; 

$error = '';
$success = '';

// Tambahkan kode untuk mengecek apakah sudah login sebagai admin
if (isset($_SESSION['admins_id'])) {
    header("Location: ../../index.php");
    exit();
}

if (isset($_SESSION['camaba_id'])) {
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['identifier'] ?? '';
    $password = $_POST['password'] ?? '';
    $login_type = $_POST['login_type'] ?? 'camaba'; // Default login sebagai camaba
    
    if (empty($identifier) || empty($password)) {
        $error = "Data login harus diisi lengkap!";
    } else {
        // Login berdasarkan tipe pengguna
        if ($login_type === 'admin') {
            // LOGIN ADMIN
            $sql = "SELECT * FROM admins WHERE noa = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $identifier);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                // Verifikasi password (asumsi password disimpan dengan hashing)
                if (password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_noa'] = $admin['noa'];
                    $_SESSION['admin_nama'] = $admin['nama_lengkap'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['user_type'] = 'admin';
                    header("Location: ../../admin/index.php");
                    exit();
                } else {
                    $error = "NOA atau password salah!";
                }
            } else {
                $error = "Akun admin tidak ditemukan!";
            }
            
            $stmt->close();
        } else {
            // LOGIN CAMABA (kode yang sudah ada)
            $sql = "SELECT * FROM camaba WHERE nomor_tes = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $identifier);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $camaba = $result->fetch_assoc();
                if (password_verify($password, $camaba['password'])) {
                    if ($camaba['status'] === 'belum_verifikasi') {
                        $error = "Akun belum diverifikasi. Cek email Anda untuk kode verifikasi.";
                    } else {
                        $_SESSION['camaba_id'] = $camaba['id'];
                        $_SESSION['nomor_tes'] = $camaba['nomor_tes'];
                        $_SESSION['nama'] = $camaba['nama_lengkap'];
                        $_SESSION['status'] = $camaba['status'];
                        $_SESSION['user_type'] = 'camaba';
                        header("Location: ../../index.php");
                        exit();
                    }
                } else {
                    $error = "Nomor tes atau password salah!";
                }
            } else {
                $error = "Nomor tes tidak ditemukan!";
            }
            
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PMB Universitas Kita</title>
    
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
            --admin-blue: #1565c0;
            --admin-dark-blue: #0d47a1;
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
        
        /* LOGIN CONTAINER */
        .login-container {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(183, 28, 28, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
        }
        
        /* HEADER */
        .login-header {
            background: linear-gradient(135deg, var(--dark-red), var(--primary-red));
            color: var(--white);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header.admin {
            background: linear-gradient(135deg, var(--admin-dark-blue), var(--admin-blue));
        }
        
        .login-header::before {
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
        
        .login-icon {
            font-size: 3.5rem;
            color: var(--accent-red);
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .login-icon.admin {
            color: #90caf9;
        }
        
        .login-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .login-header p {
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        /* BODY */
        .login-body {
            padding: 40px 30px;
        }
        
        /* TABS */
        .login-tabs {
            display: flex;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 30px;
            background: var(--bg-light);
            border: 2px solid #ffcdd2;
        }
        
        .tab-btn {
            flex: 1;
            padding: 12px;
            border: none;
            background: transparent;
            color: var(--text-light);
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .tab-btn.active {
            background: var(--primary-red);
            color: var(--white);
        }
        
        .tab-btn.admin.active {
            background: var(--admin-blue);
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
        
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }
        
        /* FORM */
        .form-label {
            font-weight: 600;
            color: var(--dark-red);
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .admin .form-label {
            color: var(--admin-dark-blue);
        }
        
        .input-group-custom {
            position: relative;
            margin-bottom: 25px;
        }
        
        .input-group-custom .input-group-text {
            background-color: #ffebee;
            border: 2px solid #ffcdd2;
            border-right: none;
            color: var(--primary-red);
            padding: 12px 15px;
            border-radius: 10px 0 0 10px;
        }
        
        .admin .input-group-custom .input-group-text {
            background-color: #e3f2fd;
            border-color: #bbdefb;
            color: var(--admin-blue);
        }
        
        .input-group-custom .form-control {
            border: 2px solid #ffcdd2;
            border-left: none;
            padding: 12px 15px;
            border-radius: 0 10px 10px 0;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .admin .input-group-custom .form-control {
            border-color: #bbdefb;
        }
        
        .input-group-custom .form-control:focus {
            border-color: var(--light-red);
            box-shadow: 0 0 0 3px rgba(255, 82, 82, 0.2);
        }
        
        .admin .input-group-custom .form-control:focus {
            border-color: #2196f3;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.2);
        }
        
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
        .btn-login {
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
        
        .btn-login.admin {
            background: linear-gradient(135deg, #42a5f5, var(--admin-blue));
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(198, 40, 40, 0.3);
            background: linear-gradient(135deg, var(--primary-red), var(--dark-red));
        }
        
        .btn-login.admin:hover {
            box-shadow: 0 10px 20px rgba(21, 101, 192, 0.3);
            background: linear-gradient(135deg, var(--admin-blue), var(--admin-dark-blue));
        }
        
        .btn-login:active {
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
        
        .info-box.admin {
            border-left-color: #42a5f5;
        }
        
        .info-box h5 {
            color: var(--dark-red);
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .info-box.admin h5 {
            color: var(--admin-dark-blue);
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
        
        .info-box.admin a {
            color: var(--admin-blue);
        }
        
        .info-box a:hover {
            color: var(--dark-red);
            text-decoration: underline;
        }
        
        .info-box.admin a:hover {
            color: var(--admin-dark-blue);
        }
        
        /* FOOTER */
        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .login-footer a {
            color: var(--primary-red);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .login-footer a:hover {
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
        
        /* RESPONSIVE */
        @media (max-width: 576px) {
            .login-container {
                border-radius: 15px;
            }
            
            .login-header, .login-body {
                padding: 30px 20px;
            }
            
            .login-header h1 {
                font-size: 1.8rem;
            }
            
            .login-icon {
                font-size: 3rem;
            }
            
            .tab-btn {
                font-size: 0.85rem;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container fade-in">
        <!-- Header - akan berubah berdasarkan tipe login -->
        <div class="login-header <?php echo isset($_POST['login_type']) && $_POST['login_type'] === 'admin' ? 'admin' : ''; ?>" id="loginHeader">
            <div class="login-icon <?php echo isset($_POST['login_type']) && $_POST['login_type'] === 'admin' ? 'admin' : ''; ?>" id="loginIcon">
                <?php if (isset($_POST['login_type']) && $_POST['login_type'] === 'admin'): ?>
                    <i class="fas fa-user-shield"></i>
                <?php else: ?>
                    <i class="fas fa-graduation-cap"></i>
                <?php endif; ?>
            </div>
            <h1 id="loginTitle">
                <?php echo isset($_POST['login_type']) && $_POST['login_type'] === 'admin' ? 'Login Admin PMB' : 'Login Calon Mahasiswa'; ?>
            </h1>
            <p id="loginSubtitle">
                <?php echo isset($_POST['login_type']) && $_POST['login_type'] === 'admin' ? 'Masuk dengan NOA dan password admin' : 'Masuk dengan nomor tes dan password Anda'; ?>
            </p>
        </div>
        
        <!-- Body -->
        <div class="login-body">
            <!-- Tabs untuk pilihan login -->
            <div class="login-tabs">
                <button type="button" class="tab-btn <?php echo (!isset($_POST['login_type']) || $_POST['login_type'] === 'camaba') ? 'active' : ''; ?>" id="tabCamaba" data-type="camaba">
                    <i class="fas fa-graduation-cap"></i> Calon Mahasiswa
                </button>
                <button type="button" class="tab-btn admin <?php echo (isset($_POST['login_type']) && $_POST['login_type'] === 'admin') ? 'active' : ''; ?>" id="tabAdmin" data-type="admin">
                    <i class="fas fa-user-shield"></i> Admin PMB
                </button>
            </div>
            
            <!-- Pesan Error/Sukses -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-custom alert-error fade-in" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-circle me-3"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-custom alert-success fade-in" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-3"></i>
                        <div><?php echo htmlspecialchars($success); ?></div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Form Login -->
            <form action="" method="POST" id="loginForm">
                <!-- Input tersembunyi untuk menentukan tipe login -->
                <input type="hidden" name="login_type" id="loginType" value="<?php echo isset($_POST['login_type']) && $_POST['login_type'] === 'admin' ? 'admin' : 'camaba'; ?>">
                
                <!-- Form input akan berubah berdasarkan tipe login -->
                <div class="mb-4 <?php echo isset($_POST['login_type']) && $_POST['login_type'] === 'admin' ? 'admin' : ''; ?>" id="formFields">
                    <div class="mb-4">
                        <label for="identifier" class="form-label" id="identifierLabel">
                            <?php echo isset($_POST['login_type']) && $_POST['login_type'] === 'admin' ? 'NOA Admin' : 'Nomor Tes'; ?>
                        </label>
                        <div class="input-group input-group-custom">
                            <span class="input-group-text">
                                <?php if (isset($_POST['login_type']) && $_POST['login_type'] === 'admin'): ?>
                                    <i class="fas fa-id-badge"></i>
                                <?php else: ?>
                                    <i class="fas fa-id-card"></i>
                                <?php endif; ?>
                            </span>
                            <input type="text" 
                                   id="identifier" 
                                   name="identifier" 
                                   class="form-control" 
                                   placeholder="<?php echo isset($_POST['login_type']) && $_POST['login_type'] === 'admin' ? 'Masukkan NOA admin' : 'Masukkan nomor tes Anda'; ?>"
                                   value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>"
                                   required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group input-group-custom">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control" 
                                   placeholder="Masukkan password"
                                   required>
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login <?php echo isset($_POST['login_type']) && $_POST['login_type'] === 'admin' ? 'admin' : ''; ?>" id="loginButton">
                    <?php if (isset($_POST['login_type']) && $_POST['login_type'] === 'admin'): ?>
                        <i class="fas fa-sign-in-alt me-2"></i> Masuk ke Dashboard Admin
                    <?php else: ?>
                        <i class="fas fa-sign-in-alt me-2"></i> Masuk ke Akun Camaba
                    <?php endif; ?>
                </button>
            </form>
            
            <!-- Informasi -->
            <div class="info-box <?php echo isset($_POST['login_type']) && $_POST['login_type'] === 'admin' ? 'admin' : ''; ?>" id="infoBox">
                <h5>
                    <i class="fas fa-info-circle me-2"></i>
                    <span id="infoTitle">
                        <?php echo isset($_POST['login_type']) && $_POST['login_type'] === 'admin' ? 'Informasi Login Admin:' : 'Informasi Login Calon Mahasiswa:'; ?>
                    </span>
                </h5>
                <ul class="mb-0" id="infoContent">
                    <?php if (isset($_POST['login_type']) && $_POST['login_type'] === 'admin'): ?>
                        <li>Gunakan <strong>NOA admin</strong> yang telah diberikan</li>
                        <li>Password bersifat rahasia dan hanya untuk administrator</li>
                        <li>Pastikan Anda adalah admin yang berwenang</li>
                        <li>Hubungi super admin jika ada masalah akses</li>
                    <?php else: ?>
                        <li>Gunakan <strong>nomor tes</strong> yang telah Anda terima via email</li>
                        <li>Password adalah yang Anda buat saat registrasi</li>
                        <li>Belum punya akun? <a href="../register/register.php">Daftar di sini</a></li>
                        <li>Lupa password? Hubungi admin PMB di <strong>pmb@universitaskita.ac.id</strong></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Footer -->
            <div class="login-footer">
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
        // Toggle Password Visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
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
        
        // Fungsi untuk mengganti tampilan berdasarkan tipe login
        function switchLoginType(type) {
            const loginHeader = document.getElementById('loginHeader');
            const loginIcon = document.getElementById('loginIcon');
            const loginTitle = document.getElementById('loginTitle');
            const loginSubtitle = document.getElementById('loginSubtitle');
            const loginTypeInput = document.getElementById('loginType');
            const identifierLabel = document.getElementById('identifierLabel');
            const identifierInput = document.getElementById('identifier');
            const formFields = document.getElementById('formFields');
            const loginButton = document.getElementById('loginButton');
            const infoBox = document.getElementById('infoBox');
            const infoTitle = document.getElementById('infoTitle');
            const infoContent = document.getElementById('infoContent');
            
            if (type === 'admin') {
                // Tampilan admin
                loginHeader.classList.add('admin');
                loginIcon.classList.add('admin');
                loginIcon.innerHTML = '<i class="fas fa-user-shield"></i>';
                loginTitle.textContent = 'Login Admin PMB';
                loginSubtitle.textContent = 'Masuk dengan NOA dan password admin';
                loginTypeInput.value = 'admin';
                identifierLabel.textContent = 'NOA Admin';
                identifierInput.placeholder = 'Masukkan NOA admin';
                formFields.classList.add('admin');
                loginButton.classList.add('admin');
                loginButton.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i> Masuk ke Dashboard Admin';
                infoBox.classList.add('admin');
                infoTitle.textContent = 'Informasi Login Admin:';
                infoContent.innerHTML = `
                    <li>Gunakan <strong>NOA admin</strong> yang telah diberikan</li>
                    <li>Password bersifat rahasia dan hanya untuk administrator</li>
                    <li>Pastikan Anda adalah admin yang berwenang</li>
                    <li>Hubungi super admin jika ada masalah akses</li>
                `;
                
                // Update tabs
                document.getElementById('tabCamaba').classList.remove('active');
                document.getElementById('tabAdmin').classList.add('active');
            } else {
                // Tampilan camaba
                loginHeader.classList.remove('admin');
                loginIcon.classList.remove('admin');
                loginIcon.innerHTML = '<i class="fas fa-graduation-cap"></i>';
                loginTitle.textContent = 'Login Calon Mahasiswa';
                loginSubtitle.textContent = 'Masuk dengan nomor tes dan password Anda';
                loginTypeInput.value = 'camaba';
                identifierLabel.textContent = 'Nomor Tes';
                identifierInput.placeholder = 'Masukkan nomor tes Anda';
                formFields.classList.remove('admin');
                loginButton.classList.remove('admin');
                loginButton.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i> Masuk ke Akun Camaba';
                infoBox.classList.remove('admin');
                infoTitle.textContent = 'Informasi Login Calon Mahasiswa:';
                infoContent.innerHTML = `
                    <li>Gunakan <strong>nomor tes</strong> yang telah Anda terima via email</li>
                    <li>Password adalah yang Anda buat saat registrasi</li>
                    <li>Belum punya akun? <a href="../register/register.php">Daftar di sini</a></li>
                    <li>Lupa password? Hubungi admin PMB di <strong>pmb@universitaskita.ac.id</strong></li>
                `;
                
                // Update tabs
                document.getElementById('tabCamaba').classList.add('active');
                document.getElementById('tabAdmin').classList.remove('active');
            }
            
            // Reset input
            identifierInput.value = '';
            document.getElementById('password').value = '';
            identifierInput.focus();
        }
        
        // Event listener untuk tabs
        document.getElementById('tabCamaba').addEventListener('click', function() {
            switchLoginType('camaba');
        });
        
        document.getElementById('tabAdmin').addEventListener('click', function() {
            switchLoginType('admin');
        });
        
        // Form Validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const identifier = document.getElementById('identifier').value.trim();
            const password = document.getElementById('password').value.trim();
            const loginType = document.getElementById('loginType').value;
            
            let errors = [];
            
            if (!identifier) {
                errors.push(loginType === 'admin' ? 'NOA admin harus diisi' : 'Nomor tes harus diisi');
            } else if (loginType === 'admin') {
                // Validasi untuk admin (contoh: minimal 3 karakter)
                if (identifier.length < 3) {
                    errors.push('NOA admin minimal 3 karakter');
                }
            } else {
                // Validasi untuk camaba
                if (!/^[A-Za-z0-9]{8,20}$/.test(identifier)) {
                    errors.push('Format nomor tes tidak valid');
                }
            }
            
            if (!password) {
                errors.push('Password harus diisi');
            } else if (password.length < 6) {
                errors.push('Password minimal 6 karakter');
            }
            
            if (errors.length > 0) {
                e.preventDefault();
                alert('Perbaiki kesalahan berikut:\n\n' + errors.join('\n'));
                return false;
            }
            
            return true;
        });
        
        // Auto focus pada field identifier
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('identifier').focus();
        });
        
        // Animation for alerts
        const alerts = document.querySelectorAll('.alert-custom');
        alerts.forEach((alert, index) => {
            alert.style.animationDelay = `${index * 0.1}s`;
        });
    </script>
</body>
</html>