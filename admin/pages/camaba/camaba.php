<?php
// Query untuk data camaba
$sql = "SELECT * FROM camaba ORDER BY id DESC";
$result = $conn->query($sql);
?>

<div class="data-table-container">
    <div class="table-header">
        <div class="table-title">
            <h3>Data Calon Mahasiswa</h3>
        </div>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchCamaba" placeholder="Cari camaba...">
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-custom" id="camabaTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Lengkap</th>
                    <th>Nomor Tes</th>
                    <th>Email</th>
                    <th>Prodi</th>
                    <th>Status</th>
                    <th>Tanggal Daftar</th>
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
                    <td><?php echo htmlspecialchars($row['prodi_pilihan']); ?></td>
                    <td>
                        <?php 
                        $status_class = '';
                        switch($row['status']) {
                            case 'baru': $status_class = 'badge-info'; break;
                            case 'sudah_ujian': $status_class = 'badge-warning'; break;
                            case 'lulus': $status_class = 'badge-success'; break;
                            case 'gagal': $status_class = 'badge-danger'; break;
                            case 'daftar_ulang': $status_class = 'badge-success'; break;
                            default: $status_class = 'badge-secondary';
                        }
                        ?>
                        <span class="badge <?php echo $status_class; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                        </span>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($row['tanggal_daftar'])); ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-action btn-view" onclick="viewCamaba(<?php echo $row['id']; ?>)" title="Lihat">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-action btn-edit" onclick="editCamaba(<?php echo $row['id']; ?>)" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-action btn-delete" onclick="deleteCamaba(<?php echo $row['id']; ?>)" title="Hapus">
                                <i class="fas fa-trash"></i>
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
function viewCamaba(id) {
    window.location.href = '?page=camaba&action=view&id=' + id;
}

function editCamaba(id) {
    window.location.href = '?page=camaba&action=edit&id=' + id;
}

function deleteCamaba(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
        window.location.href = '?page=camaba&action=delete&id=' + id;
    }
}

// Search functionality
document.getElementById('searchCamaba').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const rows = document.querySelectorAll('#camabaTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchValue) ? '' : 'none';
    });
});
</script>