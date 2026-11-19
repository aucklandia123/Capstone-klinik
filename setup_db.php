<?php
include 'koneksi.php';

$cols = [
    "no_rm VARCHAR(20) NULL DEFAULT 'RM-0891'",
    "nik VARCHAR(30) NULL DEFAULT '3204234567890001'",
    "password VARCHAR(255) NULL DEFAULT '14051988'",
    "golongan_darah VARCHAR(10) DEFAULT 'O Rh+'",
    "status_bpjs VARCHAR(50) DEFAULT 'BPJS AKTIF • MANDIRI'",
    "id_akun VARCHAR(30) DEFAULT 'USR-882910'",
    "durasi_gejala VARCHAR(50) DEFAULT '± 2 Hari'",
    "kategori_prioritas VARCHAR(50) DEFAULT 'Rawat Jalan Reguler'",
    "rujukan_poli VARCHAR(50) DEFAULT 'Poli Umum'",
    "detail_keluhan TEXT NULL"
];

foreach ($cols as $col) {
    $parts = explode(' ', $col);
    $colName = $parts[0];
    $check = mysqli_query($koneksi, "SHOW COLUMNS FROM pasien LIKE '$colName'");
    if (mysqli_num_rows($check) == 0) {
        $alter = mysqli_query($koneksi, "ALTER TABLE pasien ADD COLUMN $col");
        if ($alter) {
            echo "Added $colName successfully.\n";
        } else {
            echo "Failed adding $colName: " . mysqli_error($koneksi) . "\n";
        }
    } else {
        echo "$colName already exists.\n";
    }
}

// Check or insert sample patient Budi Santoso (RM-0891) matching the UI exactly
$cekBudi = mysqli_query($koneksi, "SELECT id_pasien FROM pasien WHERE no_rm = 'RM-0891' OR nama_lengkap = 'Budi Santoso'");
if (mysqli_num_rows($cekBudi) == 0) {
    $insertBudi = "INSERT INTO pasien (
        no_rm, nik, nama_lengkap, tanggal_lahir, gender, alamat, no_hp, password, 
        riwayat_penyakit, golongan_darah, status_bpjs, id_akun, durasi_gejala, 
        kategori_prioritas, rujukan_poli, detail_keluhan
    ) VALUES (
        'RM-0891',
        '3204234567890001',
        'Budi Santoso',
        '1988-05-14',
        'Laki-laki',
        'Jl. Medika Pratama No. 12, Kebayoran Baru, Jakarta Selatan, DKI Jakarta • Kode Pos: 12160',
        '0812-3456-7890',
        '14051988',
        'Tidak Ada Alergi Obat',
        'O Rh+',
        'BPJS AKTIF • MANDIRI',
        'USR-882910',
        '± 2 Hari',
        'Rawat Jalan Reguler',
        'Poli Umum',
        'Pasien mengeluhkan tenggorokan terasa gatal dan nyeri saat menelan sejak kemarin pagi. Suhu tubuh tercatat fluktuatif di kisaran 37.8°C. Nafsu makan sedikit menurun, tidak ada riwayat sesak napas akut.'
    )";
    mysqli_query($koneksi, $insertBudi);
    $budiId = mysqli_insert_id($koneksi);
    echo "Inserted Budi Santoso with id: $budiId\n";

    // Insert sample active visit for Budi Santoso
    $keluhanBudi = "Batuk berdahak disertai pilek dan demam ringan sejak 2 hari yang lalu";
    mysqli_query($koneksi, "INSERT INTO kunjungan (id_pasien, keluhan, status_antrean, tanggal_kunjungan) VALUES ('$budiId', '$keluhanBudi', 'Menunggu', NOW())");
    echo "Inserted active visit for Budi Santoso\n";
} else {
    $budi = mysqli_fetch_assoc($cekBudi);
    $budiId = $budi['id_pasien'];
    // Update existing Budi to match the screenshot
    $updateBudi = "UPDATE pasien SET 
        no_rm = 'RM-0891',
        nik = '3204234567890001',
        nama_lengkap = 'Budi Santoso',
        tanggal_lahir = '1988-05-14',
        gender = 'Laki-laki',
        alamat = 'Jl. Medika Pratama No. 12, Kebayoran Baru, Jakarta Selatan, DKI Jakarta • Kode Pos: 12160',
        no_hp = '0812-3456-7890',
        password = '14051988',
        riwayat_penyakit = 'Tidak Ada Alergi Obat',
        golongan_darah = 'O Rh+',
        status_bpjs = 'BPJS AKTIF • MANDIRI',
        id_akun = 'USR-882910',
        durasi_gejala = '± 2 Hari',
        kategori_prioritas = 'Rawat Jalan Reguler',
        rujukan_poli = 'Poli Umum',
        detail_keluhan = 'Pasien mengeluhkan tenggorokan terasa gatal dan nyeri saat menelan sejak kemarin pagi. Suhu tubuh tercatat fluktuatif di kisaran 37.8°C. Nafsu makan sedikit menurun, tidak ada riwayat sesak napas akut.'
        WHERE id_pasien = '$budiId'";
    mysqli_query($koneksi, $updateBudi);
    echo "Updated Budi Santoso (id: $budiId)\n";

    // Check visit
    $cekKunj = mysqli_query($koneksi, "SELECT id_kunjungan FROM kunjungan WHERE id_pasien = '$budiId' LIMIT 1");
    $keluhanBudi = "Batuk berdahak disertai pilek dan demam ringan sejak 2 hari yang lalu";
    if (mysqli_num_rows($cekKunj) == 0) {
        mysqli_query($koneksi, "INSERT INTO kunjungan (id_pasien, keluhan, status_antrean, tanggal_kunjungan) VALUES ('$budiId', '$keluhanBudi', 'Menunggu', NOW())");
    } else {
        mysqli_query($koneksi, "UPDATE kunjungan SET keluhan = '$keluhanBudi' WHERE id_pasien = '$budiId' AND (status_antrean = 'Menunggu' OR status_antrean = 'Diperiksa')");
    }
}

