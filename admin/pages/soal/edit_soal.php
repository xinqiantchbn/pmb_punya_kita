<?php
// Cek session admin
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../../index.php?page=dashboard");
    exit();
}

$errors = [];
$success = '';
$soal_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ==================== AMBIL DATA SOAL YANG AKAN DIEDIT ====================
$soal_data = null;
if ($soal_id > 0) {
    $sql = "SELECT s.*, a.nama_lengkap 
            FROM soal s 
            LEFT JOIN admins a ON s.dibuat_oleh = a.id 
            WHERE s.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $soal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $soal_data = $result->fetch_assoc();
        $default_kategori = $soal_data['kategori'];
    } else {
        $errors[] = "Soal tidak ditemukan!";
        header("Location: ?page=soal");
        exit();
    }
    $stmt->close();
} else {
    $errors[] = "ID soal tidak valid!";
    header("Location: ?page=soal");
    exit();
}

// Get all categories untuk dropdown
$sql = "SELECT DISTINCT kategori FROM soal ORDER BY kategori";
$categories_result = $conn->query($sql);

// ==================== HANDLE FORM UPDATE ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_soal'])) {
    $kategori = trim($_POST['kategori']);
    $pertanyaan = trim($_POST['pertanyaan']);
    $opsi_a = trim($_POST['opsi_a']);
    $opsi_b = trim($_POST['opsi_b']);
    $opsi_c = trim($_POST['opsi_c']);
    $opsi_d = trim($_POST['opsi_d']);
    $jawaban_benar = trim($_POST['jawaban_benar']);
    
    // Validasi data
    if (empty($kategori) || empty($pertanyaan) || empty($jawaban_benar) ||
        empty($opsi_a) || empty($opsi_b) || empty($opsi_c) || empty($opsi_d)) {
        $errors[] = "Semua field wajib diisi!";
    } else {
        // Handle gambar yang diupload
        $pertanyaan_gambar = $soal_data['pertanyaan_gambar'];
        $opsi_a_gambar = $soal_data['opsi_a_gambar'];
        $opsi_b_gambar = $soal_data['opsi_b_gambar'];
        $opsi_c_gambar = $soal_data['opsi_c_gambar'];
        $opsi_d_gambar = $soal_data['opsi_d_gambar'];
        
        // Fungsi untuk upload gambar
        // Fungsi untuk upload gambar
// Diagnostic logger untuk upload di edit_soal
function logUploadEdit($msg) {
    $upload_dir = __DIR__ . '/../../uploads/soal/';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }
    $logfile = rtrim($upload_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'upload_debug.log';
    @file_put_contents($logfile, date('c') . " " . $msg . PHP_EOL, FILE_APPEND);
}

function uploadGambar($file_key, $current_image, $prefix, $upload_dir = null) {
    if ($upload_dir === null) {
        $upload_dir = __DIR__ . '/../../uploads/soal/';
    }

    if (!file_exists($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }

    if (isset($_FILES[$file_key]) && isset($_FILES[$file_key]['name']) && $_FILES[$file_key]['name'] !== '') {
        $err = $_FILES[$file_key]['error'];
        $file_name = $_FILES[$file_key]['name'];
        $file_tmp = $_FILES[$file_key]['tmp_name'];
        $file_size = $_FILES[$file_key]['size'];

        logUploadEdit("Received file key={$file_key} name={$file_name} size={$file_size} error={$err}");

        if ($err === 0) {
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (in_array($file_extension, $allowed_extensions) && $file_size <= 2097152) {
                // Hapus gambar lama jika ada
                if (!empty($current_image) && file_exists($upload_dir . $current_image)) {
                    @unlink($upload_dir . $current_image);
                }

                // Generate unique filename
                $filename = $prefix . '_' . time() . '_' . uniqid() . '.' . $file_extension;
                $target_path = $upload_dir . $filename;

                if (move_uploaded_file($file_tmp, $target_path)) {
                    logUploadEdit("Uploaded {$file_key} => {$filename}");
                    return $filename;
                } else {
                    logUploadEdit("move_uploaded_file failed for {$file_key}. tmp={$file_tmp}");
                    return $current_image;
                }
            } else {
                logUploadEdit("Rejected {$file_key}: ext={$file_extension} size={$file_size}");
                if (!in_array($file_extension, $allowed_extensions)) {
                    throw new Exception("Format gambar tidak didukung. Gunakan JPG, PNG, GIF, atau WebP");
                } else if ($file_size > 2097152) {
                    throw new Exception("Ukuran gambar terlalu besar (maks. 2MB)");
                }
                return $current_image;
            }
        } else {
            logUploadEdit("Upload error for {$file_key}: code={$err}");
            return $current_image;
        }
    }
    return $current_image; // Return gambar lama jika tidak ada upload baru
}

// ==================== HANDLE FORM UPDATE ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_soal'])) {
    $kategori = trim($_POST['kategori']);
    $pertanyaan = trim($_POST['pertanyaan']);
    $opsi_a = trim($_POST['opsi_a']);
    $opsi_b = trim($_POST['opsi_b']);
    $opsi_c = trim($_POST['opsi_c']);
    $opsi_d = trim($_POST['opsi_d']);
    $jawaban_benar = trim($_POST['jawaban_benar']);
    
    // Validasi data
    if (empty($kategori) || empty($pertanyaan) || empty($jawaban_benar) ||
        empty($opsi_a) || empty($opsi_b) || empty($opsi_c) || empty($opsi_d)) {
        $errors[] = "Semua field wajib diisi!";
    } else {
        // Handle gambar yang diupload
        $pertanyaan_gambar = $soal_data['pertanyaan_gambar'];
        $opsi_a_gambar = $soal_data['opsi_a_gambar'];
        $opsi_b_gambar = $soal_data['opsi_b_gambar'];
        $opsi_c_gambar = $soal_data['opsi_c_gambar'];
        $opsi_d_gambar = $soal_data['opsi_d_gambar'];
        
        try {
            // Upload gambar baru jika ada
            $pertanyaan_gambar = uploadGambar('pertanyaan_gambar', $pertanyaan_gambar, 'pertanyaan');
            $opsi_a_gambar = uploadGambar('opsi_a_gambar', $opsi_a_gambar, 'opsi_a');
            $opsi_b_gambar = uploadGambar('opsi_b_gambar', $opsi_b_gambar, 'opsi_b');
            $opsi_c_gambar = uploadGambar('opsi_c_gambar', $opsi_c_gambar, 'opsi_c');
            $opsi_d_gambar = uploadGambar('opsi_d_gambar', $opsi_d_gambar, 'opsi_d');
            
            // Hapus gambar jika tombol hapus ditekan
            $upload_dir = __DIR__ . '/../../uploads/soal/';
            if (isset($_POST['hapus_pertanyaan_gambar']) && $_POST['hapus_pertanyaan_gambar'] == '1') {
                if (!empty($pertanyaan_gambar) && file_exists($upload_dir . $pertanyaan_gambar)) {
                    @unlink($upload_dir . $pertanyaan_gambar);
                    $pertanyaan_gambar = '';
                }
            }
            
            $gambar_fields = [
                'hapus_opsi_a_gambar' => &$opsi_a_gambar,
                'hapus_opsi_b_gambar' => &$opsi_b_gambar,
                'hapus_opsi_c_gambar' => &$opsi_c_gambar,
                'hapus_opsi_d_gambar' => &$opsi_d_gambar
            ];
            
            foreach ($gambar_fields as $field => &$gambar) {
                if (isset($_POST[$field]) && $_POST[$field] == '1') {
                    if (!empty($gambar) && file_exists($upload_dir . $gambar)) {
                        @unlink($upload_dir . $gambar);
                        $gambar = '';
                    }
                }
            }
            
            // Update data di database
            $sql = "UPDATE soal SET 
                    kategori = ?, 
                    pertanyaan = ?, 
                    pertanyaan_gambar = ?,
                    opsi_a = ?, 
                    opsi_a_gambar = ?,
                    opsi_b = ?, 
                    opsi_b_gambar = ?,
                    opsi_c = ?, 
                    opsi_c_gambar = ?,
                    opsi_d = ?, 
                    opsi_d_gambar = ?,
                    jawaban_benar = ?,
                    diperbarui_pada = NOW()
                    WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param(
                    "ssssssssssssi",
                    $kategori,
                    $pertanyaan,
                    $pertanyaan_gambar,
                    $opsi_a,
                    $opsi_a_gambar,
                    $opsi_b,
                    $opsi_b_gambar,
                    $opsi_c,
                    $opsi_c_gambar,
                    $opsi_d,
                    $opsi_d_gambar,
                    $jawaban_benar,
                    $soal_id
                );
                
                if ($stmt->execute()) {
                    $success = "Soal berhasil diperbarui!";
                    // Refresh data
                    $sql = "SELECT * FROM soal WHERE id = ?";
                    $stmt2 = $conn->prepare($sql);
                    $stmt2->bind_param("i", $soal_id);
                    $stmt2->execute();
                    $result = $stmt2->get_result();
                    $soal_data = $result->fetch_assoc();
                    $stmt2->close();
                } else {
                    $errors[] = "Gagal memperbarui soal: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $errors[] = "Prepare statement failed: " . $conn->error;
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="content-header">
                <div class="page-title">
                    <h2>Edit Soal</h2>
                    <p>
                        <a href="?page=soal&kategori=<?php echo urlencode($soal_data['kategori']); ?>" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>Kembali ke Data Soal
                        </a>
                    </p>
                </div>
                <div class="header-actions">
                    <div class="btn-group">
                        <a href="?page=soal&action=duplicate&id=<?php echo $soal_id; ?>" class="btn btn-custom btn-outline-success">
                            <i class="fas fa-copy me-2"></i>Duplikasi Soal
                        </a>
                        <button type="button" class="btn btn-custom btn-outline-danger" id="deleteSoalBtn">
                            <i class="fas fa-trash me-2"></i>Hapus Soal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Edit Soal ID: #<?php echo $soal_id; ?></h5>
                        <div class="text-muted small">
                            <i class="fas fa-calendar me-1"></i> Dibuat: <?php echo date('d/m/Y H:i', strtotime($soal_data['dibuat_pada'])); ?>
                            <?php if ($soal_data['diperbarui_pada']): ?>
                                | <i class="fas fa-sync-alt me-1"></i> Diperbarui: <?php echo date('d/m/Y H:i', strtotime($soal_data['diperbarui_pada'])); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
                    
                    <form method="POST" enctype="multipart/form-data" id="editSoalForm">
                        <input type="hidden" name="update_soal" value="1">
                        <input type="hidden" id="soal_id" value="<?php echo $soal_id; ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Kategori/Jurusan</label>
                                <select name="kategori" class="form-select" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php 
                                    $categories_result->data_seek(0); // Reset pointer
                                    while($cat = $categories_result->fetch_assoc()): 
                                    ?>
                                    <option value="<?php echo htmlspecialchars($cat['kategori']); ?>" 
                                        <?php echo $cat['kategori'] == $soal_data['kategori'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['kategori']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Jawaban Benar</label>
                                <select name="jawaban_benar" class="form-select" required>
                                    <option value="a" <?php echo $soal_data['jawaban_benar'] == 'a' ? 'selected' : ''; ?>>A</option>
                                    <option value="b" <?php echo $soal_data['jawaban_benar'] == 'b' ? 'selected' : ''; ?>>B</option>
                                    <option value="c" <?php echo $soal_data['jawaban_benar'] == 'c' ? 'selected' : ''; ?>>C</option>
                                    <option value="d" <?php echo $soal_data['jawaban_benar'] == 'd' ? 'selected' : ''; ?>>D</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <hr class="my-4">
                            </div>
                            
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Pertanyaan</label>
                                <textarea name="pertanyaan" class="form-control" rows="4" 
                                          placeholder="Tulis pertanyaan di sini..." required><?php echo htmlspecialchars($soal_data['pertanyaan']); ?></textarea>
                                <div class="form-text">
                                    Gunakan format HTML sederhana jika perlu: &lt;b&gt;tebal&lt;/b&gt;, &lt;i&gt;miring&lt;/i&gt;, &lt;u&gt;garis bawah&lt;/u&gt;
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Gambar Pertanyaan</label>
                                
                                <?php if (!empty($soal_data['pertanyaan_gambar'])): ?>
                                <div class="mb-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                                    <?php $pert_path = __DIR__ . '/../../uploads/soal/' . $soal_data['pertanyaan_gambar']; ?>
                                                    <img src="uploads/soal/<?php echo $soal_data['pertanyaan_gambar']; ?>" 
                                                        class="img-fluid mb-2" style="max-height: 150px;">
                                                    <div class="small text-muted">File: <?php echo htmlspecialchars($soal_data['pertanyaan_gambar']); ?> — Exists: <?php echo file_exists($pert_path) ? 'yes' : 'no'; ?></div>
                                                <div class="d-flex justify-content-center gap-2">
                                                   <a href="uploads/soal/<?php echo $soal_data['pertanyaan_gambar']; ?>" 
                                                     target="_blank" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> Lihat
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="hapusGambar('pertanyaan', this)">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="hapus_pertanyaan_gambar" id="hapusPertanyaanGambar" value="0">
                                </div>
                                <?php endif; ?>
                                
                                <div class="input-group">
                                    <input type="file" name="pertanyaan_gambar" class="form-control" 
                                           accept="image/*" id="pertanyaanGambar">
                                    <button type="button" class="btn btn-outline-secondary" 
                                            onclick="clearFileInput('pertanyaanGambar', 'previewPertanyaan')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="mt-2">
                                    <img id="previewPertanyaan" class="img-thumbnail d-none" style="max-height: 150px;">
                                </div>
                                <div class="form-text">
                                    Maks. 2MB (JPG, PNG, GIF, WebP). Kosongkan jika tidak ingin mengubah.
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
                                        <textarea name="opsi_a" class="form-control" rows="3" 
                                                  placeholder="Jawaban A..." required><?php echo htmlspecialchars($soal_data['opsi_a']); ?></textarea>
                                        <div class="mt-3">
                                            <label class="form-label small">Gambar Opsi</label>
                                            
                                            <?php if (!empty($soal_data['opsi_a_gambar'])): ?>
                                            <div class="mb-2">
                                                <div class="d-flex align-items-center mb-2">
                                                      <?php $pathA = __DIR__ . '/../../uploads/soal/' . $soal_data['opsi_a_gambar']; ?>
                                                      <img src="uploads/soal/<?php echo $soal_data['opsi_a_gambar']; ?>" 
                                                          class="img-thumbnail me-2" style="max-height: 60px;">
                                                      <div class="small text-muted">File: <?php echo htmlspecialchars($soal_data['opsi_a_gambar']); ?> — Exists: <?php echo file_exists($pathA) ? 'yes' : 'no'; ?></div>
                                                    <div>
                                                        <button type="button" class="btn btn-sm btn-danger btn-sm" 
                                                            onclick="hapusGambar('opsi_a', this)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="hapus_opsi_a_gambar" id="hapusOpsiAGambar" value="0">
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div class="input-group input-group-sm">
                                                <input type="file" name="opsi_a_gambar" class="form-control" 
                                                       accept="image/*" id="opsiAGambar">
                                                <button type="button" class="btn btn-outline-secondary" 
                                                        onclick="clearFileInput('opsiAGambar', 'previewOpsiA')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="mt-2">
                                                <img id="previewOpsiA" class="img-thumbnail d-none" style="max-height: 80px;">
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
                                        <textarea name="opsi_b" class="form-control" rows="3" 
                                                  placeholder="Jawaban B..." required><?php echo htmlspecialchars($soal_data['opsi_b']); ?></textarea>
                                        <div class="mt-3">
                                            <label class="form-label small">Gambar Opsi</label>
                                            
                                            <?php if (!empty($soal_data['opsi_b_gambar'])): ?>
                                            <div class="mb-2">
                                                <div class="d-flex align-items-center mb-2">
                                                      <?php $pathB = __DIR__ . '/../../uploads/soal/' . $soal_data['opsi_b_gambar']; ?>
                                                      <img src="uploads/soal/<?php echo $soal_data['opsi_b_gambar']; ?>" 
                                                          class="img-thumbnail me-2" style="max-height: 60px;">
                                                      <div class="small text-muted">File: <?php echo htmlspecialchars($soal_data['opsi_b_gambar']); ?> — Exists: <?php echo file_exists($pathB) ? 'yes' : 'no'; ?></div>
                                                    <div>
                                                        <button type="button" class="btn btn-sm btn-danger btn-sm" 
                                                            onclick="hapusGambar('opsi_b', this)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="hapus_opsi_b_gambar" id="hapusOpsiBGambar" value="0">
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div class="input-group input-group-sm">
                                                <input type="file" name="opsi_b_gambar" class="form-control" 
                                                       accept="image/*" id="opsiBGambar">
                                                <button type="button" class="btn btn-outline-secondary" 
                                                        onclick="clearFileInput('opsiBGambar', 'previewOpsiB')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="mt-2">
                                                <img id="previewOpsiB" class="img-thumbnail d-none" style="max-height: 80px;">
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
                                        <textarea name="opsi_c" class="form-control" rows="3" 
                                                  placeholder="Jawaban C..." required><?php echo htmlspecialchars($soal_data['opsi_c']); ?></textarea>
                                        <div class="mt-3">
                                            <label class="form-label small">Gambar Opsi</label>
                                            
                                            <?php if (!empty($soal_data['opsi_c_gambar'])): ?>
                                            <div class="mb-2">
                                                <div class="d-flex align-items-center mb-2">
                                                      <?php $pathC = __DIR__ . '/../../uploads/soal/' . $soal_data['opsi_c_gambar']; ?>
                                                      <img src="uploads/soal/<?php echo $soal_data['opsi_c_gambar']; ?>" 
                                                          class="img-thumbnail me-2" style="max-height: 60px;">
                                                      <div class="small text-muted">File: <?php echo htmlspecialchars($soal_data['opsi_c_gambar']); ?> — Exists: <?php echo file_exists($pathC) ? 'yes' : 'no'; ?></div>
                                                    <div>
                                                        <button type="button" class="btn btn-sm btn-danger btn-sm" 
                                                            onclick="hapusGambar('opsi_c', this)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="hapus_opsi_c_gambar" id="hapusOpsiCGambar" value="0">
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div class="input-group input-group-sm">
                                                <input type="file" name="opsi_c_gambar" class="form-control" 
                                                       accept="image/*" id="opsiCGambar">
                                                <button type="button" class="btn btn-outline-secondary" 
                                                        onclick="clearFileInput('opsiCGambar', 'previewOpsiC')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="mt-2">
                                                <img id="previewOpsiC" class="img-thumbnail d-none" style="max-height: 80px;">
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
                                        <textarea name="opsi_d" class="form-control" rows="3" 
                                                  placeholder="Jawaban D..." required><?php echo htmlspecialchars($soal_data['opsi_d']); ?></textarea>
                                        <div class="mt-3">
                                            <label class="form-label small">Gambar Opsi</label>
                                            
                                            <?php if (!empty($soal_data['opsi_d_gambar'])): ?>
                                            <div class="mb-2">
                                                <div class="d-flex align-items-center mb-2">
                                                      <?php $pathD = __DIR__ . '/../../uploads/soal/' . $soal_data['opsi_d_gambar']; ?>
                                                      <img src="uploads/soal/<?php echo $soal_data['opsi_d_gambar']; ?>" 
                                                          class="img-thumbnail me-2" style="max-height: 60px;">
                                                      <div class="small text-muted">File: <?php echo htmlspecialchars($soal_data['opsi_d_gambar']); ?> — Exists: <?php echo file_exists($pathD) ? 'yes' : 'no'; ?></div>
                                                    <div>
                                                        <button type="button" class="btn btn-sm btn-danger btn-sm" 
                                                            onclick="hapusGambar('opsi_d', this)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="hapus_opsi_d_gambar" id="hapusOpsiDGambar" value="0">
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div class="input-group input-group-sm">
                                                <input type="file" name="opsi_d_gambar" class="form-control" 
                                                       accept="image/*" id="opsiDGambar">
                                                <button type="button" class="btn btn-outline-secondary" 
                                                        onclick="clearFileInput('opsiDGambar', 'previewOpsiD')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="mt-2">
                                                <img id="previewOpsiD" class="img-thumbnail d-none" style="max-height: 80px;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Info Tambahan -->
                            <div class="col-12 mt-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-info-circle me-2"></i>Informasi Soal</h6>
                                                <ul class="mb-0">
                                                    <li>ID Soal: <strong>#<?php echo $soal_id; ?></strong></li>
                                                    <li>Dibuat oleh: <strong><?php echo htmlspecialchars($soal_data['nama_lengkap'] ?? 'Admin'); ?></strong></li>
                                                    <li>Tanggal dibuat: <?php echo date('d/m/Y H:i', strtotime($soal_data['dibuat_pada'])); ?></li>
                                                    <?php if ($soal_data['diperbarui_pada']): ?>
                                                    <li>Terakhir diperbarui: <?php echo date('d/m/Y H:i', strtotime($soal_data['diperbarui_pada'])); ?></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-history me-2"></i>Riwayat Perubahan</h6>
                                                <p class="small text-muted mb-0">
                                                    Setiap perubahan akan dicatat dalam sistem. Pastikan data yang diinput sudah benar.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <div>
                                <a href="?page=soal&kategori=<?php echo urlencode($soal_data['kategori']); ?>" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Batal
                                </a>
                            </div>
                            <div class="btn-group">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                                </button>
                                <a href="?page=soal&action=tambah&kategori=<?php echo urlencode($soal_data['kategori']); ?>" 
                                   class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Tambah Soal Baru
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus soal ini?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Peringatan:</strong> Tindakan ini tidak dapat dibatalkan! Semua gambar terkait soal ini juga akan dihapus.
                </div>
                <p><strong>Pertanyaan:</strong><br><?php echo substr(strip_tags($soal_data['pertanyaan']), 0, 100); ?>...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Ya, Hapus Soal</button>
            </div>
        </div>
    </div>
</div>

<script>
// Global AJAX handler URL
const AJAX_HANDLER_URL = '<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), "\\/") . "/ajax_handler.php"; ?>';
console.log('AJAX handler URL:', AJAX_HANDLER_URL);

// Fungsi untuk clear file input
function clearFileInput(inputId, previewId) {
    document.getElementById(inputId).value = '';
    const preview = document.getElementById(previewId);
    if (preview) {
        preview.src = '';
        preview.classList.add('d-none');
    }
}

// Fungsi untuk hapus gambar
function hapusGambar(jenis, btn) {
    if (confirm('Hapus gambar ' + jenis + '?')) {
        const parts = jenis.split('_').map(p => p.charAt(0).toUpperCase() + p.slice(1));
        const fieldId = 'hapus' + parts.join('') + 'Gambar';
        const hiddenField = document.getElementById(fieldId);
        if (hiddenField) {
            hiddenField.value = '1';

            // Hide the nearest .mb-2 container related to the clicked button
            try {
                const container = btn ? btn.closest('.mb-2') : null;
                if (container) container.style.display = 'none';
            } catch (e) {}
            showToast('Gambar akan dihapus setelah disimpan', 'warning');

            // Also attempt immediate delete via AJAX
            try {
                const soalIdElem = document.getElementById('soal_id');
                const soalId = soalIdElem ? soalIdElem.value : 0;
                const dbField = (jenis === 'pertanyaan') ? 'pertanyaan_gambar' : jenis + '_gambar';

                console.log('AJAX delete image URL:', AJAX_HANDLER_URL);
                fetch(AJAX_HANDLER_URL, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=hapus_gambar&id=${encodeURIComponent(soalId)}&field=${encodeURIComponent(dbField)}`
                })
                .then(async r => {
                    const text = await r.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Invalid JSON response: ' + text);
                    }
                })
                .then(data => {
                    if (data.success) {
                        showToast('Gambar dihapus setelah disimpan', 'success');
                    } else {
                        showToast('Gagal hapus gambar: ' + (data.message || 'server error'), 'danger');
                    }
                })
                .catch(err => {
                    console.error('AJAX delete error:', err);
                    showToast('Gagal koneksi saat hapus gambar: ' + (err.message || ''), 'danger');
                });
            } catch (e) {}
        }
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

// Setup previews
setupImagePreview('pertanyaanGambar', 'previewPertanyaan');
setupImagePreview('opsiAGambar', 'previewOpsiA');
setupImagePreview('opsiBGambar', 'previewOpsiB');
setupImagePreview('opsiCGambar', 'previewOpsiC');
setupImagePreview('opsiDGambar', 'previewOpsiD');

// Tombol hapus soal
document.getElementById('deleteSoalBtn').addEventListener('click', function() {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
});

// Konfirmasi hapus
document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    const soalId = document.getElementById('soal_id').value;
    
    // Tampilkan loading
    const deleteBtn = this;
    const originalText = deleteBtn.innerHTML;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menghapus...';
    deleteBtn.disabled = true;
    
    // AJAX untuk hapus soal
    fetch(AJAX_HANDLER_URL, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=delete_soal&id=${soalId}`
    })
    .then(async r => {
        const text = await r.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Invalid JSON response: ' + text);
        }
    })
    .then(data => {
        if (data.success) {
            showToast('Soal berhasil dihapus', 'success');
            setTimeout(() => {
                window.location.href = '?page=soal&kategori=<?php echo urlencode($soal_data['kategori']); ?>';
            }, 1500);
        } else {
            showToast('Gagal menghapus soal: ' + data.message, 'danger');
            deleteBtn.innerHTML = originalText;
            deleteBtn.disabled = false;
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
        }
    })
    .catch(error => {
        console.error('Delete soal AJAX error:', error);
        showToast('Terjadi kesalahan: ' + (error.message || error), 'danger');
        deleteBtn.innerHTML = originalText;
        deleteBtn.disabled = false;
    });
});

// Handle form submission
document.getElementById('editSoalForm').addEventListener('submit', function(e) {
    // Validasi
    const pertanyaan = this.querySelector('textarea[name="pertanyaan"]').value.trim();
    const opsiA = this.querySelector('textarea[name="opsi_a"]').value.trim();
    const opsiB = this.querySelector('textarea[name="opsi_b"]').value.trim();
    const opsiC = this.querySelector('textarea[name="opsi_c"]').value.trim();
    const opsiD = this.querySelector('textarea[name="opsi_d"]').value.trim();
    const kategori = this.querySelector('select[name="kategori"]').value;
    
    if (!pertanyaan || !opsiA || !opsiB || !opsiC || !opsiD || !kategori) {
        e.preventDefault();
        alert('Harap lengkapi semua field yang diperlukan!');
        return;
    }
    
    // Tampilkan loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
    submitBtn.disabled = true;
});

// Helper function untuk toast notification
function showToast(message, type = 'info') {
    const existingToast = document.querySelector('.custom-toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    const toast = document.createElement('div');
    toast.className = `custom-toast alert alert-${type} alert-dismissible fade show`;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentElement) {
            const bsAlert = new bootstrap.Alert(toast);
            bsAlert.close();
        }
    }, 3000);
}

// Auto-save draft (opsional)
let autoSaveTimer;
function startAutoSave() {
    autoSaveTimer = setInterval(() => {
        const formData = new FormData(document.getElementById('editSoalForm'));
        formData.append('auto_save', '1');
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            keepalive: true
        }).then(response => {
            console.log('Auto-saved at', new Date().toLocaleTimeString());
        });
    }, 30000); // Setiap 30 detik
}

// Hentikan auto-save saat form disubmit
document.getElementById('editSoalForm').addEventListener('submit', () => {
    if (autoSaveTimer) {
        clearInterval(autoSaveTimer);
    }
});

// Mulai auto-save setelah halaman dimuat
// document.addEventListener('DOMContentLoaded', startAutoSave);
</script>

<style>
.soal-item {
    transition: all 0.3s ease;
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

.custom-toast {
    animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>