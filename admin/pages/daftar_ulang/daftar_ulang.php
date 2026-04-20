<?php
// Query untuk daftar ulang
$sql = "SELECT * FROM camaba WHERE status = 'daftar_ulang' ORDER BY tanggal_daftar_ulang DESC";
$result = $conn->query($sql);
?>

<div class="data-table-container">
    <div class="table-header">
        <div class="table-title">
            <h3>Data Daftar Ulang</h3>
        </div>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchDaftarUlang" placeholder="Cari daftar ulang...">
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-custom" id="daftarUlangTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NIM</th>
                    <th>Prodi</th>
                    <th>Status Bayar</th>
                    <th>Bukti Bayar</th>
                    <th>Tanggal Daftar Ulang</th>
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
                    <td>
                        <?php if ($row['nim']): ?>
                            <span class="badge badge-success"><?php echo $row['nim']; ?></span>
                        <?php else: ?>
                            <span class="badge badge-warning">Belum ada</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['prodi_pilihan']); ?></td>
                    <td>
                        <?php 
                        $status_bayar_class = '';
                        switch($row['status_bayar']) {
                            case 'lunas': $status_bayar_class = 'badge-success'; break;
                            case 'menunggu': $status_bayar_class = 'badge-warning'; break;
                            default: $status_bayar_class = 'badge-danger';
                        }
                        ?>
                        <span class="badge <?php echo $status_bayar_class; ?>">
                            <?php echo ucfirst($row['status_bayar']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($row['bukti_bayar']): ?>
                            <button class="btn btn-sm btn-info" onclick="viewBuktiBayar('<?php echo $row['bukti_bayar']; ?>')">
                                <i class="fas fa-image"></i> Lihat
                            </button>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo $row['tanggal_daftar_ulang'] ? date('d/m/Y H:i', strtotime($row['tanggal_daftar_ulang'])) : '-'; ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-action btn-view" onclick="verifikasiBayar(<?php echo $row['id']; ?>)" title="Verifikasi">
                                <i class="fas fa-check-circle"></i>
                            </button>
                            <button class="btn-action btn-edit" onclick="editDaftarUlang(<?php echo $row['id']; ?>)" title="Edit">
                                <i class="fas fa-edit"></i>
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
function verifikasiBayar(id) {
    if (confirm('Verifikasi pembayaran daftar ulang?')) {
        window.location.href = '?page=daftar_ulang&action=verify_payment&id=' + id;
    }
}

function editDaftarUlang(id) {
    window.location.href = '?page=daftar_ulang&action=edit&id=' + id;
}

function viewBuktiBayar(imageUrl) {
    window.open(imageUrl, '_blank');
}

// Search functionality
document.getElementById('searchDaftarUlang').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const rows = document.querySelectorAll('#daftarUlangTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchValue) ? '' : 'none';
    });
});
</script>