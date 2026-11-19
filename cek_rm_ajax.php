<?php
header('Content-Type: application/json');
include 'koneksi.php';

$nama = isset($_GET['nama']) ? mysqli_real_escape_string($koneksi, trim($_GET['nama'])) : '';

if (empty($nama)) {
    echo json_encode(['status' => 'error', 'message' => 'Nama kosong']);
    exit;
}

$query = "SELECT nama_lengkap, no_rm, tanggal_lahir FROM pasien WHERE nama_lengkap LIKE '%$nama%' LIMIT 1";
$res = mysqli_query($koneksi, $query);

if ($res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_assoc($res);
    echo json_encode([
        'status' => 'success',
        'nama' => $row['nama_lengkap'],
        'no_rm' => !empty($row['no_rm']) ? $row['no_rm'] : ('RM-' . str_pad($row['id_pasien'], 4, '0', STR_PAD_LEFT)),
        'tgl_lahir' => $row['tanggal_lahir']
    ]);
} else {
    echo json_encode(['status' => 'not_found']);
}

