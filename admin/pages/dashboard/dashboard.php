<?php

// Query untuk statistik
$stats = [];

// Total camaba
$sql = "SELECT COUNT(*) as total FROM camaba";
$result = $conn->query($sql);
$stats['total_camaba'] = $result->fetch_assoc()['total'];

// Pendaftar baru (status baru)
$sql = "SELECT COUNT(*) as total FROM camaba WHERE status = 'baru'";
$result = $conn->query($sql);
$stats['pendaftar_baru'] = $result->fetch_assoc()['total'];

// Sudah ujian
$sql = "SELECT COUNT(*) as total FROM camaba WHERE status = 'sudah_ujian'";
$result = $conn->query($sql);
$stats['sudah_ujian'] = $result->fetch_assoc()['total'];

// Daftar ulang
$sql = "SELECT COUNT(*) as total FROM camaba WHERE status = 'daftar_ulang'";
$result = $conn->query($sql);
$stats['daftar_ulang'] = $result->fetch_assoc()['total'];
?>

<!-- Stats Cards -->
<div class="stats-cards">
    <div class="stat-card">
        <div class="stat-icon stat-icon-1">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-value"><?php echo $stats['total_camaba']; ?></div>
        <div class="stat-label">Total Calon Mahasiswa</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon stat-icon-2">
            <i class="fas fa-user-plus"></i>
        </div>
        <div class="stat-value"><?php echo $stats['pendaftar_baru']; ?></div>
        <div class="stat-label">Pendaftar Baru</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon stat-icon-3">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <div class="stat-value"><?php echo $stats['sudah_ujian']; ?></div>
        <div class="stat-label">Sudah Ujian</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon stat-icon-4">
            <i class="fas fa-file-contract"></i>
        </div>
        <div class="stat-value"><?php echo $stats['daftar_ulang']; ?></div>
        <div class="stat-label">Daftar Ulang</div>
    </div>
</div>

<!-- Recent Activity -->
<div class="data-table-container">
    <div class="table-header">
        <div class="table-title">
            <h3>Aktivitas Terbaru</h3>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-custom">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Nomor Tes</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT * FROM camaba ORDER BY tanggal_daftar DESC LIMIT 10";
                $result = $conn->query($sql);
                $no = 1;
                
                while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                    <td><?php echo htmlspecialchars($row['nomor_tes']); ?></td>
                    <td>
                        <?php 
                        $status_class = '';
                        switch($row['status']) {
                            case 'baru': $status_class = 'badge-info'; break;
                            case 'sudah_ujian': $status_class = 'badge-warning'; break;
                            case 'lulus': $status_class = 'badge-success'; break;
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
                            <button class="btn-action btn-view" title="Lihat">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-action btn-edit" title="Edit">
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