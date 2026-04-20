<?php
// Query untuk hasil tes
$sql = "SELECT * FROM camaba WHERE status IN ('lulus', 'gagal', 'sudah_ujian') ORDER BY nilai_ujian DESC";
$result = $conn->query($sql);
?>

<div class="data-table-container">
    <div class="table-header">
        <div class="table-title">
            <h3>Hasil Tes Calon Mahasiswa</h3>
        </div>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchHasil" placeholder="Cari hasil...">
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-custom" id="hasilTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Nomor Tes</th>
                    <th>Nilai</th>
                    <th>Benar</th>
                    <th>Salah</th>
                    <th>Status</th>
                    <th>Tanggal Ujian</th>
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
                    <td><strong><?php echo $row['nilai_ujian']; ?></strong></td>
                    <td><?php echo $row['jawaban_benar']; ?></td>
                    <td><?php echo $row['jawaban_salah']; ?></td>
                    <td>
                        <?php if ($row['status'] == 'lulus'): ?>
                            <span class="badge badge-success">LULUS</span>
                        <?php elseif ($row['status'] == 'gagal'): ?>
                            <span class="badge badge-danger">GAGAL</span>
                        <?php else: ?>
                            <span class="badge badge-warning">SUDAH UJIAN</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo $row['tanggal_ujian'] ? date('d/m/Y H:i', strtotime($row['tanggal_ujian'])) : '-'; ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-action btn-view" onclick="viewHasil(<?php echo $row['id']; ?>)" title="Detail">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-action btn-edit" onclick="generateNIM(<?php echo $row['id']; ?>)" title="Generate NIM">
                                <i class="fas fa-id-card"></i>
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
function viewHasil(id) {
    window.location.href = '?page=hasil&action=detail&id=' + id;
}

function generateNIM(id) {
    if (confirm('Generate NIM untuk calon mahasiswa ini?')) {
        window.location.href = '?page=hasil&action=generate_nim&id=' + id;
    }
}

// Search functionality
document.getElementById('searchHasil').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const rows = document.querySelectorAll('#hasilTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchValue) ? '' : 'none';
    });
});
</script>