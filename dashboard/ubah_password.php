<?php
require_once '../config/backend-dashboard/password/config.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Password - PMB Universitas Kita</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../config/backend-dashboard/password/style.css">
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>
                PMB <span>Universitas Kita</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"><i class="fas fa-bars text-white"></i></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_camaba.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="ubah_password.php">Password</a>
                    </li>
                    
                    <!-- PROFILE DROPDOWN -->
                    <li class="nav-item ms-2 profile-dropdown">
                        <div class="dropdown">
                            <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars(explode(' ', $camaba['nama_lengkap'])[0]); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="dashboard_camaba.php">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="profile.php">
                                        <i class="fas fa-user-edit"></i> Lihat Profil
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="ubah_password.php">
                                        <i class="fas fa-key"></i> Ubah Password
                                    </a>
                                </li>
                                <?php if ($camaba['status'] == 'sudah_ujian' || $camaba['status'] == 'lulus' || $camaba['status'] == 'gagal'): ?>
                                <li>
                                    <a class="dropdown-item" href="hasil_tes.php">
                                        <i class="fas fa-chart-bar"></i> Lihat Hasil Tes
                                    </a>
                                </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="../config/logout.php">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- PASSWORD CONTENT -->
    <div class="password-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- Message Alert -->
                    <?php if ($message): ?>
                    <div class="alert alert-custom alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="password-card">
                        <!-- Header -->
                        <div class="password-header">
                            <div class="password-icon">
                                <i class="fas fa-key"></i>
                            </div>
                            <h1>Ubah Password</h1>
                            <p>Pastikan akun Anda tetap aman dengan password yang kuat</p>
                        </div>
                        
                        <!-- Body -->
                        <div class="password-body">
                            <!-- Form Ubah Password -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-lock"></i> Ubah Password
                                </h3>
                                
                                <form method="POST" action="" id="passwordForm">
                                    <!-- Password Saat Ini -->
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-key"></i> Password Saat Ini
                                        </label>
                                        <div class="password-input-wrapper">
                                            <input type="password" class="form-control" name="current_password" 
                                                   id="current_password" required placeholder="Masukkan password saat ini">
                                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('current_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Password Baru -->
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-lock"></i> Password Baru
                                        </label>
                                        <div class="password-input-wrapper">
                                            <input type="password" class="form-control" name="new_password" 
                                                   id="new_password" required placeholder="Masukkan password baru (minimal 8 karakter)"
                                                   minlength="8">
                                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('new_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Password Strength -->
                                        <div class="password-strength">
                                            <div class="strength-bar">
                                                <div class="strength-fill" id="strengthFill"></div>
                                            </div>
                                            <div class="strength-text" id="strengthText">Kekuatan password</div>
                                        </div>
                                        
                                        <!-- Requirements -->
                                        <ul class="requirements-list">
                                            <li id="reqLength" class="invalid">
                                                <i class="fas fa-times"></i> Minimal 8 karakter
                                            </li>
                                            <li id="reqUppercase" class="invalid">
                                                <i class="fas fa-times"></i> Minimal 1 huruf besar
                                            </li>
                                            <li id="reqLowercase" class="invalid">
                                                <i class="fas fa-times"></i> Minimal 1 huruf kecil
                                            </li>
                                            <li id="reqNumber" class="invalid">
                                                <i class="fas fa-times"></i> Minimal 1 angka
                                            </li>
                                            <li id="reqSpecial" class="invalid">
                                                <i class="fas fa-times"></i> Minimal 1 karakter khusus
                                            </li>
                                        </ul>
                                    </div>
                                    
                                    <!-- Konfirmasi Password Baru -->
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-lock"></i> Konfirmasi Password Baru
                                        </label>
                                        <div class="password-input-wrapper">
                                            <input type="password" class="form-control" name="confirm_password" 
                                                   id="confirm_password" required placeholder="Masukkan kembali password baru">
                                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('confirm_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="mt-2">
                                            <small id="passwordMatch" class="text-danger" style="display: none;">
                                                <i class="fas fa-times"></i> Password tidak cocok
                                            </small>
                                            <small id="passwordMatchSuccess" class="text-success" style="display: none;">
                                                <i class="fas fa-check"></i> Password cocok
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <!-- Tombol Ubah Password -->
                                    <div class="text-center mt-4">
                                        <button type="submit" name="change_password" class="btn btn-primary-custom">
                                            <i class="fas fa-check"></i> Ubah Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Security Tips -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-lightbulb"></i> Tips Keamanan Password
                                </h3>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <h6><i class="fas fa-check text-success me-2"></i> Yang Harus Dilakukan:</h6>
                                            <ul class="text-muted">
                                                <li>Gunakan minimal 8 karakter</li>
                                                <li>Gabungkan huruf besar, kecil, angka, dan simbol</li>
                                                <li>Gunakan password yang mudah diingat tapi sulit ditebak</li>
                                                <li>Simpan password di tempat yang aman</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <h6><i class="fas fa-times text-danger me-2"></i> Yang Harus Dihindari:</h6>
                                            <ul class="text-muted">
                                                <li>Jangan gunakan informasi pribadi</li>
                                                <li>Jangan gunakan kata-kata umum</li>
                                                <li>Jangan gunakan password yang sama untuk semua akun</li>
                                                <li>Jangan bagikan password dengan siapapun</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Note -->
                            <div class="alert alert-warning alert-custom">
                                <h5><i class="fas fa-exclamation-triangle me-2"></i> Catatan Penting</h5>
                                <ul class="mb-0">
                                    <li>Setelah password berhasil diubah, Anda akan otomatis logout</li>
                                    <li>Silakan login kembali dengan password baru Anda</li>
                                    <li>Pastikan Anda mengingat password baru</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <h5>PMB Universitas Kita</h5>
                    <p class="mb-4" style="color: rgba(255,255,255,0.8);">
                        Portal resmi penerimaan mahasiswa baru tahun akademik 2024/2025. 
                        Mari bergabung menjadi bagian dari kami.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-youtube fa-lg"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <h5>Tautan Cepat</h5>
                    <div class="footer-links">
                        <a href="../index.php">Beranda</a>
                        <a href="dashboard_camaba.php">Dashboard</a>
                        <a href="profile.php">Profil Saya</a>
                        <a href="ubah_password.php">Ubah Password</a>
                        <a href="../config/logout.php">Logout</a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <h5>Kontak Kami</h5>
                    <div class="contact-info">
                        <p class="mb-3">
                            <i class="fas fa-map-marker-alt"></i>
                            Jl. Pendidikan No. 123, Kota Kita
                        </p>
                        <p class="mb-3">
                            <i class="fas fa-phone"></i>
                            (021) 1234-5678
                        </p>
                        <p class="mb-3">
                            <i class="fas fa-envelope"></i>
                            pmb@universitaskita.ac.id
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <h5>Status Akun</h5>
                    <div class="footer-links">
                        <a href="#">
                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($camaba['nama_lengkap']); ?>
                        </a>
                        <a href="#">
                            <i class="fas fa-id-card me-1"></i> <?php echo htmlspecialchars($camaba['nomor_tes']); ?>
                        </a>
                        <a href="#">
                            <i class="fas fa-graduation-cap me-1"></i> <?php echo htmlspecialchars($camaba['prodi_pilihan']); ?>
                        </a>
                        <a href="#">
                            <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($camaba['email']); ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; 2024 PMB Universitas Kita. Semua hak dilindungi.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="../config/backend-dashboard/password/script.js"></script>
</body>
</html>