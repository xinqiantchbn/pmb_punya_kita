<?php

// Get all unique categories
$sql = "SELECT kategori, COUNT(*) as jumlah_soal
        FROM soal 
        GROUP BY kategori 
        ORDER BY kategori";
$result = $conn->query($sql);
$categories = $result->fetch_all(MYSQLI_ASSOC);

// Get all soal if category is selected
$selected_category = isset($_GET['kategori']) ? $_GET['kategori'] : null;
?>

<div class="container-fluid">
    <?php if (!$selected_category): ?>
    <!-- Tampilkan Card Kategori/Jurusan -->
    <div class="row">
        <div class="col-12">
            <div class="content-header">
                <div class="page-title">
                    <p>Pilih kategori/jurusan untuk melihat soal</p>
                </div>
                
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Card Tambah Kategori Baru -->
        <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
            <div class="card h-100 border-dashed">
                <div class="card-body d-flex flex-column justify-content-center align-items-center text-center" style="min-height: 200px;">
                    <div class="mb-3">
                        <i class="fas fa-plus-circle fa-3x text-primary"></i>
                    </div>
                    <h5 class="card-title">Tambah Prodi Baru</h5>
                    <p class="card-text text-muted">Buat soal baru untuk jurusan tertentu</p>
                    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#tambahKategoriModal">
                        <i class="fas fa-plus me-1"></i> Tambah
                    </button>
                </div>
            </div>
        </div>

        <!-- Card Kategori/Jurusan -->
        <?php foreach ($categories as $cat): 
            // Determine icon based on category
            $icon = 'fa-book';
            $color = 'primary';
            
            $category_map = [
                'Sistem Informasi' => ['icon' => 'fa-laptop-code', 'color' => 'info'],
                'Teknik Informatika' => ['icon' => 'fa-code', 'color' => 'success'],
                'Manajemen' => ['icon' => 'fa-chart-line', 'color' => 'warning'],
                'Akuntansi' => ['icon' => 'fa-calculator', 'color' => 'danger'],
                'Hukum' => ['icon' => 'fa-gavel', 'color' => 'dark'],
                'Kedokteran' => ['icon' => 'fa-stethoscope', 'color' => 'danger'],
                'Umum' => ['icon' => 'fa-globe', 'color' => 'secondary'],
                'Matematika' => ['icon' => 'fa-square-root-alt', 'color' => 'primary'],
                'Bahasa Inggris' => ['icon' => 'fa-language', 'color' => 'success'],
                'IPS' => ['icon' => 'fa-landmark', 'color' => 'warning'],
                'IPA' => ['icon' => 'fa-flask', 'color' => 'info'],
            ];
            
            if (isset($category_map[$cat['kategori']])) {
                $icon = $category_map[$cat['kategori']]['icon'];
                $color = $category_map[$cat['kategori']]['color'];
            }
        ?>
        <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="category-icon bg-<?php echo $color; ?> bg-opacity-10 p-3 rounded-circle">
                            <i class="fas <?php echo $icon; ?> fa-2x text-<?php echo $color; ?>"></i>
                        </div>
                        <span class="badge bg-<?php echo $color; ?>">
                            <?php echo $cat['jumlah_soal']; ?> Soal
                        </span>
                    </div>
                    
                    <h5 class="card-title"><?php echo htmlspecialchars($cat['kategori']); ?></h5>
                    <p class="card-text text-muted mb-4">
                        Total: <strong><?php echo $cat['jumlah_soal']; ?></strong> soal
                    </p>
                    
                    <div class="d-flex justify-content-between">
                        <a href="?page=soal&kategori=<?php echo urlencode($cat['kategori']); ?>" 
                           class="btn btn-outline-<?php echo $color; ?> btn-sm">
                            <i class="fas fa-eye me-1"></i> Lihat Soal
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                                    data-bs-toggle="dropdown">
                                <i class="fas fa-cog"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="?page=soal&action=tambah&kategori=<?php echo urlencode($cat['kategori']); ?>">
                                        <i class="fas fa-plus text-success me-2"></i>Tambah Soal
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" 
                                       data-bs-target="#editKategoriModal" 
                                       data-kategori="<?php echo htmlspecialchars($cat['kategori']); ?>">
                                        <i class="fas fa-edit text-primary me-2"></i>Edit Kategori
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="#" 
                                       onclick="hapusKategori('<?php echo htmlspecialchars($cat['kategori']); ?>')">
                                        <i class="fas fa-trash me-2"></i>Hapus Kategori
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Statistik Ringkas -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statistik Soal</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php
                        $total_sql = "SELECT COUNT(*) as total FROM soal";
                        $total_result = $conn->query($total_sql);
                        $stats = $total_result->fetch_assoc();
                        ?>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                                    <i class="fas fa-question-circle fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                                    <p class="text-muted mb-0">Total Soal</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3">
                                    <i class="fas fa-layer-group fa-2x text-success"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?php echo count($categories); ?></h3>
                                    <p class="text-muted mb-0">Kategori/Jurusan</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-info bg-opacity-10 p-3 rounded-circle me-3">
                                    <i class="fas fa-user-shield fa-2x text-info"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?php echo $_SESSION['admin_name'] ?? 'Admin'; ?></h3>
                                    <p class="text-muted mb-0">Admin Aktif</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Tampilkan Soal untuk Kategori Terpilih -->
    <div class="row">
        <div class="col-12">
            <div class="content-header">
                <div class="page-title">
                    <h2>Soal: <?php echo htmlspecialchars($selected_category); ?></h2>
                    <p>
                        <a href="?page=soal" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>Kembali ke Kategori
                        </a>
                    </p>
                </div>
                <div class="header-actions">
                    <div>
                        <a href="?page=soal&action=tambah&kategori=<?php echo urlencode($selected_category); ?>" 
                           class="btn btn-custom btn-primary-custom me-2">
                            <i class="fas fa-plus me-2"></i>Tambah Soal
                        </a>
                        <a href="?page=soal&action=tambah" class="btn btn-custom btn-outline-primary">
                            <i class="fas fa-plus-circle me-2"></i>Tambah di Kategori Lain
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Get soal for selected category with pagination
    $limit = 15;
    $page = isset($_GET['p']) ? intval($_GET['p']) : 1;
    $offset = ($page - 1) * $limit;

    $sql = "SELECT COUNT(*) as total FROM soal WHERE kategori = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $selected_category);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_soal = $result->fetch_assoc()['total'];
    $total_pages = ceil($total_soal / $limit);

    $sql = "SELECT s.*, a.nama_lengkap 
            FROM soal s 
            LEFT JOIN admins a ON s.dibuat_oleh = a.id 
            WHERE s.kategori = ?
            ORDER BY s.id DESC 
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $selected_category, $limit, $offset);
    $stmt->execute();
    $soal_result = $stmt->get_result();
    ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Daftar Soal 
                            <span class="badge bg-primary ms-2"><?php echo $total_soal; ?> soal</span>
                        </h5>
                        <div class="d-flex gap-2 align-items-center">
                            <div class="input-group input-group-sm" style="width: 250px;">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="searchSoal" placeholder="Cari pertanyaan atau opsi...">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">No</th>
                                    <th width="350">Pertanyaan</th>
                                    <th width="200">Opsi Jawaban</th>
                                    <th width="80">Jawaban</th>
                                    <th width="150">Dibuat</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="soalTableBody">
                                <?php if ($soal_result->num_rows > 0): 
                                    $no = $offset + 1;
                                    while($soal = $soal_result->fetch_assoc()):
                                ?>
                                <tr data-id="<?php echo $soal['id']; ?>">
                                    <td class="fw-bold"><?php echo $no++; ?></td>
                                    <td>
                                        <div class="pertanyaan-preview">
                                            <?php 
                                            $pertanyaan_text = strip_tags($soal['pertanyaan']);
                                            if (!empty($soal['pertanyaan_gambar'])) {
                                                echo '<div class="mb-1">';
                                                echo '<span class="badge bg-info bg-opacity-10 text-info border border-info">';
                                                echo '<i class="fas fa-image me-1"></i>Gambar';
                                                echo '</span>';
                                                echo '</div>';
                                            }
                                            echo substr($pertanyaan_text, 0, 120);
                                            if (strlen($pertanyaan_text) > 120) echo '...';
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="opsi-preview small">
                                            <div class="mb-1 d-flex align-items-start">
                                                <span class="badge bg-primary bg-opacity-10 text-primary me-2">A</span>
                                                <span class="text-truncate" style="max-width: 150px;">
                                                    <?php echo substr($soal['opsi_a'], 0, 40); ?>
                                                    <?php if (!empty($soal['opsi_a_gambar'])) echo '<i class="fas fa-image ms-1 text-info"></i>'; ?>
                                                </span>
                                            </div>
                                            <div class="mb-1 d-flex align-items-start">
                                                <span class="badge bg-primary bg-opacity-10 text-primary me-2">B</span>
                                                <span class="text-truncate" style="max-width: 150px;">
                                                    <?php echo substr($soal['opsi_b'], 0, 40); ?>
                                                    <?php if (!empty($soal['opsi_b_gambar'])) echo '<i class="fas fa-image ms-1 text-info"></i>'; ?>
                                                </span>
                                            </div>
                                            <div class="mb-1 d-flex align-items-start">
                                                <span class="badge bg-primary bg-opacity-10 text-primary me-2">C</span>
                                                <span class="text-truncate" style="max-width: 150px;">
                                                    <?php echo substr($soal['opsi_c'], 0, 40); ?>
                                                    <?php if (!empty($soal['opsi_c_gambar'])) echo '<i class="fas fa-image ms-1 text-info"></i>'; ?>
                                                </span>
                                            </div>
                                            <div class="mb-1 d-flex align-items-start">
                                                <span class="badge bg-primary bg-opacity-10 text-primary me-2">D</span>
                                                <span class="text-truncate" style="max-width: 150px;">
                                                    <?php echo substr($soal['opsi_d'], 0, 40); ?>
                                                    <?php if (!empty($soal['opsi_d_gambar'])) echo '<i class="fas fa-image ms-1 text-info"></i>'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-success fs-6"><?php echo strtoupper($soal['jawaban_benar']); ?></span>
                                    </td>
                                    <td>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-calendar me-1"></i><?php echo date('d/m/Y', strtotime($soal['dibuat_pada'])); ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($soal['nama_lengkap']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?page=soal&action=edit&id=<?php echo $soal['id']; ?>" 
                                               class="btn btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?page=soal&action=duplicate&id=<?php echo $soal['id']; ?>" 
                                               class="btn btn-outline-success" title="Duplikat">
                                                <i class="fas fa-copy"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger delete-soal" 
                                                    data-id="<?php echo $soal['id']; ?>" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="py-4">
                                            <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                                            <h5>Belum ada soal</h5>
                                            <p class="text-muted">Mulai tambahkan soal pertama untuk kategori ini</p>
                                            <a href="?page=soal&action=tambah&kategori=<?php echo urlencode($selected_category); ?>" 
                                               class="btn btn-primary">
                                                <i class="fas fa-plus me-1"></i>Tambah Soal
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?php echo $page == 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=soal&kategori=<?php echo urlencode($selected_category); ?>&p=<?php echo $page-1; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            <?php 
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            
                            for ($i = $start; $i <= $end; $i++): 
                            ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=soal&kategori=<?php echo urlencode($selected_category); ?>&p=<?php echo $i; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page == $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=soal&kategori=<?php echo urlencode($selected_category); ?>&p=<?php echo $page+1; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="tambahKategoriModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Prodi Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTambahKategori">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Jurusan</label>
                        <input type="text" class="form-control" name="nama_kategori" required 
                               placeholder="Contoh: Sistem Informasi, Matematika, dll">
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Kategori baru akan otomatis memiliki 1 soal contoh untuk memulai.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div class="modal fade" id="editKategoriModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditKategori">
                <div class="modal-body">
                    <input type="hidden" name="kategori_lama" id="editKategoriLama">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori/Jurusan</label>
                        <input type="text" class="form-control" name="kategori_baru" id="editKategoriBaru" required>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Mengubah nama kategori akan mempengaruhi semua soal dalam kategori ini.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Hapus soal
document.querySelectorAll('.delete-soal').forEach(btn => {
    btn.addEventListener('click', function() {
        const soalId = this.dataset.id;
        const row = this.closest('tr');
        const pertanyaan = row.querySelector('.pertanyaan-preview').textContent.trim().substring(0, 50) + '...';
        
        if (confirm(`Hapus soal:\n"${pertanyaan}"\n\nTindakan ini tidak dapat dibatalkan.`)) {
            fetch('ajax_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete_soal&id=${soalId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    row.remove();
                    
                    // Tampilkan notifikasi
                    showToast('Soal berhasil dihapus', 'success');
                    
                    // Reload jika tidak ada soal lagi
                    const remainingRows = document.querySelectorAll('#soalTableBody tr');
                    if (remainingRows.length === 0 || 
                        (remainingRows.length === 1 && remainingRows[0].querySelector('td[colspan]'))) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                } else {
                    showToast('Gagal menghapus soal: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                showToast('Terjadi kesalahan: ' + error, 'danger');
            });
        }
    });
});

// Search functionality
if (document.getElementById('searchSoal')) {
    document.getElementById('searchSoal').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#soalTableBody tr');
        
        rows.forEach(row => {
            if (row.querySelector('td[colspan]')) return; // Skip empty row
            
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
}

// Tambah kategori
document.getElementById('formTambahKategori').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'tambah_kategori');
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('tambahKategoriModal')).hide();
            showToast('Kategori berhasil ditambahkan', 'success');
            setTimeout(() => {
                window.location.href = `?page=soal&kategori=${encodeURIComponent(data.kategori)}`;
            }, 1500);
        } else {
            showToast('Gagal menambahkan kategori: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showToast('Terjadi kesalahan: ' + error, 'danger');
    });
});

// Edit kategori modal
const editKategoriModal = document.getElementById('editKategoriModal');
editKategoriModal.addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const kategori = button.getAttribute('data-kategori');
    
    document.getElementById('editKategoriLama').value = kategori;
    document.getElementById('editKategoriBaru').value = kategori;
});

// Submit edit kategori
document.getElementById('formEditKategori').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'edit_kategori');
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('editKategoriModal')).hide();
            showToast('Kategori berhasil diubah', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showToast('Gagal mengubah kategori: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showToast('Terjadi kesalahan: ' + error, 'danger');
    });
});

// Hapus kategori
function hapusKategori(kategori) {
    if (confirm(`Hapus kategori "${kategori}"?\n\nSemua soal dalam kategori ini juga akan dihapus.\nTindakan ini tidak dapat dibatalkan.`)) {
        const formData = new FormData();
        formData.append('action', 'hapus_kategori');
        formData.append('kategori', kategori);
        
        fetch('ajax_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Kategori berhasil dihapus', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showToast('Gagal menghapus kategori: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            showToast('Terjadi kesalahan: ' + error, 'danger');
        });
    }
}

// Quick navigation
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        window.location.href = '?page=soal&action=tambah';
    }
});

// Helper function untuk toast notification
function showToast(message, type = 'info') {
    // Hapus toast sebelumnya jika ada
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
    
    // Auto remove setelah 3 detik
    setTimeout(() => {
        if (toast.parentElement) {
            const bsAlert = new bootstrap.Alert(toast);
            bsAlert.close();
        }
    }, 3000);
}

// Highlight row on hover
document.addEventListener('DOMContentLoaded', function() {
    const tableRows = document.querySelectorAll('#soalTableBody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(13, 110, 253, 0.05)';
        });
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
});
</script>

<style>
.border-dashed {
    border: 2px dashed #dee2e6;
    background-color: #f8f9fa;
}

.border-dashed:hover {
    border-color: #0d6efd;
    background-color: #e7f1ff;
}

.category-icon {
    transition: transform 0.3s;
}

.card:hover .category-icon {
    transform: scale(1.1);
}

.card {
    transition: transform 0.3s, box-shadow 0.3s;
    border: 1px solid #e9ecef;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.bg-opacity-10 {
    background-color: rgba(var(--bs-primary-rgb), 0.1);
}

.table tbody tr {
    transition: background-color 0.2s;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

/* Custom toast animation */
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

/* Responsive table */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.85rem;
    }
    
    .btn-group-sm .btn {
        padding: 0.2rem 0.4rem;
        font-size: 0.7rem;
    }
    
    .card-header h5 {
        font-size: 1rem;
    }
}
</style>