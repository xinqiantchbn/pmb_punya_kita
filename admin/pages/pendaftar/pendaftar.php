<?php
// Query untuk data pendaftar (status baru)
$sql = "SELECT * FROM camaba WHERE status = 'baru' OR status = 'belum_verifikasi' ORDER BY id DESC";
$result = $conn->query($sql);
?>

<div class="data-table-container">
    <div class="table-header">
        <div class="table-title">
            <h3>Data Pendaftar Baru</h3>
        </div>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchPendaftar" placeholder="Cari pendaftar...">
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-custom" id="pendaftarTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Lengkap</th>
                    <th>Nomor Tes</th>
                    <th>Email</th>
                    <th>Asal Sekolah</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                    <td><?php echo htmlspecialchars($row['nomor_tes']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['asal_sekolah']); ?></td>
                    <td>
                        <span class="badge badge-info">
                            <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                        </span>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($row['tanggal_daftar'])); ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-action btn-view" onclick="verifikasiPendaftar(<?php echo $row['id']; ?>)" title="Verifikasi">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn-action btn-edit" onclick="viewDetail(<?php echo $row['id']; ?>)" title="Detail">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function verifikasiPendaftar(id) {
    if (confirm('Verifikasi pendaftar ini?')) {
        window.location.href = '?page=pendaftar&action=verify&id=' + id;
    }
}

function viewDetail(id) {
    window.location.href = '?page=pendaftar&action=detail&id=' + id;
}

// Search functionality
document.getElementById('searchPendaftar').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const rows = document.querySelectorAll('#pendaftarTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchValue) ? '' : 'none';
    });
});
</script>