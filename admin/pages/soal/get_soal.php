<?php
require_once '../../config/database.php';

$id = $_GET['id'] ?? 0;

$sql = "SELECT * FROM soal WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$soal = $result->fetch_assoc();
$stmt->close();

if ($soal):
?>
<div class="soal-detail">
    <div class="mb-3">
        <span class="badge bg-primary"><?php echo htmlspecialchars($soal['kategori']); ?></span>
    </div>
    
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">Pertanyaan:</h5>
            <div class="pertanyaan-content">
                <?php echo html_entity_decode($soal['pertanyaan']); ?>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card <?php echo $soal['jawaban_benar'] == 'a' ? 'border-success' : ''; ?>">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 <?php echo $soal['jawaban_benar'] == 'a' ? 'text-success' : 'text-muted'; ?>">
                        <strong>A.</strong>
                        <?php if($soal['jawaban_benar'] == 'a'): ?>
                            <span class="badge bg-success float-end">Jawaban Benar</span>
                        <?php endif; ?>
                    </h6>
                    <p class="card-text"><?php echo htmlspecialchars($soal['opsi_a']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-3">
            <div class="card <?php echo $soal['jawaban_benar'] == 'b' ? 'border-success' : ''; ?>">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 <?php echo $soal['jawaban_benar'] == 'b' ? 'text-success' : 'text-muted'; ?>">
                        <strong>B.</strong>
                        <?php if($soal['jawaban_benar'] == 'b'): ?>
                            <span class="badge bg-success float-end">Jawaban Benar</span>
                        <?php endif; ?>
                    </h6>
                    <p class="card-text"><?php echo htmlspecialchars($soal['opsi_b']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-3">
            <div class="card <?php echo $soal['jawaban_benar'] == 'c' ? 'border-success' : ''; ?>">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 <?php echo $soal['jawaban_benar'] == 'c' ? 'text-success' : 'text-muted'; ?>">
                        <strong>C.</strong>
                        <?php if($soal['jawaban_benar'] == 'c'): ?>
                            <span class="badge bg-success float-end">Jawaban Benar</span>
                        <?php endif; ?>
                    </h6>
                    <p class="card-text"><?php echo htmlspecialchars($soal['opsi_c']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-3">
            <div class="card <?php echo $soal['jawaban_benar'] == 'd' ? 'border-success' : ''; ?>">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 <?php echo $soal['jawaban_benar'] == 'd' ? 'text-success' : 'text-muted'; ?>">
                        <strong>D.</strong>
                        <?php if($soal['jawaban_benar'] == 'd'): ?>
                            <span class="badge bg-success float-end">Jawaban Benar</span>
                        <?php endif; ?>
                    </h6>
                    <p class="card-text"><?php echo htmlspecialchars($soal['opsi_d']); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-3">
        <small class="text-muted">
            Dibuat oleh: Admin #<?php echo $soal['dibuat_oleh']; ?> | 
            Tanggal: <?php echo date('d/m/Y H:i', strtotime($soal['dibuat_pada'])); ?>
        </small>
    </div>
</div>

<style>
.soal-detail .card {
    transition: all 0.3s;
}

.soal-detail .card.border-success {
    border: 2px solid #28a745 !important;
}

.pertanyaan-content {
    font-size: 1.1rem;
    line-height: 1.6;
}
</style>
<?php else: ?>
<div class="alert alert-danger">Soal tidak ditemukan!</div>
<?php endif; ?>