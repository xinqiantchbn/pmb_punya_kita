<?php
require_once '../config/backend-dashboard/profile/config.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - PMB Universitas Kita</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../config/backend-dashboard/profile/style.css">
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
                        <a class="nav-link active" href="profile.php">Profil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ubah_password.php">Password</a>
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
                                    <a class="dropdown-item" href="../tes_ujian/hasil_tes.php">
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

    <!-- PROFILE CONTENT -->
    <div class="profile-container">
        <div class="container">
            <!-- Message Alert -->
            <?php if ($message): ?>
            <div class="alert alert-custom alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="profile-card">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-photo-container">
                        <?php if (!empty($camaba['foto_profil'])): ?>
                            <img src="../<?php echo htmlspecialchars($camaba['foto_profil']); ?>" alt="Foto Profil" class="profile-photo">
                        <?php else: ?>
                            <div class="profile-photo-placeholder">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="photo-actions">
                            <button type="button" class="photo-btn" data-bs-toggle="modal" data-bs-target="#uploadFotoModal">
                                <i class="fas fa-camera"></i>
                            </button>
                            <?php if (!empty($camaba['foto_profil'])): ?>
                            <button type="button" class="photo-btn" data-bs-toggle="modal" data-bs-target="#hapusFotoModal">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <h2 class="profile-name"><?php echo htmlspecialchars($camaba['nama_lengkap']); ?></h2>
                    
                    <div class="profile-status">
                        <span class="status-badge">
                            <?php echo $statusText[$camaba['status']]; ?>
                        </span>
                    </div>
                </div>
                
                <!-- Profile Body -->
                <div class="profile-body">
                    <!-- Informasi Pribadi -->
                    <div class="profile-section">
                        <h3 class="section-title">
                            <i class="fas fa-user"></i> Informasi Pribadi
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Nama Lengkap</div>
                                    <div class="info-value"><?php echo htmlspecialchars($camaba['nama_lengkap']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Tanggal Lahir</div>
                                    <div class="info-value">
                                        <?php echo $camaba['tanggal_lahir'] ? date('d/m/Y', strtotime($camaba['tanggal_lahir'])) : '-'; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Jenis Kelamin</div>
                                    <div class="info-value"><?php echo htmlspecialchars($camaba['gender']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Nama Orang Tua</div>
                                    <div class="info-value"><?php echo htmlspecialchars($camaba['nama_orang_tua']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informasi Akun -->
                    <div class="profile-section">
                        <h3 class="section-title">
                            <i class="fas fa-key"></i> Informasi Akun
                        </h3>
                        
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" name="username" 
                                               value="<?php echo htmlspecialchars($camaba['username']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Email</label>
                                        <div class="input-group">
                                            <input type="email" class="form-control" 
                                                   value="<?php echo htmlspecialchars($camaba['email']); ?>" 
                                                   id="currentEmail" readonly>
                                            <button type="button" class="btn btn-primary-custom" 
                                                    data-bs-toggle="modal" data-bs-target="#ubahEmailModal">
                                                Ubah
                                            </button>
                                        </div>
                                        <?php if ($camaba['status'] == 'belum_verifikasi' || $camaba['status'] == 'pending'): ?>
                                        <small class="text-danger mt-2 d-block">
                                            <i class="fas fa-exclamation-circle"></i>
                                            Email belum diverifikasi. Silakan verifikasi email Anda.
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Nomor Telepon</label>
                                        <input type="text" class="form-control" name="no_hp" 
                                               value="<?php echo htmlspecialchars($camaba['no_hp']); ?>"
                                               pattern="[0-9]{10,15}" 
                                               title="Nomor telepon harus 10-15 digit angka">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Asal Sekolah</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo htmlspecialchars($camaba['asal_sekolah']); ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end mt-3">
                                <button type="submit" name="update_profile" class="btn btn-primary-custom">
                                    <i class="fas fa-save me-2"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Informasi Pendaftaran -->
                    <div class="profile-section">
                        <h3 class="section-title">
                            <i class="fas fa-file-alt"></i> Informasi Pendaftaran
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Program Studi</div>
                                    <div class="info-value"><?php echo htmlspecialchars($camaba['prodi_pilihan']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Nomor Tes</div>
                                    <div class="info-value"><?php echo htmlspecialchars($camaba['nomor_tes']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Status Pendaftaran</div>
                                    <div class="info-value">
                                        <span class="badge bg-<?php 
                                            $colorMap = [
                                                'belum_verifikasi' => 'warning',
                                                'baru' => 'info',
                                                'sudah_ujian' => 'primary',
                                                'lulus' => 'success',
                                                'gagal' => 'danger',
                                                'daftar_ulang' => 'success',
                                                'pending' => 'warning'
                                            ];
                                            echo $colorMap[$camaba['status']];
                                        ?>">
                                            <?php echo $statusText[$camaba['status']]; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Status Pembayaran</div>
                                    <div class="info-value">
                                        <span class="badge bg-<?php 
                                            $bayarColorMap = [
                                                'belum' => 'danger',
                                                'menunggu' => 'warning',
                                                'lunas' => 'success'
                                            ];
                                            echo $bayarColorMap[$camaba['status_bayar']];
                                        ?>">
                                            <?php echo $statusBayarText[$camaba['status_bayar']]; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Tanggal Daftar</div>
                                    <div class="info-value">
                                        <?php echo date('d/m/Y H:i', strtotime($camaba['tanggal_daftar'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php if ($camaba['tanggal_verifikasi']): ?>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Tanggal Verifikasi</div>
                                    <div class="info-value">
                                        <?php echo date('d/m/Y H:i', strtotime($camaba['tanggal_verifikasi'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($camaba['nim'])): ?>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">NIM</div>
                                    <div class="info-value"><?php echo htmlspecialchars($camaba['nim']); ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL UPLOAD FOTO -->
    <div class="modal fade" id="uploadFotoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-camera me-2"></i> Upload Foto Profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">Pilih Foto</label>
                            <input type="file" class="form-control" name="foto_profil" accept="image/*" required>
                            <small class="text-muted">Format: JPG, PNG, GIF | Maksimal: 2MB</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="upload_foto" class="btn btn-primary-custom">
                            <i class="fas fa-upload me-2"></i> Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL HAPUS FOTO -->
    <div class="modal fade" id="hapusFotoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash me-2"></i> Hapus Foto Profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus foto profil?</p>
                        <p class="text-danger"><small>Foto yang dihapus tidak dapat dikembalikan.</small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="hapus_foto" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i> Hapus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL UBAH EMAIL -->
    <div class="modal fade" id="ubahEmailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-envelope me-2"></i> Ubah Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">Email Baru</label>
                            <input type="email" class="form-control" name="email" required
                                   value="<?php echo htmlspecialchars($camaba['email']); ?>">
                            <small class="text-muted">Kode verifikasi akan dikirim ke email baru</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_email" class="btn btn-primary-custom">
                            <i class="fas fa-paper-plane me-2"></i> Kirim Kode Verifikasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL VERIFIKASI EMAIL -->
    <?php if ($camaba['status'] == 'belum_verifikasi' || $camaba['status'] == 'pending'): ?>
    <div class="modal fade show" id="verifikasiEmailModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-shield-alt me-2"></i> Verifikasi Email</h5>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <p>Kode verifikasi telah dikirim ke email <strong><?php echo htmlspecialchars($camaba['email']); ?></strong></p>
                        <p>Silakan masukkan kode verifikasi yang Anda terima:</p>
                        
                        <div class="form-group">
                            <label class="form-label">Kode Verifikasi</label>
                            <input type="text" class="form-control text-center" name="kode_verifikasi" 
                                   pattern="[0-9]{6}" maxlength="6" required
                                   placeholder="Masukkan 6 digit kode">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="verifikasi_email" class="btn btn-primary-custom">
                            <i class="fas fa-check me-2"></i> Verifikasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Tampilkan modal verifikasi email otomatis
        document.addEventListener('DOMContentLoaded', function() {
            var modal = new bootstrap.Modal(document.getElementById('verifikasiEmailModal'));
            modal.show();
        });
    </script>
    <?php endif; ?>

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
                        <a href="#" class="<?php echo in_array($camaba['status'], ['baru', 'lulus', 'daftar_ulang', 'sudah_ujian']) ? 'text-success' : 'text-warning'; ?>">
                            <i class="fas fa-check-circle me-1"></i> <?php echo $statusText[$camaba['status']]; ?>
                        </a>
                        <a href="#">
                            <i class="fas fa-id-card me-1"></i> <?php echo htmlspecialchars($camaba['nomor_tes']); ?>
                        </a>
                        <a href="#">
                            <i class="fas fa-graduation-cap me-1"></i> <?php echo htmlspecialchars($camaba['prodi_pilihan']); ?>
                        </a>
                        <a href="#" class="<?php echo $camaba['status_bayar'] == 'lunas' ? 'text-success' : 'text-warning'; ?>">
                            <i class="fas fa-money-bill-wave me-1"></i> <?php echo $statusBayarText[$camaba['status_bayar']]; ?>
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
    
    <script src="../config/backend-dashboard/profile/script.js"></script>
</body>
</html>