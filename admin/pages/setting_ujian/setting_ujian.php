<?php


// Buat tabel jika belum ada
$create_table = "CREATE TABLE IF NOT EXISTS ujian_setting (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    nama_ujian VARCHAR(100),
    tanggal_ujian DATE,
    jam_mulai TIME,
    jam_selesai TIME,
    durasi_menit INT(10) UNSIGNED DEFAULT 120,
    token_ujian VARCHAR(6),
    status ENUM('pending', 'active', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";

$conn->query($create_table);

// Ambil setting terbaru
$setting = $conn->query("SELECT * FROM ujian_setting ORDER BY id DESC LIMIT 1")->fetch_assoc();

// Handle form submission untuk menyimpan setting (NON-AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['ajax_action'])) {
    $nama_ujian = $conn->real_escape_string($_POST['nama_ujian'] ?? '');
    $tanggal_ujian = $conn->real_escape_string($_POST['tanggal_ujian'] ?? '');
    $jam_mulai = $conn->real_escape_string($_POST['jam_mulai'] ?? '');
    $jam_selesai = $conn->real_escape_string($_POST['jam_selesai'] ?? '');
    $durasi_menit = intval($_POST['durasi_menit'] ?? 120);
    $manual_token = isset($_POST['manual_token']) ? strtoupper($conn->real_escape_string($_POST['manual_token'])) : '';
    $auto_send_token = isset($_POST['auto_send_token']) ? 1 : 0;
    
    // Validasi input
    $errors = [];
    
    if (empty($nama_ujian)) $errors[] = "Nama ujian harus diisi";
    if (empty($tanggal_ujian)) $errors[] = "Tanggal ujian harus diisi";
    if (empty($jam_mulai)) $errors[] = "Jam mulai harus diisi";
    if (empty($jam_selesai)) $errors[] = "Jam selesai harus diisi";
    if ($durasi_menit < 1) $errors[] = "Durasi ujian minimal 1 menit";
    
    if (!empty($manual_token) && !preg_match('/^[A-Z0-9]{6}$/', $manual_token)) {
        $errors[] = "Token harus terdiri dari 6 karakter huruf kapital atau angka";
    }
    
    if (!empty($jam_mulai) && !empty($jam_selesai)) {
        $start_time = strtotime($jam_mulai);
        $end_time = strtotime($jam_selesai);
        
        if ($end_time <= $start_time) {
            $errors[] = "Jam selesai harus lebih besar dari jam mulai";
        }
        
        $diff_minutes = ($end_time - $start_time) / 60;
        if ($durasi_menit > $diff_minutes) {
            $errors[] = "Durasi ujian ($durasi_menit menit) tidak boleh lebih lama dari rentang waktu ujian (" . intval($diff_minutes) . " menit)";
        }
    }
    
    if (empty($errors)) {
        // Generate token jika belum ada atau kosong
        $token = $manual_token;
        if (empty($token)) {
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $token = '';
            for ($i = 0; $i < 6; $i++) {
                $token .= $chars[rand(0, strlen($chars) - 1)];
            }
        }
        
        // Cek apakah ada ujian aktif
        $check_active = $conn->query("SELECT * FROM ujian_setting WHERE status = 'active' LIMIT 1");
        if ($check_active->num_rows > 0 && empty($setting['id'])) {
            $_SESSION['error'] = "Tidak dapat membuat setting baru saat ada ujian aktif. Hentikan dulu ujian yang aktif.";
            header('Location: index.php?page=setting_ujian');
            exit();
        }
        
        // Simpan ke database
        if (isset($setting['id'])) {
            $sql = "UPDATE ujian_setting SET 
                    nama_ujian = ?, 
                    tanggal_ujian = ?, 
                    jam_mulai = ?, 
                    jam_selesai = ?, 
                    durasi_menit = ?, 
                    token_ujian = ? 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssisi", $nama_ujian, $tanggal_ujian, $jam_mulai, $jam_selesai, $durasi_menit, $token, $setting['id']);
        } else {
            $sql = "INSERT INTO ujian_setting (nama_ujian, tanggal_ujian, jam_mulai, jam_selesai, durasi_menit, token_ujian) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssis", $nama_ujian, $tanggal_ujian, $jam_mulai, $jam_selesai, $durasi_menit, $token);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Setting ujian berhasil disimpan! Token: <strong>$token</strong>";
            
            if ($auto_send_token) {
                $_SESSION['success'] .= "<br>Email akan dikirim ke peserta.";
            }
        } else {
            $_SESSION['error'] = "Gagal menyimpan setting ujian: " . $stmt->error;
        }
        $stmt->close();
        
        header('Location: index.php?page=setting_ujian');
        exit();
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
        header('Location: index.php?page=setting_ujian');
        exit();
    }
}
?>

<!-- HANYA HTML, TIDAK ADA LOGIC PHP LAGI -->
<div class="container-fluid py-4">
    <div class="row">
        <!-- Form Setting Ujian (Kiri) -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cog me-2"></i>Setting Waktu Ujian
                    </h5>
                    <p class="mb-0 text-white-50">Atur jadwal ujian online</p>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $_SESSION['success']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $_SESSION['error']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    
                    <form method="POST" action="index.php?page=setting_ujian" id="settingForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nama_ujian" class="form-label">
                                    <i class="fas fa-file-signature me-1"></i>Nama Ujian *
                                </label>
                                <input type="text" class="form-control" id="nama_ujian" name="nama_ujian" 
                                       value="<?php echo htmlspecialchars($setting['nama_ujian'] ?? 'Ujian Masuk Universitas Kita'); ?>" 
                                       placeholder="Contoh: Ujian Masuk Gelombang 1" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="tanggal_ujian" class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Tanggal Ujian *
                                </label>
                                <input type="date" class="form-control" id="tanggal_ujian" name="tanggal_ujian" 
                                       min="<?php echo date('Y-m-d'); ?>"
                                       value="<?php echo $setting['tanggal_ujian'] ?? date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="jam_mulai" class="form-label">
                                    <i class="fas fa-clock me-1"></i>Jam Mulai *
                                </label>
                                <input type="time" class="form-control" id="jam_mulai" name="jam_mulai" 
                                       value="<?php echo $setting['jam_mulai'] ?? '07:00'; ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="jam_selesai" class="form-label">
                                    <i class="fas fa-clock me-1"></i>Jam Selesai *
                                </label>
                                <input type="time" class="form-control" id="jam_selesai" name="jam_selesai" 
                                       value="<?php echo $setting['jam_selesai'] ?? '09:00'; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="durasi_menit" class="form-label">
                                    <i class="fas fa-hourglass-half me-1"></i>Durasi Ujian (menit) *
                                </label>
                                <input type="number" class="form-control" id="durasi_menit" name="durasi_menit" 
                                       value="<?php echo (int)($setting['durasi_menit'] ?? 120); ?>" 
                                       min="30" max="300" required>
                                <div class="form-text">Minimal 30 menit, maksimal 300 menit (5 jam)</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-key me-1"></i>Token Ujian (6 karakter)
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="token_display" 
                                           value="<?php echo $setting['token_ujian'] ?? 'Belum dibuat'; ?>" 
                                           readonly style="font-weight: bold; letter-spacing: 2px;">
                                    <button type="button" class="btn btn-outline-primary" onclick="generateToken()" id="generateTokenBtn">
                                        <i class="fas fa-sync-alt me-1"></i>Generate
                                    </button>
                                </div>
                                <small class="text-muted">Token akan digunakan peserta untuk mengakses ujian</small>
                                <input type="hidden" name="manual_token" id="manual_token" value="<?php echo $setting['token_ujian'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="auto_send_token" name="auto_send_token" value="1">
                                <label class="form-check-label" for="auto_send_token">
                                    <i class="fas fa-envelope me-1"></i>Kirim token otomatis ke email peserta setelah disimpan
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" onclick="previewSchedule()">
                                <i class="fas fa-eye me-2"></i>Preview Jadwal
                            </button>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i>Simpan Setting
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Info Ujian Saat Ini (Kanan) -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Info Ujian Saat Ini
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($setting): ?>
                        <div class="mb-3">
                            <h6>Status: 
                                <span class="badge <?php 
                                    echo $setting['status'] == 'active' ? 'bg-success' : 
                                           ($setting['status'] == 'pending' ? 'bg-warning' : 'bg-secondary'); 
                                ?> fs-6">
                                    <?php echo strtoupper($setting['status']); ?>
                                </span>
                            </h6>
                        </div>
                        
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-file-signature text-primary me-2"></i>Nama Ujian:</span>
                                <strong class="text-end"><?php echo htmlspecialchars($setting['nama_ujian']); ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-calendar-alt text-primary me-2"></i>Tanggal:</span>
                                <strong><?php echo date('d/m/Y', strtotime($setting['tanggal_ujian'])); ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-clock text-primary me-2"></i>Jam:</span>
                                <strong><?php echo date('H:i', strtotime($setting['jam_mulai'])) . ' - ' . date('H:i', strtotime($setting['jam_selesai'])); ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-hourglass-half text-primary me-2"></i>Durasi:</span>
                                <strong><?php echo (int)$setting['durasi_menit']; ?> menit</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-key text-primary me-2"></i>Token:</span>
                                <code class="fs-5 fw-bold text-success"><?php echo $setting['token_ujian'] ?? '-'; ?></code>
                            </li>
                        </ul>
                        
                        <div class="mt-4">
                            <?php if ($setting['status'] != 'active'): ?>
                                <button class="btn btn-success w-100 mb-2" onclick="startUjian()" id="startBtn">
                                    <i class="fas fa-play me-2"></i>Mulai Ujian
                                </button>
                            <?php else: ?>
                                <button class="btn btn-warning w-100 mb-2" onclick="stopUjian()" id="stopBtn">
                                    <i class="fas fa-stop me-2"></i>Stop Ujian
                                </button>
                            <?php endif; ?>
                            
                            <button class="btn btn-danger w-100" onclick="resetUjian()" id="resetBtn">
                                <i class="fas fa-redo me-2"></i>Reset Semua Ujian
                            </button>
                        </div>
                        
                        <?php if ($setting['status'] == 'active'): ?>
                            <div class="mt-4 pt-3 border-top">
                                <h6><i class="fas fa-chart-bar me-2"></i>Statistik Live:</h6>
                                <div class="row text-center mt-3">
                                    <div class="col-6">
                                        <div class="stat-number text-primary" id="activeCount">0</div>
                                        <small class="text-muted">Sedang Ujian</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="stat-number text-success" id="completedCount">0</div>
                                        <small class="text-muted">Selesai</small>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Belum ada setting ujian. Silakan buat setting baru.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Base URL untuk AJAX
const baseUrl = window.location.pathname.replace(/\/[^\/]*$/, '/ajax_handler.php');

// Fungsi untuk generate token 6 karakter
function generateToken() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let token = '';
    for (let i = 0; i < 6; i++) {
        token += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    
    document.getElementById('token_display').value = token;
    document.getElementById('manual_token').value = token;
    
    const btn = document.getElementById('generateTokenBtn');
    btn.innerHTML = `<i class="fas fa-check me-1"></i>${token}`;
    btn.classList.remove('btn-outline-primary');
    btn.classList.add('btn-success');
    
    showToast(`Token berhasil digenerate: ${token}`, 'success');
}

function previewSchedule() {
    const nama = document.getElementById('nama_ujian').value;
    const tanggal = document.getElementById('tanggal_ujian').value;
    const mulai = document.getElementById('jam_mulai').value;
    const selesai = document.getElementById('jam_selesai').value;
    const durasi = document.getElementById('durasi_menit').value;
    const token = document.getElementById('token_display').value;
    
    if (!nama || !tanggal || !mulai || !selesai) {
        showToast('Harap isi semua field yang wajib diisi!', 'error');
        return;
    }
    
    const message = `
    <div class="text-start">
        <h5 class="mb-3">📋 Preview Jadwal Ujian</h5>
        <table class="table table-sm">
            <tr><td><strong>Nama Ujian:</strong></td><td>${nama}</td></tr>
            <tr><td><strong>Tanggal:</strong></td><td>${tanggal}</td></tr>
            <tr><td><strong>Waktu:</strong></td><td>${mulai} - ${selesai}</td></tr>
            <tr><td><strong>Durasi Ujian:</strong></td><td>${durasi} menit</td></tr>
            <tr><td><strong>Token:</strong></td><td><code class="fs-5">${token}</code></td></tr>
        </table>
    </div>`;
    
    alert(message);
}

function startUjian() {
    if (confirm('Mulai ujian sekarang? Token akan aktif dan peserta dapat mengikuti ujian.')) {
        const startBtn = document.getElementById('startBtn');
        const originalText = startBtn.innerHTML;
        startBtn.disabled = true;
        startBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memulai...';
        
        fetch(`${baseUrl}?page=setting_ujian&ajax_action=start_ujian&t=${Date.now()}`)
            .then(response => {
                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    return response.text().then(text => {
                        console.error('Non-JSON response:', text.substring(0, 500));
                        throw new Error(`Response is not JSON`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Start response:', data);
                if (data.success) {
                    showToast(`✅ Ujian berhasil dimulai!<br>Token: <strong>${data.token}</strong><br>Siap untuk ${data.count} peserta.`, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('❌ ' + data.message, 'error');
                    startBtn.disabled = false;
                    startBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showToast('❌ Gagal memulai ujian: ' + error.message, 'error');
                startBtn.disabled = false;
                startBtn.innerHTML = originalText;
            });
    }
}

function stopUjian() {
    if (confirm('Hentikan ujian sekarang? Semua peserta akan dikeluarkan dan nilai akan dihitung otomatis.')) {
        const stopBtn = document.getElementById('stopBtn');
        const originalText = stopBtn.innerHTML;
        stopBtn.disabled = true;
        stopBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menghentikan...';
        
        fetch(`${baseUrl}?page=setting_ujian&ajax_action=stop_ujian&t=${Date.now()}`)
            .then(response => {
                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    return response.text().then(text => {
                        console.error('Non-JSON response:', text.substring(0, 500));
                        throw new Error(`Response is not JSON`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Stop response:', data);
                if (data.success) {
                    showToast('✅ Ujian dihentikan!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('❌ ' + data.message, 'error');
                    stopBtn.disabled = false;
                    stopBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showToast('❌ Gagal menghentikan ujian: ' + error.message, 'error');
                stopBtn.disabled = false;
                stopBtn.innerHTML = originalText;
            });
    }
}

function resetUjian() {
    if (confirm('⚠️ PERINGATAN!\n\nReset semua data ujian? Tindakan ini akan:\n1. Reset status semua peserta ke "baru"\n2. Hapus semua log ujian\n3. Reset semua nilai\n\nTindakan ini tidak dapat dibatalkan!')) {
        const resetBtn = document.getElementById('resetBtn');
        const originalText = resetBtn.innerHTML;
        resetBtn.disabled = true;
        resetBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Resetting...';
        
        fetch(`${baseUrl}?page=setting_ujian&ajax_action=reset_ujian&t=${Date.now()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('✅ Semua data ujian telah direset!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('❌ ' + data.message, 'error');
                    resetBtn.disabled = false;
                    resetBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                showToast('❌ Gagal reset ujian: ' + error.message, 'error');
                resetBtn.disabled = false;
                resetBtn.innerHTML = originalText;
            });
    }
}

// Live stats untuk ujian aktif
let liveStatsInterval;

function startLiveStats() {
    if (liveStatsInterval) clearInterval(liveStatsInterval);
    
    liveStatsInterval = setInterval(() => {
        fetch(`${baseUrl}?page=setting_ujian&ajax_action=get_ujian_status&t=${Date.now()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('activeCount').textContent = data.stats.active;
                    document.getElementById('completedCount').textContent = data.stats.completed;
                }
            })
            .catch(() => {
                // Silent fail
            });
    }, 3000);
}

function stopLiveStats() {
    if (liveStatsInterval) {
        clearInterval(liveStatsInterval);
        liveStatsInterval = null;
    }
}

// Toast notification
function showToast(message, type = 'info') {
    // Simple alert untuk testing
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Auto start live stats jika ujian aktif
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($setting) && $setting['status'] == 'active'): ?>
    startLiveStats();
    <?php endif; ?>
});
</script>