<?php
session_start();
require_once '../config/database.php';

$nomor_tes = $_SESSION['nomor_tes'] ?? '20250208001'; // ganti dengan nomor tes Anda

echo "<div style='font-family: Poppins, sans-serif; padding: 30px; background: #f5f5f5; max-width: 1200px; margin: 0 auto;'>";
echo "<h2 style='color: #c62828;'>🔍 DEBUG PERBANDINGAN KATEGORI</h2>";

// 1. Ambil data camaba
$camaba = $conn->query("
    SELECT prodi_pilihan, HEX(prodi_pilihan) as hex_prodi, LENGTH(prodi_pilihan) as len_prodi
    FROM camaba 
    WHERE nomor_tes = '$nomor_tes'
")->fetch_assoc();

echo "<div style='background: white; padding: 20px; margin: 20px 0; border-radius: 10px; border-left: 5px solid #c62828;'>";
echo "<h3>📌 DATA CAMABA</h3>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Field</th><th>Value</th><th>Length</th><th>Hex</th><th>ASCII Codes</th></tr>";
echo "<tr>";
echo "<td><strong>prodi_pilihan</strong></td>";
echo "<td><strong style='color: #c62828;'>'" . htmlspecialchars($camaba['prodi_pilihan']) . "'</strong></td>";
echo "<td>" . $camaba['len_prodi'] . "</td>";
echo "<td style='font-family: monospace; font-size: 12px;'>" . $camaba['hex_prodi'] . "</td>";

// Tampilkan ASCII codes
$ascii_codes = '';
for ($i = 0; $i < strlen($camaba['prodi_pilihan']); $i++) {
    $ascii_codes .= ord($camaba['prodi_pilihan'][$i]) . ' ';
}
echo "<td style='font-family: monospace;'>" . $ascii_codes . "</td>";
echo "</tr>";
echo "</table>";
echo "</div>";

// 2. Ambil semua kategori dari tabel soal
echo "<div style='background: white; padding: 20px; margin: 20px 0; border-radius: 10px; border-left: 5px solid #2196F3;'>";
echo "<h3>📚 DATA KATEGORI SOAL</h3>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>No</th><th>kategori</th><th>Length</th><th>Hex</th><th>ASCII Codes</th><th>Match?</th></tr>";

$result = $conn->query("SELECT DISTINCT kategori, HEX(kategori) as hex_kat, LENGTH(kategori) as len_kat FROM soal ORDER BY kategori");
$no = 1;
$found_match = false;

while ($row = $result->fetch_assoc()) {
    $match = '❌';
    $bg_color = '';
    
    // Bandingkan dengan berbagai metode
    $prodi_clean = trim($camaba['prodi_pilihan']);
    $kategori_clean = trim($row['kategori']);
    
    if ($prodi_clean === $kategori_clean) {
        $match = '✅ EXACT MATCH (trim)';
        $found_match = true;
        $bg_color = 'background: #e8f5e9;';
    } elseif (strcasecmp($prodi_clean, $kategori_clean) == 0) {
        $match = '✅ CASE INSENSITIVE';
        $found_match = true;
        $bg_color = 'background: #e8f5e9;';
    } elseif (str_replace(' ', '', $prodi_clean) == str_replace(' ', '', $kategori_clean)) {
        $match = '✅ WITHOUT SPACES';
        $found_match = true;
        $bg_color = 'background: #e8f5e9;';
    } elseif (strpos($kategori_clean, $prodi_clean) !== false || strpos($prodi_clean, $kategori_clean) !== false) {
        $match = '⚠️ PARTIAL MATCH';
        $bg_color = 'background: #fff3e0;';
    }
    
    // Tampilkan ASCII codes untuk kategori
    $ascii_kat = '';
    for ($i = 0; $i < strlen($row['kategori']); $i++) {
        $ascii_kat .= ord($row['kategori'][$i]) . ' ';
    }
    
    echo "<tr style='$bg_color'>";
    echo "<td>" . $no++ . "</td>";
    echo "<td><strong>'" . htmlspecialchars($row['kategori']) . "'</strong></td>";
    echo "<td>" . $row['len_kat'] . "</td>";
    echo "<td style='font-family: monospace; font-size: 12px;'>" . $row['hex_kat'] . "</td>";
    echo "<td style='font-family: monospace;'>" . $ascii_kat . "</td>";
    echo "<td style='font-weight: bold;'>" . $match . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// 3. Test query langsung
echo "<div style='background: white; padding: 20px; margin: 20px 0; border-radius: 10px; border-left: 5px solid #4CAF50;'>";
echo "<h3>⚡ TEST QUERY LANGSUNG</h3>";

// Test dengan berbagai variasi
$variasi = [
    "Sistem Informasi",
    "Sistem Informasi ", // dengan spasi
    " Sistem Informasi", // dengan spasi depan
    "Sistem  Informasi", // spasi ganda
    "sistem informasi",
    "SISTEM INFORMASI",
    "SistemInformasi",
    "Sistem%Informasi", // untuk LIKE
];

foreach ($variasi as $v) {
    $escaped = $conn->real_escape_string($v);
    
    // Exact match
    $result = $conn->query("SELECT COUNT(*) as total FROM soal WHERE kategori = '$escaped'");
    $exact = $result->fetch_assoc()['total'];
    
    // LIKE match
    $like = "%" . str_replace(' ', '%', $escaped) . "%";
    $result = $conn->query("SELECT COUNT(*) as total FROM soal WHERE kategori LIKE '$like'");
    $like_count = $result->fetch_assoc()['total'];
    
    echo "<p style='margin-bottom: 5px;'><strong>Query:</strong> kategori = '" . htmlspecialchars($v) . "'</p>";
    echo "<p style='margin-top: 0; margin-left: 20px;'>➡️ Exact match: <strong>" . $exact . "</strong> soal, LIKE match: <strong>" . $like_count . "</strong> soal</p>";
}

echo "</div>";

// 4. Solusi perbaikan
echo "<div style='background: white; padding: 20px; margin: 20px 0; border-radius: 10px; border-left: 5px solid #FF9800;'>";
echo "<h3>🛠️ SOLUSI PERBAIKAN</h3>";

// Cek apakah ada spasi di belakang
$prodi_hex = $camaba['hex_prodi'];
$prodi_len = $camaba['len_prodi'];
$prodi_value = $camaba['prodi_pilihan'];

echo "<p><strong>Masalah terdeteksi:</strong></p>";

if (preg_match('/20$/', $prodi_hex)) {
    echo "<p style='color: #c62828;'>⚠️ Ada spasi di belakang nilai prodi_pilihan! (Hex berakhir dengan 20)</p>";
    $fixed_prodi = rtrim($prodi_value);
    echo "<p>✅ Perbaikan: UPDATE camaba SET prodi_pilihan = '" . $fixed_prodi . "' WHERE nomor_tes = '$nomor_tes';</p>";
}

if (preg_match('/^20/', $prodi_hex)) {
    echo "<p style='color: #c62828;'>⚠️ Ada spasi di depan nilai prodi_pilihan! (Hex dimulai dengan 20)</p>";
    $fixed_prodi = ltrim($prodi_value);
    echo "<p>✅ Perbaikan: UPDATE camaba SET prodi_pilihan = '" . $fixed_prodi . "' WHERE nomor_tes = '$nomor_tes';</p>";
}

// Cek karakter non-ASCII
for ($i = 0; $i < strlen($prodi_value); $i++) {
    $ord = ord($prodi_value[$i]);
    if ($ord > 127) {
        echo "<p style='color: #c62828;'>⚠️ Ada karakter non-ASCII pada posisi " . ($i+1) . " (ASCII: $ord)</p>";
        break;
    }
}

// Solusi query perbaikan untuk semua data
echo "<p style='margin-top: 20px;'><strong>📝 Query perbaikan untuk SEMUA data camaba:</strong></p>";
echo "<pre style='background: #f0f0f0; padding: 15px; border-radius: 8px;'>";
echo "-- Hapus spasi di depan/belakang\n";
echo "UPDATE camaba SET prodi_pilihan = TRIM(prodi_pilihan);\n\n";
echo "-- Hapus multiple spasi\n";
echo "UPDATE camaba SET prodi_pilihan = REGEXP_REPLACE(prodi_pilihan, '\\s+', ' ');\n\n";
echo "-- Normalisasi untuk masing-masing prodi\n";
echo "UPDATE camaba SET prodi_pilihan = 'Sistem Informasi' WHERE TRIM(UPPER(prodi_pilihan)) LIKE '%SISTEM%INFORMASI%';\n";
echo "UPDATE camaba SET prodi_pilihan = 'Teknik Informatika' WHERE TRIM(UPPER(prodi_pilihan)) LIKE '%TEKNIK%INFORMATIKA%';\n";
echo "UPDATE camaba SET prodi_pilihan = 'Manajemen' WHERE TRIM(UPPER(prodi_pilihan)) LIKE '%MANAJEMEN%';\n";
echo "UPDATE camaba SET prodi_pilihan = 'Akuntansi' WHERE TRIM(UPPER(prodi_pilihan)) LIKE '%AKUNTANSI%';\n";
echo "UPDATE camaba SET prodi_pilihan = 'Ilmu Komunikasi' WHERE TRIM(UPPER(prodi_pilihan)) LIKE '%ILMU%KOMUNIKASI%';\n";
echo "UPDATE camaba SET prodi_pilihan = 'Desain Komunikasi Visual' WHERE TRIM(UPPER(prodi_pilihan)) LIKE '%DESAIN%KOMUNIKASI%VISUAL%';\n";
echo "</pre>";

echo "</div>";

// 5. Rekomendasi kode perbaikan untuk file ujian
echo "<div style='background: white; padding: 20px; margin: 20px 0; border-radius: 10px; border-left: 5px solid #4CAF50;'>";
echo "<h3>💡 REKOMENDASI KODE UNTUK FILE UJIAN</h3>";
echo "<pre style='background: #f0f0f0; padding: 15px; border-radius: 8px;'>";
echo "// ============================================\n";
echo "// CARA PALING AMBIL SOAL - PAKSA LANGSUNG DENGAN LIKE\n";
echo "// ============================================\n";
echo "\$prodi = \$camaba['prodi_pilihan'];\n";
echo "\$prodi_clean = trim(\$prodi);\n";
echo "\n";
echo "// Ambil kata kunci\n";
echo "if (stripos(\$prodi_clean, 'sistem') !== false && stripos(\$prodi_clean, 'informasi') !== false) {\n";
echo "    \$kategori = 'Sistem Informasi';\n";
echo "} elseif (stripos(\$prodi_clean, 'teknik') !== false && stripos(\$prodi_clean, 'informatika') !== false) {\n";
echo "    \$kategori = 'Teknik Informatika';\n";
echo "} elseif (stripos(\$prodi_clean, 'manajemen') !== false) {\n";
echo "    \$kategori = 'Manajemen';\n";
echo "} elseif (stripos(\$prodi_clean, 'akuntansi') !== false) {\n";
echo "    \$kategori = 'Akuntansi';\n";
echo "} elseif (stripos(\$prodi_clean, 'ilmu') !== false && stripos(\$prodi_clean, 'komunikasi') !== false) {\n";
echo "    \$kategori = 'Ilmu Komunikasi';\n";
echo "} elseif (stripos(\$prodi_clean, 'desain') !== false && stripos(\$prodi_clean, 'komunikasi') !== false && stripos(\$prodi_clean, 'visual') !== false) {\n";
echo "    \$kategori = 'Desain Komunikasi Visual';\n";
echo "} else {\n";
echo "    \$kategori = 'Umum';\n";
echo "}\n";
echo "\n";
echo "// Ambil soal dengan LIKE (paling aman)\n";
echo "\$stmt = \$conn->prepare(\"\n";
echo "    SELECT id FROM soal \n";
echo "    WHERE kategori LIKE ? \n";
echo "    ORDER BY RAND()\n";
echo "\");\n";
echo "\$like = \"%\$kategori%\";\n";
echo "\$stmt->bind_param(\"s\", \$like);\n";
echo "\$stmt->execute();\n";
echo "</pre>";
echo "</div>";

echo "</div>"; // tutup div utama
?>