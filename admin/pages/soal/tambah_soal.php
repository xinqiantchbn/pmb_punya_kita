<?php

// Cek session admin
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../../index.php?page=dashboard");
    exit();
}

$errors = [];
$success = '';

// ==================== PERBAIKAN 2: AMBIL KATEGORI DENGAN BENAR ====================
$default_kategori = isset($_GET['kategori']) ? urldecode($_GET['kategori']) : 'Umum';

// Debug: Cek apakah kategori diterima
// error_log("Kategori diterima: " . $default_kategori);

// Get all categories
$sql = "SELECT DISTINCT kategori FROM soal ORDER BY kategori";
$categories_result = $conn->query($sql);

$upload_dir = __DIR__ . '/../../uploads/soal/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Diagnostic logger untuk upload
function logUpload($msg) {
    global $upload_dir;
    $logfile = rtrim($upload_dir, '/') . '/upload_debug.log';
    @file_put_contents($logfile, date('c') . " " . $msg . PHP_EOL, FILE_APPEND);
}

logUpload("Upload dir: " . $upload_dir);


// ==================== PERBAIKAN 2: HANDLE FORM SUBMISSION ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['soal_data'])) {
    $soal_data_array = json_decode($_POST['soal_data'], true);
    $success_count = 0;
    
    if (!is_array($soal_data_array)) {
        $errors[] = "Format data soal tidak valid!";
    } else {
        foreach ($soal_data_array as $index => $soal_data) {
            // Validasi data
            if (empty($soal_data['kategori']) || empty($soal_data['pertanyaan']) || 
                empty($soal_data['jawaban_benar'])) {
                $errors[] = "Soal #" . ($index + 1) . ": Data tidak lengkap";
                continue;
            }
            
            // Upload gambar pertanyaan jika ada
            $pertanyaan_gambar = '';
$file_key = 'pertanyaan_gambar_' . $index;

if (!empty($_FILES[$file_key]['name'])) {
    $file_name = $_FILES[$file_key]['name'];
    $file_tmp  = $_FILES[$file_key]['tmp_name'];
    $file_size = $_FILES[$file_key]['size'];

    logUpload("Pertanyaan file key={$file_key} name={$file_name} size={$file_size} error=" . ($_FILES[$file_key]['error'] ?? ''));

    $allowed_extensions = ['jpg','jpeg','png','gif','webp'];
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($file_extension, $allowed_extensions) && $file_size <= 2097152) {
            $filename = 'pertanyaan_' . time() . '_' . $index . '_' . uniqid() . '.' . $file_extension;
            if (move_uploaded_file($file_tmp, $upload_dir . $filename)) {
                $pertanyaan_gambar = $filename;
                logUpload("Uploaded pertanyaan: {$filename}");
            } else {
                $errors[] = "Soal #" . ($index + 1) . ": Gagal upload gambar pertanyaan";
                $err = isset($_FILES[$file_key]['error']) ? $_FILES[$file_key]['error'] : 'unknown';
                logUpload("move_uploaded_file failed for {$file_key}. tmp={$file_tmp} err={$err}");
            }
        } else {
            logUpload("Pertanyaan file rejected for {$file_key}: ext={$file_extension} size={$file_size}");
        }
}

            
            // Upload gambar opsi
            $opsi_images = ['a'=>'','b'=>'','c'=>'','d'=>''];

foreach (['a','b','c','d'] as $char) {
    $file_key = 'opsi_' . $char . '_gambar_' . $index;

    if (!empty($_FILES[$file_key]['name'])) {
        $file_name = $_FILES[$file_key]['name'];
        $file_tmp  = $_FILES[$file_key]['tmp_name'];
        $file_size = $_FILES[$file_key]['size'];

        $allowed_extensions = ['jpg','jpeg','png','gif','webp'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($file_extension, $allowed_extensions) && $file_size <= 2097152) {
            $filename = 'opsi_' . $char . '_' . time() . '_' . $index . '_' . uniqid() . '.' . $file_extension;
            if (move_uploaded_file($file_tmp, $upload_dir . $filename)) {
                $opsi_images[$char] = $filename;
                logUpload("Uploaded opsi {$char}: {$filename}");
            } else {
                $err = isset($_FILES[$file_key]['error']) ? $_FILES[$file_key]['error'] : 'unknown';
                logUpload("move_uploaded_file failed for {$file_key}. tmp={$file_tmp} err={$err}");
            }
        } else {
            logUpload("Opsi file rejected for {$file_key}: ext={$file_extension} size={$file_size}");
        }
    }
}

            
            // ==================== PERBAIKAN 3: SQL YANG BENAR ====================
            $sql = "INSERT INTO soal (kategori, pertanyaan, pertanyaan_gambar, 
                    opsi_a, opsi_a_gambar, opsi_b, opsi_b_gambar, 
                    opsi_c, opsi_c_gambar, opsi_d, opsi_d_gambar, 
                    jawaban_benar, dibuat_oleh) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $errors[] = "Soal #" . ($index + 1) . ": Prepare failed - " . $conn->error;
                continue;
            }
            
            // Bind parameter
            $stmt->bind_param(
                "sssssssssssss",
                $soal_data['kategori'],
                $soal_data['pertanyaan'],
                $pertanyaan_gambar,
                $soal_data['opsi_a'],
                $opsi_images['a'],
                $soal_data['opsi_b'],
                $opsi_images['b'],
                $soal_data['opsi_c'],
                $opsi_images['c'],
                $soal_data['opsi_d'],
                $opsi_images['d'],
                $soal_data['jawaban_benar'],
                $_SESSION['admin_id']
            );
            
            if ($stmt->execute()) {
                $success_count++;
            } else {
                $errors[] = "Soal #" . ($index + 1) . ": " . $stmt->error;
                error_log("SQL Error: " . $stmt->error);
            }
            $stmt->close();
        }
        
        if ($success_count > 0) {
            $success = "Berhasil menambahkan $success_count soal!";
            // Refresh halaman setelah sukses
            echo '<script>setTimeout(function() { window.location.href = "?page=soal&kategori=' . urlencode($default_kategori) . '"; }, 2000);</script>';
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="content-header">
                <div class="page-title">
                    <h2>Tambah Soal Baru</h2>
                    <p>
                        <a href="?page=soal<?php echo isset($_GET['kategori']) ? '&kategori=' . urlencode($_GET['kategori']) : ''; ?>" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>Kembali ke Data Soal
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Form Tambah Soal</h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addMoreSoal">
                                <i class="fas fa-plus me-1"></i>Tambah Soal Lain
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" id="importTextBtn">
                                <i class="fas fa-file-import me-1"></i>Import Teks
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <div class="mt-2">
                            <a href="?page=soal&kategori=<?php echo urlencode($default_kategori); ?>" class="btn btn-sm btn-success">
                                Lihat Soal di Kategori <?php echo htmlspecialchars($default_kategori); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h6>Error:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" id="soalForm">
                        <input type="hidden" name="soal_data" id="soalData">
                        
                        <div id="soalContainer">
                            <!-- Soal pertama -->
                            <div class="soal-item card mb-4 border-primary" data-index="0">
                                <div class="card-header bg-primary bg-opacity-10 text-primary d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>Soal #1</h5>
                                    <button type="button" class="btn btn-sm btn-danger remove-soal" style="display:none;">
                                        <i class="fas fa-times"></i> Hapus
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <!-- ==================== PERBAIKAN 5: TAMPILKAN KATEGORI OTOMATIS ==================== -->
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Kategori/Jurusan</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control bg-light" 
                                                       value="<?php echo htmlspecialchars($default_kategori); ?>" 
                                                       readonly>
                                                <input type="hidden" name="kategori[]" 
                                                       value="<?php echo htmlspecialchars($default_kategori); ?>">
                                                <span class="input-group-text bg-success text-white">
                                                    <i class="fas fa-check"></i>
                                                </span>
                                            </div>
                                            <small class="text-muted">Kategori dipilih otomatis</small>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Jawaban Benar</label>
                                            <select name="jawaban_benar[]" class="form-select" required>
                                                <option value="a">A</option>
                                                <option value="b">B</option>
                                                <option value="c">C</option>
                                                <option value="d">D</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-12">
                                            <hr class="my-4">
                                        </div>
                                        
                                        <div class="col-md-8">
                                            <label class="form-label fw-bold">Pertanyaan</label>
                                            <textarea name="pertanyaan[]" class="form-control" rows="4" 
                                                      placeholder="Tulis pertanyaan di sini..." required></textarea>
                                            <div class="form-text">
                                                Gunakan format HTML sederhana jika perlu: &lt;b&gt;tebal&lt;/b&gt;, &lt;i&gt;miring&lt;/i&gt;, &lt;u&gt;garis bawah&lt;/u&gt;
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Gambar Pertanyaan (Opsional)</label>
                                            <div class="input-group">
                                                <input type="file" name="pertanyaan_gambar_0" class="form-control" 
                                                       accept="image/*" id="pertanyaanGambar0">
                                                <button type="button" class="btn btn-outline-secondary" onclick="clearFileInput('pertanyaanGambar0', 'previewPertanyaan0')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="mt-2">
                                                <img id="previewPertanyaan0" class="img-thumbnail d-none" style="max-height: 150px;">
                                            </div>
                                            <div class="form-text">
                                                Maks. 2MB (JPG, PNG, GIF, WebP)
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <h6 class="mt-4 mb-3 fw-bold">Opsi Jawaban:</h6>
                                        </div>
                                        
                                        <!-- Opsi A -->
                                        <div class="col-md-6">
                                            <div class="card h-100">
                                                <div class="card-header bg-success bg-opacity-10">
                                                    <h6 class="mb-0"><span class="badge bg-success">A</span> Opsi Pertama</h6>
                                                </div>
                                                <div class="card-body">
                                                    <textarea name="opsi_a[]" class="form-control" rows="3" 
                                                              placeholder="Jawaban A..." required></textarea>
                                                    <div class="mt-3">
                                                        <label class="form-label small">Gambar Opsi (Opsional)</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="file" name="opsi_a_gambar_0" class="form-control" 
                                                                   accept="image/*" id="opsiAGambar0">
                                                            <button type="button" class="btn btn-outline-secondary" 
                                                                    onclick="clearFileInput('opsiAGambar0', 'previewOpsiA0')">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                        <div class="mt-2">
                                                            <img id="previewOpsiA0" class="img-thumbnail d-none" style="max-height: 80px;">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Opsi B -->
                                        <div class="col-md-6">
                                            <div class="card h-100">
                                                <div class="card-header bg-info bg-opacity-10">
                                                    <h6 class="mb-0"><span class="badge bg-info">B</span> Opsi Kedua</h6>
                                                </div>
                                                <div class="card-body">
                                                    <textarea name="opsi_b[]" class="form-control" rows="3" 
                                                              placeholder="Jawaban B..." required></textarea>
                                                    <div class="mt-3">
                                                        <label class="form-label small">Gambar Opsi (Opsional)</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="file" name="opsi_b_gambar_0" class="form-control" 
                                                                   accept="image/*" id="opsiBGambar0">
                                                            <button type="button" class="btn btn-outline-secondary" 
                                                                    onclick="clearFileInput('opsiBGambar0', 'previewOpsiB0')">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                        <div class="mt-2">
                                                            <img id="previewOpsiB0" class="img-thumbnail d-none" style="max-height: 80px;">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Opsi C -->
                                        <div class="col-md-6 mt-3">
                                            <div class="card h-100">
                                                <div class="card-header bg-warning bg-opacity-10">
                                                    <h6 class="mb-0"><span class="badge bg-warning">C</span> Opsi Ketiga</h6>
                                                </div>
                                                <div class="card-body">
                                                    <textarea name="opsi_c[]" class="form-control" rows="3" 
                                                              placeholder="Jawaban C..." required></textarea>
                                                    <div class="mt-3">
                                                        <label class="form-label small">Gambar Opsi (Opsional)</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="file" name="opsi_c_gambar_0" class="form-control" 
                                                                   accept="image/*" id="opsiCGambar0">
                                                            <button type="button" class="btn btn-outline-secondary" 
                                                                    onclick="clearFileInput('opsiCGambar0', 'previewOpsiC0')">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                        <div class="mt-2">
                                                            <img id="previewOpsiC0" class="img-thumbnail d-none" style="max-height: 80px;">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Opsi D -->
                                        <div class="col-md-6 mt-3">
                                            <div class="card h-100">
                                                <div class="card-header bg-danger bg-opacity-10">
                                                    <h6 class="mb-0"><span class="badge bg-danger">D</span> Opsi Keempat</h6>
                                                </div>
                                                <div class="card-body">
                                                    <textarea name="opsi_d[]" class="form-control" rows="3" 
                                                              placeholder="Jawaban D..." required></textarea>
                                                    <div class="mt-3">
                                                        <label class="form-label small">Gambar Opsi (Opsional)</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="file" name="opsi_d_gambar_0" class="form-control" 
                                                                   accept="image/*" id="opsiDGambar0">
                                                            <button type="button" class="btn btn-outline-secondary" 
                                                                    onclick="clearFileInput('opsiDGambar0', 'previewOpsiD0')">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                        <div class="mt-2">
                                                            <img id="previewOpsiD0" class="img-thumbnail d-none" style="max-height: 80px;">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <div>
                                <a href="?page=soal<?php echo isset($_GET['kategori']) ? '&kategori=' . urlencode($_GET['kategori']) : ''; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Batal
                                </a>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check me-2"></i>Simpan Soal
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Import Teks -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-import me-2"></i>Import Soal dari Teks</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Format yang didukung:</label>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <pre class="mb-0" style="font-size: 0.8rem;">
Kategori: <?php echo htmlspecialchars($default_kategori); ?> 
Pertanyaan: Apa fungsi utama database?
A. Menyimpan dan mengelola data
B. Membuat program aplikasi
C. Merancang antarmuka pengguna
D. Mengirim email
Jawaban: A
---
Kategori: <?php echo htmlspecialchars($default_kategori); ?> 
Pertanyaan: Ibu kota Indonesia?
A. Jakarta
B. Bandung
C. Surabaya
D. Medan
Jawaban: A</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Teks yang akan diimport:</label>
                            <textarea class="form-control" rows="10" id="importText" 
                                      placeholder="Tempel teks soal di sini..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kategori:</label>
                            <input type="text" class="form-control" id="defaultKategori" 
                                   value="<?php echo htmlspecialchars($default_kategori); ?>" readonly>
                        </div>
                    </div>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Setiap soal dipisahkan dengan garis (---) atau baris kosong. Sistem akan otomatis membuat soal baru.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="processImportBtn">
                    <i class="fas fa-cogs me-2"></i>Proses Import
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let soalCounter = 1;

// Fungsi untuk clear file input
function clearFileInput(inputId, previewId) {
    document.getElementById(inputId).value = '';
    const preview = document.getElementById(previewId);
    if (preview) {
        preview.src = '';
        preview.classList.add('d-none');
    }
}

// Image preview
function setupImagePreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    
    if (input && preview) {
        input.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                };
                reader.readAsDataURL(this.files[0]);
            } else {
                preview.classList.add('d-none');
            }
        });
    }
}

// Setup previews for first soal
setupImagePreview('pertanyaanGambar0', 'previewPertanyaan0');
setupImagePreview('opsiAGambar0', 'previewOpsiA0');
setupImagePreview('opsiBGambar0', 'previewOpsiB0');
setupImagePreview('opsiCGambar0', 'previewOpsiC0');
setupImagePreview('opsiDGambar0', 'previewOpsiD0');

// Tambah soal baru
document.getElementById('addMoreSoal').addEventListener('click', function() {
    const container = document.getElementById('soalContainer');
    const firstSoal = document.querySelector('.soal-item');
    const clone = firstSoal.cloneNode(true);
    
    soalCounter++;
    const newIndex = soalCounter - 1;
    
    // Update data-index
    clone.setAttribute('data-index', newIndex);
    
    // Update header
    const header = clone.querySelector('.card-header h5');
    header.innerHTML = `<i class="fas fa-question-circle me-2"></i>Soal #${soalCounter}`;
    
    // Show remove button
    clone.querySelector('.remove-soal').style.display = 'block';
    
    // Update all elements
    const elements = clone.querySelectorAll('input, select, textarea, button, img');
    elements.forEach(el => {
        if (el.name && el.name.includes('_0')) {
            el.name = el.name.replace('_0', '_' + newIndex);
        }
        if (el.id && el.id.includes('0')) {
            el.id = el.id.replace('0', newIndex);
        }
        if (el.htmlFor && el.htmlFor.includes('0')) {
            el.htmlFor = el.htmlFor.replace('0', newIndex);
        }
        
        // Clear values
        if (el.tagName === 'TEXTAREA') {
            el.value = '';
        }
        if (el.tagName === 'INPUT' && el.type === 'file') {
            el.value = '';
            // Update clear button onclick
            if (el.id && el.id.includes('Gambar')) {
                const clearBtn = el.parentElement.querySelector('button');
                if (clearBtn) {
                    const previewId = el.id.replace('Gambar', 'preview');
                    clearBtn.setAttribute('onclick', `clearFileInput('${el.id}', '${previewId}')`);
                }
            }
        }
        if (el.tagName === 'SELECT') {
            if (el.name.includes('jawaban_benar')) {
                el.value = 'a';
            }
        }
        if (el.tagName === 'IMG') {
            el.src = '';
            el.classList.add('d-none');
        }
    });
    
    // Setup image preview for new soal
    setupImagePreview(`pertanyaanGambar${newIndex}`, `previewPertanyaan${newIndex}`);
    setupImagePreview(`opsiAGambar${newIndex}`, `previewOpsiA${newIndex}`);
    setupImagePreview(`opsiBGambar${newIndex}`, `previewOpsiB${newIndex}`);
    setupImagePreview(`opsiCGambar${newIndex}`, `previewOpsiC${newIndex}`);
    setupImagePreview(`opsiDGambar${newIndex}`, `previewOpsiD${newIndex}`);
    
    // Add to container
    container.appendChild(clone);
    
    // Scroll to new soal
    clone.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
});

// Hapus soal
document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-soal')) {
        const soalItem = e.target.closest('.soal-item');
        if (document.querySelectorAll('.soal-item').length > 1) {
            if (confirm('Hapus soal ini?')) {
                soalItem.remove();
                // Renumber remaining soal
                const allSoal = document.querySelectorAll('.soal-item');
                allSoal.forEach((item, index) => {
                    const header = item.querySelector('.card-header h5');
                    header.innerHTML = `<i class="fas fa-question-circle me-2"></i>Soal #${index + 1}`;
                    item.setAttribute('data-index', index);
                });
                soalCounter = allSoal.length;
            }
        } else {
            alert('Minimal harus ada 1 soal');
        }
    }
});

// Import dari teks
document.getElementById('importTextBtn').addEventListener('click', function() {
    const modal = new bootstrap.Modal(document.getElementById('importModal'));
    modal.show();
});

document.getElementById('processImportBtn').addEventListener('click', function() {
    const text = document.getElementById('importText').value.trim();
    const defaultCategory = document.getElementById('defaultKategori').value || '<?php echo $default_kategori; ?>';
    
    if (!text) {
        alert('Masukkan teks terlebih dahulu!');
        return;
    }
    
    // Split soal blocks
    const soalBlocks = text.split(/\n---\n|\n\s*\n\s*\n/);
    let processedCount = 0;
    
    soalBlocks.forEach((block, blockIndex) => {
        block = block.trim();
        if (!block) return;
        
        // Add new soal if needed
        if (blockIndex > 0) {
            document.getElementById('addMoreSoal').click();
        }
        
        const lines = block.split('\n').map(l => l.trim()).filter(l => l);
        const soalData = {
            kategori: defaultCategory,
            pertanyaan: '',
            opsi_a: '',
            opsi_b: '',
            opsi_c: '',
            opsi_d: '',
            jawaban_benar: 'a'
        };
        
        lines.forEach(line => {
            if (line.toLowerCase().startsWith('kategori:')) {
                soalData.kategori = line.replace(/^kategori:\s*/i, '').trim();
            } else if (line.toLowerCase().startsWith('pertanyaan:')) {
                soalData.pertanyaan = line.replace(/^pertanyaan:\s*/i, '').trim();
            } else if (line.toLowerCase().startsWith('jawaban:')) {
                const jawab = line.replace(/^jawaban:\s*/i, '').trim().toLowerCase();
                if (['a','b','c','d'].includes(jawab)) {
                    soalData.jawaban_benar = jawab;
                }
            } else if (line.match(/^[A-D]\.?\s*(.+)/i)) {
                const match = line.match(/^([A-D])\.?\s*(.+)/i);
                if (match) {
                    const option = match[1].toLowerCase();
                    const value = match[2].trim();
                    soalData[`opsi_${option}`] = value;
                }
            }
        });
        
        // If question not found in format, use first line as question
        if (!soalData.pertanyaan && lines.length > 0) {
            const firstLine = lines[0];
            if (!firstLine.toLowerCase().startsWith('kategori:') && 
                !firstLine.match(/^[A-D]\./i) &&
                !firstLine.toLowerCase().startsWith('jawaban:')) {
                soalData.pertanyaan = firstLine;
            }
        }
        
        // Fill the form
        const soalItem = document.querySelector(`[data-index="${blockIndex}"]`);
        if (soalItem) {
            soalItem.querySelector('textarea[name="pertanyaan[]"]').value = soalData.pertanyaan;
            soalItem.querySelector('textarea[name="opsi_a[]"]').value = soalData.opsi_a || '';
            soalItem.querySelector('textarea[name="opsi_b[]"]').value = soalData.opsi_b || '';
            soalItem.querySelector('textarea[name="opsi_c[]"]').value = soalData.opsi_c || '';
            soalItem.querySelector('textarea[name="opsi_d[]"]').value = soalData.opsi_d || '';
            soalItem.querySelector('select[name="jawaban_benar[]"]').value = soalData.jawaban_benar;
            
            processedCount++;
        }
    });
    
    bootstrap.Modal.getInstance(document.getElementById('importModal')).hide();
    
    // Show success message
    const toast = document.createElement('div');
    toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '9999';
    toast.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>
        Berhasil mengimpor ${processedCount} soal!
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 3000);
});

// Handle form submission
document.getElementById('soalForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validate all soal
    let isValid = true;
    const soalItems = document.querySelectorAll('.soal-item');
    const errorMessages = [];
    
    soalItems.forEach((item, index) => {
        const pertanyaan = item.querySelector('textarea[name="pertanyaan[]"]').value.trim();
        const opsiA = item.querySelector('textarea[name="opsi_a[]"]').value.trim();
        const opsiB = item.querySelector('textarea[name="opsi_b[]"]').value.trim();
        const opsiC = item.querySelector('textarea[name="opsi_c[]"]').value.trim();
        const opsiD = item.querySelector('textarea[name="opsi_d[]"]').value.trim();
        
        if (!pertanyaan || !opsiA || !opsiB || !opsiC || !opsiD) {
            isValid = false;
            item.classList.add('border-danger');
            errorMessages.push(`Soal #${index + 1} belum lengkap`);
        } else {
            item.classList.remove('border-danger');
        }
    });
    
    if (!isValid) {
        alert('Harap lengkapi semua field yang diperlukan!\n' + errorMessages.join('\n'));
        return;
    }
    
    // Collect all soal data
    const soalDataArray = [];
    soalItems.forEach((item, index) => {
        const data = {
            kategori: '<?php echo addslashes($default_kategori); ?>',
            pertanyaan: item.querySelector('textarea[name="pertanyaan[]"]').value,
            opsi_a: item.querySelector('textarea[name="opsi_a[]"]').value,
            opsi_b: item.querySelector('textarea[name="opsi_b[]"]').value,
            opsi_c: item.querySelector('textarea[name="opsi_c[]"]').value,
            opsi_d: item.querySelector('textarea[name="opsi_d[]"]').value,
            jawaban_benar: item.querySelector('select[name="jawaban_benar[]"]').value
        };
        
        soalDataArray.push(data);
    });
    
    // Set the hidden field
    document.getElementById('soalData').value = JSON.stringify(soalDataArray);
    
    // Show loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
    submitBtn.disabled = true;
    
    // Submit form
    this.submit();
});
</script>

<style>
.soal-item {
    transition: all 0.3s ease;
}

.soal-item.border-danger {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
}

.card-header.bg-primary.bg-opacity-10 {
    border-bottom: 2px solid #0d6efd;
}

.card-header.bg-success.bg-opacity-10 {
    border-bottom: 2px solid #198754;
}

.card-header.bg-info.bg-opacity-10 {
    border-bottom: 2px solid #0dcaf0;
}

.card-header.bg-warning.bg-opacity-10 {
    border-bottom: 2px solid #ffc107;
}

.card-header.bg-danger.bg-opacity-10 {
    border-bottom: 2px solid #dc3545;
}

.form-label.fw-bold {
    color: #2c3e50;
}

.img-thumbnail {
    max-width: 100%;
    height: auto;
}

.bg-light {
    background-color: #f8f9fa !important;
}
</style>