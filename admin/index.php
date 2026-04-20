<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
ob_start(); // Start output buffering

require_once '../config/database.php';

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$base_dir = dirname($_SERVER['PHP_SELF']);
define('BASE_URL', $base_url . $base_dir);
define('BASE_DIR', __DIR__);

// Cek apakah user sudah login sebagai admin
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../gate/login/login.php");
    exit();
}

// Mengatur halaman default
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = ['dashboard', 'camaba', 'soal', 'pendaftar', 'hasil', 'daftar_ulang', 'ujian', 'setting_ujian'];
// Di bagian router sebelum include halaman:
if ($page == 'soal') {
    if (isset($_GET['action'])) {
        $action = $_GET['action'];

        // Mapping action ke file
        $action_files = [
            'tambah' => 'tambah_soal.php',
            'edit' => 'edit_soal.php',
            'duplicate' => 'duplicate_soal.php'
        ];

        if (isset($action_files[$action])) {
            $content_file = "pages/soal/" . $action_files[$action];

            // Cek apakah file exist
            if (!file_exists($content_file)) {
                die("<div class='alert alert-danger'>File <strong>$content_file</strong> tidak ditemukan!</div>");
            }
        } else {
            // Default ke list soal
            $content_file = "pages/soal/soal.php";
        }
    } else {
        $content_file = "pages/soal/soal.php";
    }
} else {
    // Untuk halaman lain, gunakan routing biasa
    $content_file = "pages/{$page}/{$page}.php";
}

if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

// Jika ini AJAX request untuk setting_ujian, langsung include handler
if (isset($_GET['ajax_action']) && $page == 'setting_ujian') {
    include('ajax_handler.php');
    exit();
}

// Jika ini AJAX request untuk ujian, langsung include handler
if (isset($_GET['action']) && $page == 'ujian') {
    include('ujian_ajax_handler.php');
    exit();
}

// Mengambil data admin
$admin_id = $_SESSION['admin_id'];
$sql = "SELECT * FROM admins WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin_data = $result->fetch_assoc();
$stmt->close();

// Title berdasarkan halaman
$page_titles = [
    'dashboard' => 'Dashboard',
    'camaba' => 'Data Calon Mahasiswa',
    'soal' => 'Data Soal Tes',
    'pendaftar' => 'Data Pendaftar',
    'hasil' => 'Hasil Tes',
    'daftar_ulang' => 'Daftar Ulang',
    'ujian' => 'Kontrol Ujian Live',
    'setting_ujian' => 'Setting Waktu Ujian'
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin -<?php echo $page_titles[$page]; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <style>
        :root {
            --sidebar-width: 250px;
            --primary-blue: #0d6efd;
            --secondary-blue: #0dcaf0;
            --dark-blue: #0a58ca;
            --light-blue: #e7f1ff;
            --sidebar-bg: #1e293b;
            --sidebar-hover: #334155;
            --content-bg: #f8fafc;
        }

        * {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--content-bg);
            overflow-x: hidden;
        }

        /* SIDEBAR */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            color: white;
            transition: all 0.3s;
            z-index: 1000;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 20px 15px;
            border-bottom: 1px solid #334155;
            text-align: center;
            background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
        }

        .sidebar-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .admin-info {
            padding: 20px 15px;
            border-bottom: 1px solid #334155;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary-blue), var(--primary-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .admin-details h5 {
            font-size: 1rem;
            margin-bottom: 5px;
        }

        .admin-details p {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .sidebar-menu {
            padding: 20px 0;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }

        .sidebar-menu::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar-menu::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 10px;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            color: #cbd5e1;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
            text-decoration: none;
            border-left: 3px solid transparent;
        }

        .nav-link:hover {
            color: white;
            background-color: var(--sidebar-hover);
            border-left-color: var(--secondary-blue);
        }

        .nav-link.active {
            color: white;
            background-color: var(--sidebar-hover);
            border-left-color: var(--primary-blue);
        }

        .nav-icon {
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 15px;
            border-top: 1px solid #334155;
        }

        .logout-btn {
            width: 100%;
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        /* MAIN CONTENT */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s;
        }

        /* HEADER CONTENT */
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .page-title h2 {
            color: var(--dark-blue);
            font-weight: 700;
            margin-bottom: 5px;
        }

        .page-title p {
            color: #64748b;
            font-size: 0.9rem;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn-custom {
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
            border: none;
            color: white;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }

        /* CARD STATS */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon-1 {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .stat-icon-2 {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .stat-icon-3 {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }

        .stat-icon-4 {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* DATA TABLE */
        .data-table-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-title h3 {
            color: #1e293b;
            font-weight: 600;
        }

        .search-box {
            position: relative;
            width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table-custom {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-custom thead {
            background-color: #f1f5f9;
        }

        .table-custom th {
            padding: 15px;
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
        }

        .table-custom td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
        }

        .table-custom tbody tr:hover {
            background-color: #f8fafc;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn-action {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            color: white;
            transition: all 0.3s;
        }

        .btn-edit {
            background-color: var(--primary-blue);
        }

        .btn-delete {
            background-color: #dc3545;
        }

        .btn-view {
            background-color: #10b981;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }

            .sidebar-header h3,
            .admin-details,
            .nav-text,
            .logout-btn span {
                display: none;
            }

            .admin-info {
                justify-content: center;
            }

            .nav-link {
                justify-content: center;
            }

            .main-content {
                margin-left: 70px;
            }

            .search-box {
                width: 200px;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                margin-left: 0;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .mobile-menu-toggle {
                display: block;
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1001;
                background: var(--primary-blue);
                color: white;
                border: none;
                width: 40px;
                height: 40px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle d-none" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>PMB Admin</h3>
            <p>Universitas Kita</p>
        </div>

        <div class="admin-info">
            <div class="admin-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="admin-details">
                <h5><?php echo htmlspecialchars($admin_data['nama_lengkap'] ?? 'Admin'); ?></h5>
                <p><?php echo htmlspecialchars($admin_data['noa'] ?? 'ADMIN'); ?></p>
            </div>
        </div>

        <div class="sidebar-menu">
            <nav class="nav flex-column">
                <div class="nav-item">
                    <a href="?page=dashboard" class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                        <div class="nav-icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </div>

                <div class="nav-item">
                    <a href="?page=camaba" class="nav-link <?php echo $page === 'camaba' ? 'active' : ''; ?>">
                        <div class="nav-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="nav-text">Data Camaba</span>
                    </a>
                </div>

                <div class="nav-item">
                    <a href="?page=soal" class="nav-link <?php echo $page === 'soal' ? 'active' : ''; ?>">
                        <div class="nav-icon">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <span class="nav-text">Data Soal</span>
                    </a>
                </div>

                <div class="nav-item">
                    <a href="?page=pendaftar" class="nav-link <?php echo $page === 'pendaftar' ? 'active' : ''; ?>">
                        <div class="nav-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <span class="nav-text">Data Pendaftar</span>
                    </a>
                </div>

                <!-- MENU BARU UNTUK UJIAN -->
                <div class="nav-item">
                    <a href="?page=setting_ujian"
                        class="nav-link <?php echo $page === 'setting_ujian' ? 'active' : ''; ?>">
                        <div class="nav-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <span class="nav-text">Setting Ujian</span>
                    </a>
                </div>

                <div class="nav-item">
                    <a href="?page=ujian" class="nav-link <?php echo $page === 'ujian' ? 'active' : ''; ?>">
                        <div class="nav-icon">
                            <i class="fas fa-desktop"></i>
                        </div>
                        <span class="nav-text">Kontrol Ujian</span>
                    </a>
                </div>

                <div class="nav-item">
                    <a href="?page=hasil" class="nav-link <?php echo $page === 'hasil' ? 'active' : ''; ?>">
                        <div class="nav-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <span class="nav-text">Hasil Tes</span>
                    </a>
                </div>

                <div class="nav-item">
                    <a href="?page=daftar_ulang"
                        class="nav-link <?php echo $page === 'daftar_ulang' ? 'active' : ''; ?>">
                        <div class="nav-icon">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <span class="nav-text">Daftar Ulang</span>
                    </a>
                </div>
            </nav>
        </div>

        <div class="sidebar-footer">
            <form action="../config/logout.php" method="POST">
                <button type="submit" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="mainContent">
        <!-- Content Header -->
        <div class="content-header">
            <div class="page-title">
                <h2><?php echo $page_titles[$page]; ?></h2>
                <p><?php echo date('l, d F Y'); ?></p>
            </div>
            <div class="header-actions">
                <button class="btn btn-custom btn-primary-custom" id="printBtn">
                    <i class="fas fa-print me-2"></i>Print
                </button>
                <button class="btn btn-custom btn-primary-custom" id="exportBtn">
                    <i class="fas fa-file-export me-2"></i>Export
                </button>
            </div>
        </div>

        <!-- Page Content -->
        <div id="pageContent">
            <?php
            // Include konten berdasarkan routing yang sudah ditentukan
            if (file_exists($content_file)) {
                include($content_file);
            } else {
                echo "<div class='alert alert-danger'>Halaman tidak ditemukan: $content_file</div>";
            }
            ?>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // JavaScript sama seperti sebelumnya
    </script>
</body>

</html>