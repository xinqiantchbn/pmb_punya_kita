<?php
date_default_timezone_set('Asia/Jakarta');
require_once 'config/session.php';
require_once 'config/database.php';

// Cek status login
$isLoggedIn = isLoggedIn();
$userData = null;
$jadwalUjian = null;

if ($isLoggedIn) {
    $camaba_id = $_SESSION['camaba_id'];

    // Ambil semua data camaba (SELECT * seperti punya teman)
    $query = "SELECT * FROM camaba WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $camaba_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();

    // Cek jadwal ujian terdekat yang aktif (logika punya teman)
    $ujianSettingQuery = "SELECT * FROM ujian_setting WHERE status = 'active' AND tanggal_ujian >= CURDATE() ORDER BY tanggal_ujian, jam_mulai LIMIT 1";
    $ujianSettingResult = $conn->query($ujianSettingQuery);
    $jadwalUjian = $ujianSettingResult->fetch_assoc();
}

// =============================================
// SEMUA FUNGSI DARI BACKEND TEMAN
// =============================================

function getActionButton($userData, $jadwalUjian) {
    // Case 1: belum_verifikasi / baru
    if ($userData['status'] == 'belum_verifikasi' || $userData['status'] == 'baru') {
        return [
            'type' => 'secondary',
            'button' => [
                'text' => 'Menunggu Aktivasi Akun',
                'link' => '#',
                'disabled' => true,
                'icon' => 'fas fa-hourglass-half'
            ],
            'message' => 'Akun Anda sedang dalam proses verifikasi oleh admin. Mohon menunggu konfirmasi.'
        ];
    }

    // Case 2: aktif
    if ($userData['status'] == 'aktif') {
        if ($jadwalUjian) {
            $waktu_sekarang = date('H:i:s');
            $tanggal_sekarang = date('Y-m-d');
            $bisa_ujian = ($tanggal_sekarang == $jadwalUjian['tanggal_ujian'] &&
                          $waktu_sekarang >= $jadwalUjian['jam_mulai'] &&
                          $waktu_sekarang <= $jadwalUjian['jam_selesai']);

            return [
                'type' => $bisa_ujian ? 'primary' : 'secondary',
                'button' => [
                    'text' => $bisa_ujian ? 'Mulai Ujian Sekarang' : 'Jadwal Ujian',
                    'link' => $bisa_ujian ? 'tes_ujian/ujian_token.php' : '#',
                    'disabled' => !$bisa_ujian,
                    'icon' => $bisa_ujian ? 'fas fa-pencil-alt' : 'fas fa-clock'
                ],
                'message' => $bisa_ujian ?
                    'Ujian sudah dapat dimulai. Pastikan koneksi internet Anda stabil dan siapkan diri sebaik mungkin.' :
                    'Jadwal ujian: ' . date('d M Y', strtotime($jadwalUjian['tanggal_ujian'])) . ' ' .
                    date('H:i', strtotime($jadwalUjian['jam_mulai'])) . ' - ' .
                    date('H:i', strtotime($jadwalUjian['jam_selesai']))
            ];
        } else {
            return [
                'type' => 'secondary',
                'button' => [
                    'text' => 'Menunggu Jadwal Ujian',
                    'link' => '#',
                    'disabled' => true,
                    'icon' => 'fas fa-hourglass-half'
                ],
                'message' => 'Jadwal ujian belum ditentukan. Silakan pantau terus informasi terbaru dari panitia PMB.'
            ];
        }
    }

    // Case 3: sudah_ujian
    if ($userData['status'] == 'sudah_ujian') {
        return [
            'type' => 'secondary',
            'button' => [
                'text' => 'Menunggu Hasil Ujian',
                'link' => '#',
                'disabled' => true,
                'icon' => 'fas fa-hourglass-half'
            ],
            'message' => 'Anda telah menyelesaikan ujian. Tim panitia sedang melakukan koreksi dan pengolahan nilai. Mohon kesabarannya.'
        ];
    }

    // Case 4: lulus
    if ($userData['status'] == 'lulus') {
        return [
            'type' => 'primary',
            'button' => [
                'text' => 'Lihat Hasil',
                'link' => 'tes_ujian/hasil_ujian.php',
                'disabled' => false,
                'icon' => 'fas fa-chart-bar'
            ],
            'message' => 'Hasil seleksi Anda telah tersedia. Klik tombol di bawah untuk melihat informasi lebih lanjut.'
        ];
    }

    // Case 5: gagal
    if ($userData['status'] == 'gagal') {
        return [
            'type' => 'primary',
            'button' => [
                'text' => 'Lihat Hasil',
                'link' => 'tes_ujian/hasil_ujian.php',
                'disabled' => false,
                'icon' => 'fas fa-chart-bar'
            ],
            'message' => 'Hasil seleksi Anda telah tersedia. Klik tombol di bawah untuk melihat informasi lebih lanjut.'
        ];
    }

    // Case 6: daftar_ulang
    if ($userData['status'] == 'daftar_ulang') {
        return [
            'type' => 'success',
            'button' => [
                'text' => 'Daftar Ulang',
                'link' => 'daftar_ulang.php',
                'disabled' => false,
                'icon' => 'fas fa-clipboard-check'
            ],
            'message' => 'Selamat! Anda berhak untuk melakukan daftar ulang. Lengkapi data dan lakukan pembayaran sesuai ketentuan.'
        ];
    }

    // Case 7: selected
    if ($userData['status'] == 'selected') {
        $statusBayar = $userData['status_bayar'] ?? 'belum';

        if ($statusBayar == 'belum') {
            return [
                'type' => 'warning',
                'button' => [
                    'text' => 'Hubungi Admin via WhatsApp',
                    'link' => 'https://wa.me/6281234567890?text=Halo%20Admin%2C%20saya%20' . urlencode($userData['nama_lengkap']) . '%20(Nomor%20Tes%3A%20' . urlencode($userData['nomor_tes']) . ')%20ingin%20melakukan%20konfirmasi%20pembayaran%20daftar%20ulang.%20Mohon%20bantuannya.%20Terima%20kasih.',
                    'disabled' => false,
                    'icon' => 'fab fa-whatsapp'
                ],
                'message' => 'Untuk melakukan pembayaran daftar ulang dan konfirmasi, silakan hubungi admin melalui WhatsApp.'
            ];
        } elseif ($statusBayar == 'menunggu') {
            return [
                'type' => 'secondary',
                'button' => [
                    'text' => 'Menunggu Verifikasi Pembayaran',
                    'link' => '#',
                    'disabled' => true,
                    'icon' => 'fas fa-hourglass-half'
                ],
                'message' => 'Bukti pembayaran Anda sedang dalam proses verifikasi oleh admin. Mohon bersabar.'
            ];
        } elseif ($statusBayar == 'lunas') {
            return [
                'type' => 'success',
                'button' => [
                    'text' => 'Logout',
                    'link' => 'config/logout.php',
                    'disabled' => false,
                    'icon' => 'fas fa-sign-out-alt'
                ],
                'message' => '✓ Pembayaran Terverifikasi | Selamat bergabung sebagai mahasiswa baru! NIM: ' . ($userData['nim'] ?? '-'),
                'show_nim' => true
            ];
        }
    }

    // Case 8: not_selected
    if ($userData['status'] == 'not_selected') {
        return [
            'type' => 'secondary',
            'button' => [
                'text' => 'Logout',
                'link' => 'config/logout.php',
                'disabled' => false,
                'icon' => 'fas fa-sign-out-alt'
            ],
            'message' => 'Terima kasih telah mengikuti seleksi PMB. Mohon maaf, Anda belum berhasil. Tetap semangat!'
        ];
    }

    // Default
    return [
        'type' => 'secondary',
        'button' => [
            'text' => 'Dashboard Saya',
            'link' => 'dashboard/dashboard_camaba.php',
            'disabled' => false,
            'icon' => 'fas fa-tachometer-alt'
        ],
        'message' => 'Kelola informasi pendaftaran Anda di dashboard.'
    ];
}

function getStatusLabel($status, $statusBayar = null) {
    if ($status == 'selected') {
        if ($statusBayar == 'belum') return 'Menunggu Pembayaran';
        if ($statusBayar == 'menunggu') return 'Verifikasi Pembayaran';
        if ($statusBayar == 'lunas') return 'Mahasiswa Aktif';
        return 'Proses Seleksi';
    }

    $labels = [
        'belum_verifikasi' => 'Menunggu Verifikasi',
        'baru'             => 'Pendaftaran Baru',
        'aktif'            => 'Siap Ujian',
        'sudah_ujian'      => 'Menunggu Hasil',
        'lulus'            => 'Lihat Hasil',
        'gagal'            => 'Lihat Hasil',
        'daftar_ulang'     => 'Daftar Ulang',
        'selected'         => 'Mahasiswa Aktif',
        'not_selected'     => 'Tidak Lulus'
    ];
    return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function canShowNim($userData) {
    return $userData['status'] == 'selected' &&
           ($userData['status_bayar'] ?? '') == 'lunas' &&
           !empty($userData['nim']);
}

$actionButton = $isLoggedIn && $userData ? getActionButton($userData, $jadwalUjian) : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penerimaan Mahasiswa Baru - Universitas Kita</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="config/backend-index/style.css">
    <style>
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

        * { font-family: 'Poppins', sans-serif; }
        body { color: var(--text-dark); overflow-x: hidden; }

        /* ===== NAVBAR ===== */
        .navbar-custom {
            background: linear-gradient(135deg, var(--dark-red), var(--primary-red)) !important;
            padding: 15px 0;
            box-shadow: 0 2px 15px rgba(198, 40, 40, 0.2);
        }
        .navbar-brand { color: var(--white) !important; font-weight: 700; font-size: 1.8rem; }
        .navbar-brand span { color: var(--accent-red); }
        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500; margin: 0 8px;
            padding: 8px 15px !important;
            border-radius: 20px; transition: all 0.3s;
        }
        .navbar-nav .nav-link:hover {
            color: var(--white) !important;
            background-color: rgba(255,255,255,0.1);
        }
        .btn-login {
            background-color: var(--accent-red);
            color: var(--dark-red) !important;
            font-weight: 600; padding: 8px 25px !important;
            border-radius: 25px; border: none; transition: all 0.3s;
        }
        .btn-login:hover {
            background-color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,138,128,0.4);
        }
        .profile-dropdown .dropdown-toggle {
            background-color: var(--accent-red);
            color: var(--dark-red) !important;
            font-weight: 600; padding: 8px 25px !important;
            border-radius: 25px; border: none;
            display: flex; align-items: center; gap: 8px;
        }
        .profile-dropdown .dropdown-toggle:hover {
            background-color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,138,128,0.4);
        }
        .profile-dropdown .dropdown-menu {
            border-radius: 15px; border: none;
            box-shadow: 0 10px 30px rgba(198,40,40,0.15);
            margin-top: 10px;
        }
        .profile-dropdown .dropdown-item {
            padding: 12px 20px; color: var(--text-dark); transition: all 0.3s;
        }
        .profile-dropdown .dropdown-item:hover {
            background-color: #ffebee; color: var(--primary-red); padding-left: 25px;
        }
        .profile-dropdown .dropdown-item i { width: 20px; margin-right: 10px; color: var(--primary-red); }
        .dropdown-divider { border-color: #ffcdd2; }

        /* ===== HERO ===== */
        .hero-section {
            background: linear-gradient(rgba(183,28,28,0.85), rgba(198,40,40,0.9)),
                        url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3');
            background-size: cover; background-position: center; background-attachment: fixed;
            color: var(--white); padding: 150px 0; position: relative;
        }
        .hero-title { font-size: 3.5rem; font-weight: 700; margin-bottom: 25px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        .hero-subtitle { font-size: 1.3rem; max-width: 700px; margin: 0 auto 40px; opacity: 0.95; }

        .welcome-user {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px; padding: 30px; margin-bottom: 40px;
            border: 2px solid rgba(255,255,255,0.2);
        }
        .user-avatar {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, var(--accent-red), var(--light-red));
            border-radius: 50%; display: flex; align-items: center;
            justify-content: center; margin: 0 auto 20px;
            font-size: 2rem; color: white;
        }
        .user-info h3 { color: var(--white); font-size: 1.8rem; margin-bottom: 10px; }
        .user-info p { color: rgba(255,255,255,0.9); margin-bottom: 5px; }

        /* NIM display khusus */
        .nim-highlight {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.4);
            border-radius: 12px; padding: 15px 25px;
            margin-bottom: 20px; text-align: center;
        }
        .nim-highlight .nim-number {
            font-size: 1.8rem; font-weight: 700;
            color: var(--accent-red); letter-spacing: 2px;
        }
        .nim-highlight small { display: block; color: rgba(255,255,255,0.8); font-size: 0.85rem; margin-top: 5px; }

        /* Action message box */
        .action-message {
            background: rgba(255,255,255,0.15);
            border-radius: 10px; padding: 12px 20px;
            margin-top: 15px; font-size: 0.95rem;
            color: rgba(255,255,255,0.95);
        }

        /* ===== HERO BUTTONS ===== */
        .btn-hero-primary {
            background-color: var(--accent-red); color: var(--dark-red);
            font-weight: 600; padding: 15px 35px; border-radius: 30px;
            font-size: 1.1rem; border: none; transition: all 0.3s; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-hero-primary:hover {
            background-color: var(--white);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255,138,128,0.4);
            color: var(--dark-red);
        }
        .btn-hero-secondary {
            background-color: transparent;
            border: 2px solid var(--white); color: var(--white);
            font-weight: 600; padding: 15px 35px; border-radius: 30px;
            font-size: 1.1rem; transition: all 0.3s; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-hero-secondary:hover { background-color: var(--white); color: var(--primary-red); }
        .btn-hero-secondary:disabled, .btn-hero-secondary[disabled] {
            opacity: 0.6; cursor: not-allowed; pointer-events: none;
        }
        .btn-hero-success {
            background-color: #4CAF50; color: var(--white);
            font-weight: 600; padding: 15px 35px; border-radius: 30px;
            font-size: 1.1rem; border: none; transition: all 0.3s; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-hero-success:hover {
            background-color: #388E3C; transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(76,175,80,0.4); color: white;
        }
        .btn-hero-warning {
            background-color: #ffc107; color: #333;
            font-weight: 600; padding: 15px 35px; border-radius: 30px;
            font-size: 1.1rem; border: none; transition: all 0.3s; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-hero-warning:hover {
            background-color: #e0a800; transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255,193,7,0.4); color: #333;
        }

        /* ===== SECTIONS ===== */
        .section-title { text-align: center; margin-bottom: 60px; }
        .section-title h2 {
            color: var(--dark-red); font-weight: 700; font-size: 2.8rem;
            position: relative; display: inline-block; padding-bottom: 15px;
        }
        .section-title h2::after {
            content: ''; position: absolute; width: 80px; height: 4px;
            background-color: var(--light-red); bottom: 0; left: 50%; transform: translateX(-50%);
        }
        .section-title p { color: var(--text-light); font-size: 1.1rem; max-width: 700px; margin: 20px auto 0; }

        /* ===== PRODI CARDS ===== */
        .prodi-card {
            background: var(--white); border-radius: 15px; padding: 30px; height: 100%;
            box-shadow: 0 10px 30px rgba(198,40,40,0.08);
            border: 2px solid transparent; transition: all 0.3s ease;
            position: relative; overflow: hidden;
        }
        .prodi-card::before {
            content: ''; position: absolute; top: 0; left: 0;
            width: 100%; height: 5px;
            background: linear-gradient(90deg, var(--primary-red), var(--light-red));
        }
        .prodi-card:hover { transform: translateY(-10px); box-shadow: 0 15px 40px rgba(198,40,40,0.15); border-color: var(--accent-red); }
        .prodi-icon {
            width: 70px; height: 70px;
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;
        }
        .prodi-icon i { font-size: 2rem; color: var(--primary-red); }
        .prodi-card h3 { color: var(--dark-red); font-weight: 600; margin-bottom: 15px; }
        .prodi-card p { color: var(--text-light); margin-bottom: 15px; }
        .kuota-badge {
            display: inline-block; background-color: #ffebee;
            color: var(--primary-red); padding: 5px 15px; border-radius: 20px;
            font-weight: 500; font-size: 0.9rem;
        }

        /* ===== TIMELINE ===== */
        .timeline-section { background-color: var(--bg-light); padding: 100px 0; }
        .timeline-container { position: relative; max-width: 1000px; margin: 0 auto; }
        .timeline-container::before {
            content: ''; position: absolute; left: 50%; top: 0; bottom: 0; width: 4px;
            background: linear-gradient(to bottom, var(--primary-red), var(--light-red));
            transform: translateX(-50%); z-index: 1;
        }
        .timeline-item { display: flex; margin-bottom: 60px; position: relative; z-index: 2; }
        .timeline-item:nth-child(odd) { justify-content: flex-start; }
        .timeline-item:nth-child(even) { justify-content: flex-end; }
        .timeline-content {
            background: var(--white); border-radius: 15px; padding: 30px;
            box-shadow: 0 10px 30px rgba(198,40,40,0.1);
            border-left: 5px solid var(--primary-red); width: 45%; position: relative;
        }
        .timeline-number {
            position: absolute; top: 50%; width: 60px; height: 60px;
            background: linear-gradient(135deg, var(--primary-red), var(--light-red));
            color: var(--white); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1.4rem;
            box-shadow: 0 5px 20px rgba(198,40,40,0.3);
            z-index: 3; transform: translateY(-50%);
        }
        .timeline-item:nth-child(even) .timeline-number { right: 425px; }
        .timeline-item:nth-child(odd) .timeline-number { left: -35px; }
        .timeline-content h3 { color: var(--dark-red); font-weight: 600; margin-bottom: 15px; font-size: 1.4rem; }
        .timeline-content p { color: var(--text-light); margin-bottom: 0; }

        /* ===== FAQ ===== */
        .faq-item {
            background: var(--white); border-radius: 10px; margin-bottom: 15px;
            overflow: hidden; box-shadow: 0 3px 15px rgba(198,40,40,0.05);
            border-left: 4px solid transparent; transition: all 0.3s;
        }
        .faq-item:hover { border-left-color: var(--primary-red); }
        .faq-question {
            padding: 20px; font-weight: 600; color: var(--dark-red);
            cursor: pointer; display: flex; justify-content: space-between; align-items: center;
        }
        .faq-answer { padding: 0 20px; max-height: 0; overflow: hidden; transition: max-height 0.3s, padding 0.3s; color: var(--text-light); }
        .faq-item.active .faq-answer { padding: 0 20px 20px 20px; max-height: 300px; }
        .faq-question i { transition: transform 0.3s; }
        .faq-item.active .faq-question i { transform: rotate(180deg); }

        /* ===== FOOTER ===== */
        .footer {
            background: linear-gradient(135deg, var(--dark-red), var(--primary-red));
            color: var(--white); padding: 70px 0 20px;
        }
        .footer h5 {
            color: var(--accent-red); font-weight: 600; margin-bottom: 25px;
            position: relative; padding-bottom: 10px;
        }
        .footer h5::after {
            content: ''; position: absolute; bottom: 0; left: 0;
            width: 40px; height: 3px; background-color: var(--accent-red);
        }
        .footer-links a { color: rgba(255,255,255,0.8); text-decoration: none; display: block; margin-bottom: 12px; transition: all 0.3s; }
        .footer-links a:hover { color: var(--accent-red); padding-left: 5px; }
        .contact-info i { color: var(--accent-red); margin-right: 10px; width: 20px; }
        .copyright { text-align: center; padding-top: 30px; margin-top: 50px; border-top: 1px solid rgba(255,255,255,0.1); color: rgba(255,255,255,0.6); }

        /* ===== ANIMATIONS ===== */
        .fade-in-up { opacity: 0; transform: translateY(30px); animation: fadeInUp 0.8s ease forwards; }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
        .delay-1 { animation-delay: 0.2s; }
        .delay-2 { animation-delay: 0.4s; }
        .delay-3 { animation-delay: 0.6s; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .hero-title { font-size: 2.8rem; }
            .section-title h2 { font-size: 2.3rem; }
            .timeline-container::before { left: 30px; }
            .timeline-item { justify-content: flex-start !important; margin-left: 60px; margin-bottom: 40px; }
            .timeline-content { width: 100%; }
            .timeline-number { left: -75px !important; right: auto !important; top: 30px; transform: translateY(0); }
        }
        @media (max-width: 768px) {
            .hero-title { font-size: 2.2rem; }
            .hero-subtitle { font-size: 1.1rem; }
            .section-title h2 { font-size: 2rem; }
            .navbar-nav { text-align: center; padding-top: 15px; }
            .btn-hero-primary, .btn-hero-secondary, .btn-hero-success, .btn-hero-warning {
                width: 100%; justify-content: center; margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap me-2"></i>PMB <span>Universitas Kita</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"><i class="fas fa-bars text-white"></i></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#home">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#prodi">Program Studi</a></li>
                    <li class="nav-item"><a class="nav-link" href="#alur">Alur Pendaftaran</a></li>
                    <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>

                    <?php if ($isLoggedIn && $userData): ?>
                        <li class="nav-item ms-2 profile-dropdown">
                            <div class="dropdown">
                                <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user-circle"></i>
                                    <?php echo htmlspecialchars(explode(' ', $userData['nama_lengkap'])[0]); ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="dashboard/dashboard_camaba.php">
                                            <i class="fas fa-tachometer-alt"></i> Dashboard
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="dashboard/profile.php">
                                            <i class="fas fa-user-edit"></i> Lihat Profil
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="dashboard/ubah_password.php">
                                            <i class="fas fa-key"></i> Ubah Password
                                        </a>
                                    </li>
                                    <?php if ($userData['status'] == 'aktif' && $jadwalUjian): ?>
                                    <li>
                                        <a class="dropdown-item" href="tes_ujian/ujian_token.php">
                                            <i class="fas fa-pencil-alt"></i> Ikuti Ujian
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($userData['status'] == 'lulus' || $userData['status'] == 'gagal'): ?>
                                    <li>
                                        <a class="dropdown-item" href="tes_ujian/hasil_ujian.php">
                                            <i class="fas fa-chart-bar"></i> Lihat Hasil
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($userData['status'] == 'daftar_ulang'): ?>
                                    <li>
                                        <a class="dropdown-item" href="daftar_ulang.php">
                                            <i class="fas fa-clipboard-check"></i> Daftar Ulang
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($userData['status'] == 'selected' && ($userData['status_bayar'] ?? '') == 'belum'): ?>
                                    <li>
                                        <a class="dropdown-item" href="https://wa.me/6281234567890?text=Halo%20Admin%2C%20saya%20<?php echo urlencode($userData['nama_lengkap']); ?>%20(Nomor%20Tes%3A%20<?php echo urlencode($userData['nomor_tes']); ?>)%20ingin%20melakukan%20konfirmasi%20pembayaran." target="_blank">
                                            <i class="fab fa-whatsapp"></i> Hubungi Admin
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if (canShowNim($userData)): ?>
                                    <li>
                                        <a class="dropdown-item" href="kartu_mahasiswa.php">
                                            <i class="fas fa-id-card"></i> Kartu Mahasiswa
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="config/logout.php">
                                            <i class="fas fa-sign-out-alt"></i> Logout
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    <?php else: ?>
                        <li class="nav-item ms-2">
                            <a class="btn btn-login" href="gate/login/login.php">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ===== HERO SECTION ===== -->
    <section class="hero-section" id="home">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">

                    <?php if ($isLoggedIn && $userData): ?>
                        <!-- Welcome Card untuk User Login -->
                        <div class="welcome-user fade-in-up">
                            <div class="user-avatar">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="user-info">
                                <h3>Selamat Datang, <?php echo htmlspecialchars($userData['nama_lengkap']); ?>!</h3>
                                <p>Nomor Tes: <strong><?php echo htmlspecialchars($userData['nomor_tes'] ?? '-'); ?></strong></p>
                                <?php if (!empty($userData['prodi_pilihan'])): ?>
                                <p>Program Studi: <strong><?php echo htmlspecialchars($userData['prodi_pilihan']); ?></strong></p>
                                <?php endif; ?>
                                <p>
                                    Status: <span class="badge bg-secondary"><?php echo getStatusLabel($userData['status'], $userData['status_bayar'] ?? null); ?></span>
                                </p>

                                <?php if (canShowNim($userData)): ?>
                                <div class="nim-highlight mt-3">
                                    <div class="nim-number"><i class="fas fa-graduation-cap me-2"></i><?php echo $userData['nim']; ?></div>
                                    <small>Nomor Induk Mahasiswa — Anda resmi menjadi mahasiswa baru!</small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="hero-buttons fade-in-up delay-1">

                            <?php
                            // Render tombol utama dari getActionButton()
                            $btnClass = match($actionButton['type']) {
                                'primary'   => 'btn-hero-primary',
                                'success'   => 'btn-hero-success',
                                'warning'   => 'btn-hero-warning',
                                default     => 'btn-hero-secondary',
                            };
                            $isWa = strpos($actionButton['button']['link'], 'wa.me') !== false;
                            $target = $isWa ? '_blank' : '_self';
                            ?>

                            <?php if ($actionButton['button']['disabled']): ?>
                                <button class="btn-hero-secondary me-3 mb-3" disabled>
                                    <i class="<?php echo $actionButton['button']['icon']; ?> me-2"></i>
                                    <?php echo htmlspecialchars($actionButton['button']['text']); ?>
                                </button>
                            <?php else: ?>
                                <a href="<?php echo htmlspecialchars($actionButton['button']['link']); ?>"
                                   class="<?php echo $btnClass; ?> me-3 mb-3"
                                   target="<?php echo $target; ?>">
                                    <i class="<?php echo $actionButton['button']['icon']; ?> me-2"></i>
                                    <?php echo htmlspecialchars($actionButton['button']['text']); ?>
                                </a>
                            <?php endif; ?>

                            <!-- Tombol Dashboard selalu tampil -->
                            <?php if ($userData['status'] !== 'not_selected' && !canShowNim($userData)): ?>
                            <a href="dashboard/dashboard_camaba.php" class="btn-hero-secondary mb-3">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard Saya
                            </a>
                            <?php endif; ?>

                            <!-- Pesan informasi status -->
                            <?php if (!empty($actionButton['message'])): ?>
                            <div class="action-message">
                                <i class="fas fa-info-circle me-2"></i><?php echo htmlspecialchars($actionButton['message']); ?>
                            </div>
                            <?php endif; ?>

                        </div>

                    <?php else: ?>
                        <!-- Hero default untuk tamu -->
                        <h1 class="hero-title fade-in-up">Selamat Datang di Penerimaan Mahasiswa Baru</h1>
                        <p class="hero-subtitle fade-in-up delay-1">
                            Bergabunglah dengan komunitas akademik terbaik dan wujudkan impian Anda
                            untuk menjadi profesional yang kompeten di bidangnya.
                        </p>
                        <div class="hero-buttons fade-in-up delay-2">
                            <a href="gate/register/register.php" class="btn-hero-primary me-3 mb-3">
                                <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                            </a>
                            <a href="#alur" class="btn-hero-secondary mb-3">
                                <i class="fas fa-list-ol me-2"></i>Lihat Alur Pendaftaran
                            </a>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </section>

    <!-- ===== PROGRAM STUDI ===== -->
    <section class="py-5" id="prodi">
        <div class="container py-5">
            <div class="section-title">
                <h2>Program Studi Tersedia</h2>
                <p>Pilih program studi yang sesuai dengan minat dan bakat Anda</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="prodi-card fade-in-up">
                        <div class="prodi-icon"><i class="fas fa-laptop-code"></i></div>
                        <h3>Teknik Informatika</h3>
                        <p>Belajar pemrograman, jaringan komputer, kecerdasan buatan, dan pengembangan software.</p>
                        <div class="kuota-badge"><i class="fas fa-users me-1"></i> Kuota: 120 mahasiswa</div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="prodi-card fade-in-up delay-1">
                        <div class="prodi-icon"><i class="fas fa-database"></i></div>
                        <h3>Sistem Informasi</h3>
                        <p>Integrasi teknologi informasi dengan proses bisnis untuk efisiensi organisasi.</p>
                        <div class="kuota-badge"><i class="fas fa-users me-1"></i> Kuota: 100 mahasiswa</div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="prodi-card fade-in-up delay-2">
                        <div class="prodi-icon"><i class="fas fa-chart-line"></i></div>
                        <h3>Manajemen</h3>
                        <p>Mempelajari pengelolaan organisasi, strategi bisnis, dan kepemimpinan.</p>
                        <div class="kuota-badge"><i class="fas fa-users me-1"></i> Kuota: 150 mahasiswa</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== ALUR PENDAFTARAN ===== -->
    <section class="timeline-section" id="alur">
        <div class="container">
            <div class="section-title">
                <h2>Alur Pendaftaran</h2>
                <p>Ikuti langkah-langkah berikut untuk menjadi mahasiswa di Universitas Kita</p>
            </div>
            <div class="timeline-container">
                <div class="timeline-item fade-in-up">
                    <div class="timeline-content">
                        <h3>Registrasi Akun</h3>
                        <p>Buat akun dengan mengisi form registrasi online. Pastikan email dan nomor HP aktif.</p>
                    </div>
                    <div class="timeline-number">1</div>
                </div>
                <div class="timeline-item fade-in-up delay-1">
                    <div class="timeline-content">
                        <h3>Verifikasi & Aktivasi</h3>
                        <p>Akun akan diverifikasi dan diaktivasi oleh admin. Mohon tunggu konfirmasi via email.</p>
                    </div>
                    <div class="timeline-number">2</div>
                </div>
                <div class="timeline-item fade-in-up delay-2">
                    <div class="timeline-content">
                        <h3>Ujian Online</h3>
                        <p>Ikuti ujian seleksi online sesuai jadwal yang ditentukan. Durasi 90 menit.</p>
                    </div>
                    <div class="timeline-number">3</div>
                </div>
                <div class="timeline-item fade-in-up delay-1">
                    <div class="timeline-content">
                        <h3>Pengumuman Hasil</h3>
                        <p>Hasil ujian akan diumumkan maksimal 3 hari setelah ujian melalui website dan email.</p>
                    </div>
                    <div class="timeline-number">4</div>
                </div>
                <div class="timeline-item fade-in-up delay-2">
                    <div class="timeline-content">
                        <h3>Pembayaran & Daftar Ulang</h3>
                        <p>Jika lulus, hubungi admin via WhatsApp untuk proses pembayaran dan daftar ulang.</p>
                    </div>
                    <div class="timeline-number">5</div>
                </div>
                <div class="timeline-item fade-in-up">
                    <div class="timeline-content">
                        <h3>Terima NIM</h3>
                        <p>Setelah pembayaran terverifikasi, Anda mendapatkan Nomor Induk Mahasiswa (NIM).</p>
                    </div>
                    <div class="timeline-number">6</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FAQ ===== -->
    <section class="py-5" id="faq">
        <div class="container py-5">
            <div class="section-title">
                <h2>Pertanyaan Umum</h2>
                <p>Temukan jawaban atas pertanyaan yang sering diajukan</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>Apakah pendaftaran dipungut biaya?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Pendaftaran sepenuhnya GRATIS. Tidak ada biaya apapun sampai Anda dinyatakan lulus dan melakukan daftar ulang.</p>
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>Berapa lama waktu ujian online?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Ujian berlangsung selama 90 menit dengan soal pilihan ganda. Pastikan koneksi internet stabil.</p>
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>Bolehkah ganti prodi setelah mendaftar?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Perubahan prodi hanya bisa dilakukan sebelum ujian. Setelah ujian, pilihan prodi tidak dapat diubah.</p>
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>Bagaimana cara melakukan daftar ulang?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Jika dinyatakan lulus, Anda akan dihubungi atau bisa menghubungi admin via WhatsApp untuk informasi pembayaran dan proses daftar ulang lebih lanjut.</p>
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>Kapan NIM diterbitkan?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>NIM akan diterbitkan setelah pembayaran daftar ulang Anda terverifikasi oleh admin.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <h5>PMB Universitas Kita</h5>
                    <p class="mb-4" style="color: rgba(255,255,255,0.8);">
                        Portal resmi penerimaan mahasiswa baru tahun akademik 2026/2027.
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
                        <a href="#home">Beranda</a>
                        <a href="#prodi">Program Studi</a>
                        <a href="#alur">Alur Pendaftaran</a>
                        <a href="#faq">FAQ</a>
                        <a href="gate/login/login.php">Login</a>
                        <a href="gate/register/register.php">Daftar</a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5>Program Studi</h5>
                    <div class="footer-links">
                        <a href="#">Teknik Informatika</a>
                        <a href="#">Sistem Informasi</a>
                        <a href="#">Manajemen</a>
                        <a href="#">Akuntansi</a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5>Kontak Kami</h5>
                    <div class="contact-info">
                        <p class="mb-3"><i class="fas fa-map-marker-alt"></i> Jl. Pendidikan No. 123, Kota Kita</p>
                        <p class="mb-3"><i class="fas fa-phone"></i> (021) 1234-5678</p>
                        <p class="mb-3"><i class="fas fa-envelope"></i> pmb@universitaskita.ac.id</p>
                        <p class="mb-3"><i class="fab fa-whatsapp"></i> 0812-3456-7890</p>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2026 PMB Universitas Kita. Semua hak dilindungi.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="config/backend-index/script.js"></script>
    <script>
        // FAQ toggle
        document.querySelectorAll('.faq-question').forEach(q => {
            q.addEventListener('click', () => {
                const item = q.parentElement;
                item.classList.toggle('active');
            });
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) target.scrollIntoView({ behavior: 'smooth' });
            });
        });
    </script>
</body>
</html>