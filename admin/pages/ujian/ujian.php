<?php
date_default_timezone_set('Asia/Jakarta');

// Ambil data setting ujian
$setting = $conn->query("SELECT * FROM ujian_setting ORDER BY id DESC LIMIT 1")->fetch_assoc();

// AMBIL DATA PESERTA DARI UJIAN_LOG
if ($setting) {
    // Ambil data peserta dari ujian_log untuk ujian aktif ini
    $peserta = $conn->query("
        SELECT * FROM ujian_log 
        WHERE ujian_id = '{$setting['id']}'
        ORDER BY nama_lengkap
    ")->fetch_all(MYSQLI_ASSOC);
    
    // Statistik
    $total_peserta = count($peserta);
    $sedang_ujian = array_sum(array_map(function($p) {
        return $p['status'] == 'active' ? 1 : 0;
    }, $peserta));
    $selesai_ujian = array_sum(array_map(function($p) {
        return $p['status'] == 'completed' ? 1 : 0;
    }, $peserta));
} else {
    $peserta = [];
    $total_peserta = 0;
    $sedang_ujian = 0;
    $selesai_ujian = 0;
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="data-table-container mb-4">
            <div class="table-header">
                <div class="table-title">
                    <h3>Kontrol Ujian Live</h3>
                    <p class="mb-0">
                        <?php if($setting): ?>
                            Status: <span class="badge <?php echo $setting['status'] == 'active' ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo strtoupper($setting['status']); ?>
                            </span>
                            | Token: <code><?php echo $setting['token_ujian'] ?? '-'; ?></code>
                        <?php else: ?>
                            <span class="text-danger">Belum ada setting ujian!</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="header-actions">
                    <?php if($setting && $setting['status'] == 'active'): ?>
                        <button class="btn btn-danger me-2" onclick="stopUjian(event)">
                            <i class="fas fa-stop me-2"></i>Stop Ujian
                        </button>
                    <?php else: ?>
                        <button class="btn btn-success me-2" onclick="startUjian(event)">
                            <i class="fas fa-play me-2"></i>Mulai Ujian
                        </button>
                    <?php endif; ?>
                    <button class="btn btn-primary" onclick="sendTokens(event)">
                        <i class="fas fa-paper-plane me-2"></i>Kirim Token
                    </button>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <h1 class="display-4"><?php echo $total_peserta; ?></h1>
                            <p class="card-text">Total Peserta</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <h1 class="display-4"><?php echo $sedang_ujian; ?></h1>
                            <p class="card-text">Sedang Ujian</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <h1 class="display-4"><?php echo $selesai_ujian; ?></h1>
                            <p class="card-text">Selesai Ujian</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <div id="timerDisplay" class="display-4">00:00:00</div>
                            <p class="card-text">Waktu Tersisa</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Peserta</th>
                            <th>Nomor Tes</th>
                            <th>Status Ujian</th>
                            <th>Token</th>
                            <th>IP Address</th>
                            <th>Waktu Mulai</th>
                            <th>Waktu Sisa</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="pesertaTable">
                        <?php foreach($peserta as $key => $p): ?>
                        <tr id="peserta-<?php echo htmlspecialchars($p['camaba_id']); ?>">
                            <td><?php echo $key + 1; ?></td>
                            <td><?php echo htmlspecialchars($p['nama_lengkap']); ?></td>
                            <td><?php echo htmlspecialchars($p['camaba_id']); ?></td>
                            <td>
                                <?php 
                                $status_badge = '';
                                if ($p['status'] == 'active') {
                                    $status_badge = '<span class="badge bg-success">SEDANG UJIAN</span>';
                                } elseif ($p['status'] == 'completed') {
                                    $status_badge = '<span class="badge bg-secondary">SELESAI</span>';
                                } elseif ($p['status'] == 'expired') {
                                    $status_badge = '<span class="badge bg-danger">KADALUARSA</span>';
                                } else {
                                    $status_badge = '<span class="badge bg-warning">MENUNGGU</span>';
                                }
                                echo $status_badge;
                                ?>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($p['token_ujian'] ?? $setting['token_ujian'] ?? '-'); ?></code>
                            </td>
                            <td id="ip-<?php echo htmlspecialchars($p['camaba_id']); ?>">
                                <?php echo htmlspecialchars($p['ip_address'] ?? '-'); ?>
                            </td>
                            <td id="start-<?php echo htmlspecialchars($p['camaba_id']); ?>">
                                <?php echo $p['start_time'] ? date('H:i:    s', strtotime($p['start_time'])) : '-'; ?>
                            </td>
                            <td id="remaining-<?php echo htmlspecialchars($p['camaba_id']); ?>">
                                <?php 
                                // Hitung waktu sisa jika sedang ujian
                                if ($p['status'] == 'active' && $p['start_time'] && $setting) {
                                    $start_time = strtotime($p['start_time']);
                                    $durasi_menit = $setting['durasi_menit'] ?? 120;
                                    $end_time = $start_time + ($durasi_menit * 60);
                                    $remaining = $end_time - time();
                                    
                                    if ($remaining > 0) {
                                        $hours = floor($remaining / 3600);
                                        $minutes = floor(($remaining % 3600) / 60);
                                        $seconds = $remaining % 60;
                                        echo sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                                    } else {
                                        echo '00:00:00';
                                    }
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action btn-view" onclick="resetUjian('<?php echo htmlspecialchars($p['camaba_id']); ?>', event)" title="Reset">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                    <button class="btn-action btn-edit" onclick="sendTokenIndividu('<?php echo htmlspecialchars($p['camaba_id']); ?>', event)" title="Kirim Token">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript tetap sama seperti yang Anda punya, tidak perlu perubahan -->
<script>
// Base URL untuk AJAX ujian
const ujianAjaxUrl = window.location.pathname.replace(/\/[^\/]*$/, '/ujian_ajax_handler.php');
// Base URL untuk get waktu ujian
const timeApiUrl = window.location.pathname.replace(/\/[^\/]*$/, 'get_ujian_time.php');

// Global variables for timer
let timerInterval;
let countdownInterval;
let remainingSeconds = 0;

// Timer untuk ujian - Real-time countdown
function startTimer() {
    clearInterval(timerInterval);
    clearInterval(countdownInterval);
    
    // First, get initial time from server
    updateTimerFromServer();
    
    // Then update every second for smooth countdown
    countdownInterval = setInterval(() => {
        updateCountdown();
    }, 1000);
    
    // Also update from server every 30 seconds to stay in sync
    timerInterval = setInterval(() => {
        updateTimerFromServer();
    }, 30000);
}

function updateTimerFromServer() {
    fetch(`${timeApiUrl}?t=${Date.now()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Timer response:', data); // Debug log
            if (data.success && data.remaining) {
                // Parse time string to seconds
                const timeParts = data.remaining.split(':');
                if (timeParts.length === 3) {
                    remainingSeconds = (parseInt(timeParts[0]) * 3600) + 
                                      (parseInt(timeParts[1]) * 60) + 
                                      parseInt(timeParts[2]);
                    
                    // Update display immediately
                    document.getElementById('timerDisplay').textContent = data.remaining;
                    
                    // Update color
                    updateTimerColor();
                    
                    // If time is up, auto refresh
                    if (remainingSeconds <= 0) {
                        clearIntervals();
                        setTimeout(() => {
                            showNotification('⏰ Waktu ujian telah habis!', 'warning');
                            location.reload();
                        }, 2000);
                    }
                }
            } else if (!data.success) {
                // Exam is not active
                console.log('No active exam or timer error:', data.message);
                clearIntervals();
                document.getElementById('timerDisplay').textContent = '00:00:00';
                document.getElementById('timerDisplay').style.color = '#6c757d';
            }
        })
        .catch(error => {
            console.error('Error fetching timer:', error);
            // Jangan hentikan countdown lokal jika ada error
        });
}

function updateCountdown() {
    if (remainingSeconds > 0) {
        remainingSeconds--;
        
        // Update display timer
        const hours = Math.floor(remainingSeconds / 3600);
        const minutes = Math.floor((remainingSeconds % 3600) / 60);
        const seconds = remainingSeconds % 60;
        const formattedTime = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        document.getElementById('timerDisplay').textContent = formattedTime;
        
        // Update color
        updateTimerColor();
        
        // If time is up
        if (remainingSeconds <= 0) {
            clearIntervals();
            document.getElementById('timerDisplay').textContent = '00:00:00';
            showNotification('⏰ Waktu ujian telah habis!', 'warning');
            setTimeout(() => location.reload(), 3000);
        }
    }
}

function updateTimerColor() {
    // Change color based on remaining time
    if (remainingSeconds < 300) { // Less than 5 minutes
        document.getElementById('timerDisplay').style.color = '#dc3545';
    } else if (remainingSeconds < 600) { // Less than 10 minutes
        document.getElementById('timerDisplay').style.color = '#ffc107';
    } else {
        document.getElementById('timerDisplay').style.color = '#0d6efd';
    }
}

function clearIntervals() {
    clearInterval(timerInterval);
    clearInterval(countdownInterval);
}

// Helper functions
function showLoading(button, text = 'Memproses...') {
    if (!button) return null;
    
    const originalHTML = button.innerHTML;
    button.disabled = true;
    button.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>${text}`;
    button.style.opacity = '0.7';
    button.style.cursor = 'wait';
    
    return originalHTML;
}

function restoreButton(button, originalHTML) {
    if (button && originalHTML) {
        button.disabled = false;
        button.innerHTML = originalHTML;
        button.style.opacity = '1';
        button.style.cursor = 'pointer';
    }
}

function showNotification(message, type = 'success') {
    // Remove existing notifications
    const existingAlert = document.querySelector('.ajax-notification');
    if (existingAlert) existingAlert.remove();
    
    // Create new notification
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show ajax-notification position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px; animation: slideIn 0.3s ease;';
    
    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    else if (type === 'error') icon = 'exclamation-circle';
    else if (type === 'warning') icon = 'exclamation-triangle';
    
    alertDiv.innerHTML = `
        <i class="fas fa-${icon} me-2"></i>
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentElement) {
            alertDiv.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (alertDiv.parentElement) alertDiv.remove();
            }, 300);
        }
    }, 5000);
}

// Main functions with event handling
function startUjian(event) {
    // Get button from event
    let button = event?.target?.closest('button');
    
    // Fallback: find by class
    if (!button) button = document.querySelector('.header-actions .btn-success');
    
    if (!button || !confirm('Mulai ujian sekarang? Token akan dikirim ke semua peserta.')) {
        return;
    }
    
    const originalHTML = showLoading(button, 'Memulai ujian...');
    
    fetch(`${ujianAjaxUrl}?page=ujian&action=start&t=${Date.now()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(`✅ Ujian berhasil dimulai!<br>Token: <strong>${data.token || 'N/A'}</strong>`, 'success');
                
                // Show success state briefly
                button.innerHTML = '<i class="fas fa-check me-2"></i>Berhasil!';
                button.className = 'btn btn-success me-2';
                button.disabled = true;
                
                // Reload after delay
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(`❌ ${data.message}`, 'error');
                restoreButton(button, originalHTML);
            }
        })
        .catch(error => {
            console.error('Error starting ujian:', error);
            showNotification(`❌ Gagal memulai ujian: ${error.message}`, 'error');
            restoreButton(button, originalHTML);
        });
}

function stopUjian(event) {
    // Get button from event
    let button = event?.target?.closest('button');
    
    // Fallback: find by class
    if (!button) button = document.querySelector('.header-actions .btn-danger');
    
    if (!button || !confirm('Stop ujian? Semua peserta akan dikeluarkan dari sistem.')) {
        return;
    }
    
    const originalHTML = showLoading(button, 'Menghentikan ujian...');
    
    fetch(`${ujianAjaxUrl}?page=ujian&action=stop&t=${Date.now()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('✅ Ujian dihentikan!', 'success');
                
                // Show success state briefly
                button.innerHTML = '<i class="fas fa-check me-2"></i>Berhasil!';
                button.className = 'btn btn-success me-2';
                button.disabled = true;
                
                // Reload after delay
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(`❌ ${data.message}`, 'error');
                restoreButton(button, originalHTML);
            }
        })
        .catch(error => {
            console.error('Error stopping ujian:', error);
            showNotification(`❌ Gagal menghentikan ujian: ${error.message}`, 'error');
            restoreButton(button, originalHTML);
        });
}

function sendTokens(event) {
    // Get button from event
    let button = event?.target?.closest('button');
    
    // Fallback: find by class
    if (!button) button = document.querySelector('.header-actions .btn-primary');
    
    if (!button || !confirm('Kirim token ke semua peserta via email?')) {
        return;
    }
    
    const originalHTML = showLoading(button, 'Mengirim token...');
    
    fetch(`${ujianAjaxUrl}?page=ujian&action=send_tokens&t=${Date.now()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(`✅ Token berhasil dikirim ke ${data.count} peserta!`, 'success');
            } else {
                showNotification(`❌ ${data.message}`, 'error');
            }
            restoreButton(button, originalHTML);
        })
        .catch(error => {
            console.error('Error sending tokens:', error);
            showNotification(`❌ Gagal mengirim token: ${error.message}`, 'error');
            restoreButton(button, originalHTML);
        });
}

function resetUjian(camabaId, event) {
    if (!event || !confirm('Reset ujian untuk peserta ini?')) {
        return;
    }
    
    const button = event.target.closest('button');
    if (!button) return;
    
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    // Kirim camaba_id (yang sebenarnya adalah nomor_tes)
    fetch(`${ujianAjaxUrl}?page=ujian&action=reset&camaba_id=${camabaId}&t=${Date.now()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('✅ Ujian berhasil direset untuk peserta ini!', 'success');
                
                // Update the row immediately
                const row = document.getElementById(`peserta-${camabaId}`);
                if (row) {
                    // Update status
                    const statusCell = row.querySelector('td:nth-child(4)');
                    if (statusCell) {
                        statusCell.innerHTML = '<span class="badge bg-warning">MENUNGGU</span>';
                    }
                    
                    // Reset time and IP
                    const ipCell = document.getElementById(`ip-${camabaId}`);
                    const startCell = document.getElementById(`start-${camabaId}`);
                    const remainingCell = document.getElementById(`remaining-${camabaId}`);
                    
                    if (ipCell) ipCell.textContent = '-';
                    if (startCell) startCell.textContent = '-';
                    if (remainingCell) remainingCell.textContent = '-';
                }
            } else {
                showNotification(`❌ ${data.message}`, 'error');
            }
            
            button.innerHTML = originalHTML;
            button.disabled = false;
        })
        .catch(error => {
            console.error('Error resetting ujian:', error);
            showNotification(`❌ Gagal reset ujian: ${error.message}`, 'error');
            button.innerHTML = originalHTML;
            button.disabled = false;
        });
}

function sendTokenIndividu(userId, event) {
    if (!event || !confirm('Kirim token ke peserta ini?')) {
        return;
    }
    
    const button = event.target.closest('button');
    if (!button) return;
    
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    fetch(`${ujianAjaxUrl}?page=ujian&action=send_token&id=${userId}&t=${Date.now()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('✅ Token berhasil dikirim!', 'success');
            } else {
                showNotification(`❌ ${data.message}`, 'error');
            }
            
            button.innerHTML = originalHTML;
            button.disabled = false;
        })
        .catch(error => {
            console.error('Error sending individual token:', error);
            showNotification(`❌ Gagal mengirim token: ${error.message}`, 'error');
            button.innerHTML = originalHTML;
            button.disabled = false;
        });
}

// Initialize on page load
// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, checking timer...');
    
    <?php if($setting && $setting['status'] == 'active'): ?>
    // Set initial time from PHP - PERBAIKAN: gunakan jam_selesai bukan durasi
    <?php 
    if ($setting && $setting['status'] == 'active') {
        // PERBAIKAN DI SINI: Gunakan jam_selesai, bukan durasi
        $end_datetime = $setting['tanggal_ujian'] . ' ' . $setting['jam_selesai'];
        $end_time = strtotime($end_datetime);
        $remaining = $end_time - time();
        
        if ($remaining > 0) {
            $hours = floor($remaining / 3600);
            $minutes = floor(($remaining % 3600) / 60);
            $seconds = $remaining % 60;
            $initial_time = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            echo "remainingSeconds = $remaining;";
            echo "console.log('Initial timer from PHP: $initial_time ($remaining seconds)');";
            echo "document.getElementById('timerDisplay').textContent = '$initial_time';";
            
            // Set color based on time
            if ($remaining < 300) {
                echo "document.getElementById('timerDisplay').style.color = '#dc3545';";
            } else if ($remaining < 600) {
                echo "document.getElementById('timerDisplay').style.color = '#ffc107';";
            } else {
                echo "document.getElementById('timerDisplay').style.color = '#0d6efd';";
            }
        } else {
            echo "remainingSeconds = 0;";
            echo "console.log('Time expired from PHP');";
            echo "document.getElementById('timerDisplay').textContent = '00:00:00';";
            echo "document.getElementById('timerDisplay').style.color = '#6c757d';";
        }
    }
    ?>
    
    // Start timer dengan delay kecil untuk memastikan DOM ready
    setTimeout(() => {
        startTimer();
        console.log('Timer started from PHP condition');
    }, 500);
    <?php else: ?>
    // Jika tidak ada ujian aktif, tetap cek apakah ada timer yang perlu dimulai
    console.log('No active exam in PHP, checking via API...');
    
    // Coba ambil waktu dari API untuk memastikan
    setTimeout(() => {
        updateTimerFromServer();
        console.log('Timer checked via API');
    }, 1000);
    <?php endif; ?>
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Timer color transitions */
    #timerDisplay {
        transition: color 0.5s ease;
    }
`;
document.head.appendChild(style);
</script>
<style>
.stat-card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card .display-4 {
    font-size: 2.5rem;
    font-weight: bold;
    color: #0d6efd;
}

#timerDisplay {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    transition: color 0.3s ease;
}

.btn-action {
    border: none;
    background: none;
    padding: 5px 8px;
    border-radius: 4px;
    margin-right: 5px;
    transition: all 0.3s;
    cursor: pointer;
}

.btn-view {
    color: #0d6efd;
}

.btn-edit {
    color: #198754;
}

.btn-action:hover {
    background-color: rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.btn-action:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.fa-spinner {
    animation: spin 1s linear infinite;
}

/* Loading button styles */
.btn-loading {
    position: relative;
    overflow: hidden;
}

.btn-loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% { left: -100%; }
    100% { left: 100%; }
}
</style>