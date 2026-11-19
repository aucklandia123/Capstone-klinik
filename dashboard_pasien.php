<?php
session_start();
include 'koneksi.php';

// Ambil ID pasien dari session jika ada, atau default ke pasien Budi Santoso (RM-0891) untuk kemudahan demo
$id_pasien = isset($_SESSION['id_pasien']) ? intval($_SESSION['id_pasien']) : 1;

// Handle Update Data Pasien
$pesan_sukses = "";
if (isset($_GET['register']) && $_GET['register'] == 'success') {
    $pesan_sukses = "🎉 Pendaftaran akun baru berhasil! Selamat datang di portal pasien Klinik Sehat Pratama.";
}
if (isset($_POST['update_profil'])) {
    $alamat_baru = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $no_hp_baru = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $keluhan_baru = mysqli_real_escape_string($koneksi, $_POST['keluhan']);
    $detail_keluhan_baru = mysqli_real_escape_string($koneksi, $_POST['detail_keluhan']);

    mysqli_query($koneksi, "UPDATE pasien SET 
        alamat = '$alamat_baru', 
        no_hp = '$no_hp_baru', 
        detail_keluhan = '$detail_keluhan_baru' 
        WHERE id_pasien = '$id_pasien'");

    // Update keluhan pada kunjungan hari ini / aktif
    if (!empty($keluhan_baru)) {
        mysqli_query($koneksi, "UPDATE kunjungan SET keluhan = '$keluhan_baru' WHERE id_pasien = '$id_pasien' ORDER BY id_kunjungan DESC LIMIT 1");
    }
    $pesan_sukses = "Data profil dan keluhan berhasil diperbarui!";
}

// Handle Ambil Antrean Baru
if (isset($_POST['daftar_antrean_baru'])) {
    $keluhan_antre = mysqli_real_escape_string($koneksi, $_POST['keluhan_antre']);
    $detail_antre = mysqli_real_escape_string($koneksi, $_POST['detail_antre']);
    
    // Simpan ke kunjungan
    mysqli_query($koneksi, "INSERT INTO kunjungan (id_pasien, keluhan, status_antrean, tanggal_kunjungan) VALUES ('$id_pasien', '$keluhan_antre', 'Menunggu', NOW())");
    mysqli_query($koneksi, "UPDATE pasien SET detail_keluhan = '$detail_antre' WHERE id_pasien = '$id_pasien'");
    $pesan_sukses = "Pendaftaran berobat berhasil! Anda telah masuk antrean hari ini.";
}

// Query data pasien
$queryPasien = "SELECT * FROM pasien WHERE id_pasien = '$id_pasien' LIMIT 1";
$resPasien = mysqli_query($koneksi, $queryPasien);

if (!$resPasien || mysqli_num_rows($resPasien) == 0) {
    // Fallback jika id tidak ditemukan
    $resPasien = mysqli_query($koneksi, "SELECT * FROM pasien WHERE no_rm = 'RM-0891' LIMIT 1");
}
$pasien = mysqli_fetch_assoc($resPasien);

// Ambil kunjungan terkini / keluhan
$queryKunjungan = "SELECT * FROM kunjungan WHERE id_pasien = '{$pasien['id_pasien']}' ORDER BY id_kunjungan DESC LIMIT 1";
$resKunj = mysqli_query($koneksi, $queryKunjungan);
$kunjunganTerakhir = ($resKunj && mysqli_num_rows($resKunj) > 0) ? mysqli_fetch_assoc($resKunj) : null;

// Query riwayat semua kunjungan
$queryRiwayat = "SELECT * FROM kunjungan WHERE id_pasien = '{$pasien['id_pasien']}' ORDER BY id_kunjungan DESC";
$resRiwayat = mysqli_query($koneksi, $queryRiwayat);

// Format Inisial Avatar
$nama_arr = explode(' ', trim($pasien['nama_lengkap']));
$initials = '';
if (count($nama_arr) >= 2) {
    $initials = strtoupper(substr($nama_arr[0], 0, 1) . substr($nama_arr[1], 0, 1));
} else {
    $initials = strtoupper(substr($pasien['nama_lengkap'], 0, 2));
}

// Format Tanggal Lahir dan Usia
$tgl_lahir = $pasien['tanggal_lahir'];
$tgl_lahir_formatted = "14 Mei 1988";
$usia = 36;
if ($tgl_lahir && $tgl_lahir != '0000-00-00') {
    $tgl_obj = new DateTime($tgl_lahir);
    $sekarang = new DateTime();
    $usia = $sekarang->diff($tgl_obj)->y;
    // Format nama bulan Indonesia
    $bulanIndo = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $tgl_lahir_formatted = $tgl_obj->format('j') . ' ' . $bulanIndo[(int)$tgl_obj->format('n')] . ' ' . $tgl_obj->format('Y');
}

// Fallback nilai data agar persis dengan UI
$no_rm = !empty($pasien['no_rm']) ? $pasien['no_rm'] : 'RM-0891';
$no_hp = !empty($pasien['no_hp']) && $pasien['no_hp'] != '-' ? $pasien['no_hp'] : '0812-3456-7890';
$status_bpjs = !empty($pasien['status_bpjs']) ? $pasien['status_bpjs'] : 'BPJS AKTIF • MANDIRI';
$golongan_darah = !empty($pasien['golongan_darah']) ? $pasien['golongan_darah'] : 'O Rh+';
$riwayat_alergi = (!empty($pasien['riwayat_penyakit']) && $pasien['riwayat_penyakit'] != '-') ? $pasien['riwayat_penyakit'] : 'Tidak Ada Alergi Obat';
$id_akun = !empty($pasien['id_akun']) ? $pasien['id_akun'] : 'USR-882910';
$durasi_gejala = !empty($pasien['durasi_gejala']) ? $pasien['durasi_gejala'] : '± 2 Hari';
$kategori_prioritas = !empty($pasien['kategori_prioritas']) ? $pasien['kategori_prioritas'] : 'Rawat Jalan Reguler';
$rujukan_poli = !empty($pasien['rujukan_poli']) ? $pasien['rujukan_poli'] : 'Poli Umum';

// Keluhan aktif
$judul_keluhan = ($kunjunganTerakhir && !empty($kunjunganTerakhir['keluhan']) && $kunjunganTerakhir['keluhan'] != '-') 
    ? $kunjunganTerakhir['keluhan'] 
    : "Batuk berdahak disertai pilek dan demam ringan sejak 2 hari yang lalu";

$detail_keluhan = !empty($pasien['detail_keluhan']) 
    ? $pasien['detail_keluhan'] 
    : "Pasien mengeluhkan tenggorokan terasa gatal dan nyeri saat menelan sejak kemarin pagi. Suhu tubuh tercatat fluktuatif di kisaran 37.8°C. Nafsu makan sedikit menurun, tidak ada riwayat sesak napas akut.";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pasien - <?php echo htmlspecialchars($pasien['nama_lengkap']); ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        body {
            background-color: #f8fafd;
            color: #1e293b;
            padding: 24px 20px 50px 20px;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* 1. TOP HEADER PROFILE CARD */
        .card-profile-header {
            background: #ffffff;
            border-radius: 16px;
            padding: 20px 26px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #eef2f6;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        }
        .profile-left {
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .avatar-box {
            position: relative;
            width: 62px;
            height: 62px;
            background: #0077ff;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 22px;
            font-weight: 800;
            box-shadow: 0 4px 12px rgba(0, 119, 255, 0.2);
            flex-shrink: 0;
        }
        .verified-badge {
            position: absolute;
            bottom: -4px;
            right: -4px;
            width: 20px;
            height: 20px;
            background: #00c6ff;
            border: 2px solid #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .verified-badge svg {
            width: 11px;
            height: 11px;
            stroke: #ffffff;
            stroke-width: 3;
        }
        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .profile-name-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .profile-name {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.3px;
        }
        .badge-bpjs {
            background: #e0f2fe;
            color: #0284c7;
            font-size: 10.5px;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 20px;
            letter-spacing: 0.3px;
        }
        .badge-terdaftar {
            background: #fef3c7;
            color: #b45309;
            font-size: 10.5px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
        }
        .profile-subtext {
            font-size: 13px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        .profile-subtext .dot {
            color: #cbd5e1;
            font-size: 14px;
        }
        .profile-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-outline-action {
            background: #ffffff;
            border: 1.5px solid #bfdbfe;
            color: #0284c7;
            padding: 9px 16px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-outline-action:hover {
            background: #f0f9ff;
            border-color: #0284c7;
        }
        .btn-primary-action {
            background: #0077ff;
            border: none;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 119, 255, 0.2);
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-primary-action:hover {
            background: #0066ee;
            transform: translateY(-1px);
        }
        .btn-logout {
            color: #94a3b8;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: color 0.2s;
        }
        .btn-logout:hover {
            color: #ef4444;
            background: #fee2e2;
        }

        /* 2. MIDDLE SECTION: 2 COLUMNS */
        .middle-grid {
            display: grid;
            grid-template-columns: 1.8fr 1fr;
            gap: 20px;
        }

        .card-white {
            background: #ffffff;
            border-radius: 16px;
            padding: 22px;
            border: 1px solid #eef2f6;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .card-header-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
        }
        .card-header-title svg {
            width: 18px;
            height: 18px;
            color: #0284c7;
        }
        .badge-cyan-pill {
            background: #e0f2fe;
            color: #0284c7;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
        }

        /* HIGHLIGHT BOX KELUHAN */
        .highlight-keluhan-box {
            background: #f0f9ff;
            border-radius: 14px;
            padding: 16px;
            display: flex;
            gap: 14px;
            margin-bottom: 16px;
        }
        .keluhan-avatar {
            width: 40px;
            height: 40px;
            background: #38bdf8;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .keluhan-text-wrap {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .keluhan-main-title {
            font-size: 14.5px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.35;
        }
        .keluhan-description {
            font-size: 12.5px;
            color: #475569;
            line-height: 1.55;
        }

        /* 3-COLUMN STATS DI BAWAH KELUHAN */
        .stats-3-col {
            display: grid;
            grid-template-columns: 1fr 1.2fr 1fr;
            gap: 12px;
            padding-top: 10px;
            border-top: 1px solid #f1f5f9;
        }
        .stat-item {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .stat-label {
            font-size: 10px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-value {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
        }
        .stat-value.blue {
            color: #0284c7;
        }

        /* STATUS KEPESERTAAN ROWS */
        .status-rows {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-top: 6px;
        }
        .status-row-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13.5px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f8fafc;
        }
        .status-row-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .status-label {
            color: #64748b;
            font-weight: 500;
        }
        .badge-aktif {
            background: #e0f2fe;
            color: #0284c7;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 12px;
            border-radius: 20px;
        }
        .blood-type {
            font-size: 15px;
            font-weight: 800;
            color: #0077ff;
        }
        .allergy-text {
            color: #1e293b;
            font-weight: 600;
        }

        /* 3. BOTTOM SECTION: DATA PRIBADI & KONTAK PASIEN */
        .card-data-pribadi {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #eef2f6;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        }
        .grid-data-pribadi {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-top: 16px;
        }
        .tile-data {
            background: #f8fafd;
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            border: 1px solid #f1f5f9;
        }
        .tile-data.span-2 {
            grid-column: span 2;
        }
        .tile-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 10.5px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .tile-label svg {
            width: 14px;
            height: 14px;
            color: #0284c7;
        }
        .tile-value {
            font-size: 15.5px;
            font-weight: 800;
            color: #0f172a;
        }
        .tile-value.phone-blue {
            color: #0077ff;
        }
        .tile-subtext {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }
        .tile-subtext.verified-green {
            color: #16a34a;
            font-weight: 600;
        }

        /* MODAL STYLES */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            z-index: 999;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }
        .modal-card {
            background: #ffffff;
            border-radius: 18px;
            max-width: 520px;
            width: 90%;
            padding: 26px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15);
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-close {
            position: absolute;
            top: 18px;
            right: 18px;
            background: #f1f5f9;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
        }

        /* KARTU DIGITAL PASIEN ELEGAN */
        .digital-id-card {
            background: linear-gradient(135deg, #0062e2 0%, #0099ff 100%);
            border-radius: 16px;
            padding: 22px;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 119, 255, 0.35);
            margin: 15px 0;
        }
        .digital-id-card::after {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 180px;
            height: 180px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
        }
        .card-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .card-clinic-title {
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .card-clinic-sub {
            font-size: 9px;
            color: #e0f2fe;
            letter-spacing: 0.5px;
        }
        .card-chip {
            width: 36px;
            height: 26px;
            background: linear-gradient(135deg, #ffd700 0%, #ffae00 100%);
            border-radius: 5px;
            opacity: 0.85;
        }
        .card-mid-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 16px;
        }
        .card-patient-name {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .card-rm-code {
            font-size: 15px;
            font-weight: 700;
            color: #e0f2fe;
            letter-spacing: 2px;
            font-family: monospace;
        }

        .alert-success-banner {
            background: #dcfce7;
            color: #166534;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            border-left: 4px solid #22c55e;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        @media (max-width: 850px) {
            .middle-grid {
                grid-template-columns: 1fr;
            }
            .grid-data-pribadi {
                grid-template-columns: 1fr;
            }
            .tile-data.span-2 {
                grid-column: span 1;
            }
            .card-profile-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            .profile-actions {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>

    <div class="container">

        <!-- NOTIFIKASI SUKSES JIKA ADA PERUBAHAN DATA -->
        <?php if (!empty($pesan_sukses)): ?>
            <div class="alert-success-banner">
                <span>✓ <?php echo $pesan_sukses; ?></span>
                <span style="cursor:pointer;" onclick="this.parentElement.style.display='none';">&times;</span>
            </div>
        <?php endif; ?>

        <!-- 1. KARTU HEADER PROFIL PASIEN -->
        <div class="card-profile-header">
            <div class="profile-left">
                <!-- Avatar Inisial Pasien -->
                <div class="avatar-box">
                    <?php echo $initials; ?>
                    <div class="verified-badge">
                        <svg viewBox="0 0 24 24" fill="none">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                </div>

                <!-- Info Profil Utama -->
                <div class="profile-info">
                    <div class="profile-name-row">
                        <h2 class="profile-name"><?php echo htmlspecialchars($pasien['nama_lengkap']); ?></h2>
                        <span class="badge-bpjs"><?php echo htmlspecialchars($status_bpjs); ?></span>
                        <span class="badge-terdaftar">Pasien Terdaftar</span>
                    </div>

                    <div class="profile-subtext">
                        <span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -2px;"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                            <strong style="color:#0284c7;"><?php echo htmlspecialchars($no_rm); ?></strong>
                        </span>
                        <span class="dot">•</span>
                        <span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -2px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <?php echo $tgl_lahir_formatted; ?> (<?php echo $usia; ?> Tahun)
                        </span>
                        <span class="dot">•</span>
                        <span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -2px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <?php echo htmlspecialchars($no_hp); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi di Kanan -->
            <div class="profile-actions">
                <button type="button" class="btn-outline-action" onclick="openModalEdit()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    Perbarui Data
                </button>

                <button type="button" class="btn-primary-action" onclick="openModalKartu()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="3"/><line x1="7" y1="8" x2="11" y2="8"/><line x1="7" y1="12" x2="17" y2="12"/><line x1="7" y1="16" x2="14" y2="16"/></svg>
                    Kartu Pasien Digital
                </button>

                <a href="logout.php" class="btn-logout" title="Keluar dari akun">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Keluar
                </a>
            </div>
        </div>

        <!-- 2. MIDDLE SECTION: 2 KOLOM (KELUHAN PASIEN SAAT INI + STATUS KEPESERTAAN) -->
        <div class="middle-grid">
            <!-- Kolom Kiri: Keluhan Pasien Saat Ini -->
            <div class="card-white">
                <div>
                    <div class="card-header-row">
                        <div class="card-header-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                                <line x1="9" y1="12" x2="15" y2="12"></line>
                                <line x1="12" y1="9" x2="12" y2="15"></line>
                            </svg>
                            Keluhan Pasien Saat Ini
                        </div>
                        <span class="badge-cyan-pill">Keluhan Utama Aktif</span>
                    </div>

                    <!-- Highlight Box Biru Muda -->
                    <div class="highlight-keluhan-box">
                        <div class="keluhan-avatar">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                                <line x1="9" y1="9" x2="9.01" y2="9"></line>
                                <line x1="15" y1="9" x2="15.01" y2="9"></line>
                            </svg>
                        </div>
                        <div class="keluhan-text-wrap">
                            <h4 class="keluhan-main-title"><?php echo htmlspecialchars($judul_keluhan); ?></h4>
                            <p class="keluhan-description"><?php echo htmlspecialchars($detail_keluhan); ?></p>
                        </div>
                    </div>
                </div>

                <!-- 3 Kolom Info Durasi / Prioritas / Poliklinik -->
                <div class="stats-3-col">
                    <div class="stat-item">
                        <span class="stat-label">DURASI GEJALA</span>
                        <span class="stat-value"><?php echo htmlspecialchars($durasi_gejala); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">KATEGORI PRIORITAS</span>
                        <span class="stat-value blue"><?php echo htmlspecialchars($kategori_prioritas); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">RUJUKAN POLIKLINIK</span>
                        <span class="stat-value blue"><?php echo htmlspecialchars($rujukan_poli); ?></span>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Status Kepesertaan Pasien -->
            <div class="card-white">
                <div>
                    <div class="card-header-row">
                        <div class="card-header-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                            Status Kepesertaan Pasien
                        </div>
                    </div>

                    <div class="status-rows">
                        <div class="status-row-item">
                            <span class="status-label">Status Akun</span>
                            <span class="badge-aktif">Aktif</span>
                        </div>
                        <div class="status-row-item">
                            <span class="status-label">Golongan Darah</span>
                            <span class="blood-type"><?php echo htmlspecialchars($golongan_darah); ?></span>
                        </div>
                        <div class="status-row-item">
                            <span class="status-label">Riwayat Alergi</span>
                            <span class="allergy-text"><?php echo htmlspecialchars($riwayat_alergi); ?></span>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <button type="button" onclick="openModalAntrean()" style="width:100%; background:#f0f9ff; color:#0284c7; border:1px dashed #0284c7; padding:9px; border-radius:8px; font-weight:700; cursor:pointer; font-size:12px;">
                        + Daftar Kunjungan Baru
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. BOTTOM SECTION: DATA PRIBADI & KONTAK PASIEN -->
        <div class="card-data-pribadi">
            <div class="card-header-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Data Pribadi & Kontak Pasien
            </div>

            <div class="grid-data-pribadi">
                <!-- Tile 1: Nama Lengkap -->
                <div class="tile-data">
                    <span class="tile-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        NAMA LENGKAP
                    </span>
                    <span class="tile-value"><?php echo htmlspecialchars($pasien['nama_lengkap']); ?></span>
                    <span class="tile-subtext">Jenis Kelamin: <?php echo htmlspecialchars($pasien['gender']); ?></span>
                </div>

                <!-- Tile 2: Tanggal Lahir & Usia -->
                <div class="tile-data">
                    <span class="tile-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        TANGGAL LAHIR & USIA
                    </span>
                    <span class="tile-value"><?php echo $tgl_lahir_formatted; ?></span>
                    <span class="tile-subtext">Usia: <?php echo $usia; ?> Tahun</span>
                </div>

                <!-- Tile 3: Alamat Domisili (Full Width) -->
                <div class="tile-data span-2">
                    <span class="tile-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        ALAMAT DOMISILI PASIEN
                    </span>
                    <span class="tile-value" style="font-size: 14.5px;"><?php echo htmlspecialchars($pasien['alamat']); ?></span>
                    <span class="tile-subtext">DKI Jakarta • Kode Pos: 12160</span>
                </div>

                <!-- Tile 4: Nomor Telepon / HP -->
                <div class="tile-data">
                    <span class="tile-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                        NOMOR TELEPON / HP
                    </span>
                    <span class="tile-value phone-blue"><?php echo htmlspecialchars($no_hp); ?></span>
                    <span class="tile-subtext verified-green">Terverifikasi via WhatsApp</span>
                </div>

                <!-- Tile 5: Nomor Rekam Medis (No. RM) -->
                <div class="tile-data">
                    <span class="tile-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        NOMOR REKAM MEDIS (NO. RM)
                    </span>
                    <span class="tile-value"><?php echo htmlspecialchars($no_rm); ?></span>
                    <span class="tile-subtext">ID Akun: <?php echo htmlspecialchars($id_akun); ?></span>
                </div>
            </div>
        </div>

        <!-- 4. SECTION RIWAYAT KUNJUNGAN & OBAT (BONUS FITUR CAPSTONE) -->
        <div class="card-white" style="margin-top: 4px;">
            <div class="card-header-row" style="margin-bottom: 10px;">
                <div class="card-header-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Riwayat Kunjungan & Catatan Medis Sebelumnya
                </div>
                <span style="font-size: 12px; color: #64748b;">Total Kunjungan: <?php echo mysqli_num_rows($resRiwayat); ?> kali</span>
            </div>

            <?php if (mysqli_num_rows($resRiwayat) == 0): ?>
                <p style="color: #94a3b8; font-size: 13px; font-style: italic;">Belum ada riwayat kunjungan sebelumnya.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 5px;">
                        <thead>
                            <tr style="background: #f8fafc; color: #475569; text-align: left; border-bottom: 2px solid #eef2f6;">
                                <th style="padding: 10px;">Tanggal</th>
                                <th style="padding: 10px;">Keluhan</th>
                                <th style="padding: 10px;">Diagnosa Dokter</th>
                                <th style="padding: 10px;">Terapi / Resep Obat</th>
                                <th style="padding: 10px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($rw = mysqli_fetch_assoc($resRiwayat)): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 10px; color: #64748b;"><?php echo date('d M Y', strtotime($rw['tanggal_kunjungan'])); ?></td>
                                    <td style="padding: 10px; font-weight: 600;"><?php echo htmlspecialchars($rw['keluhan']); ?></td>
                                    <td style="padding: 10px; color: #0284c7; font-weight: 600;"><?php echo (!empty($rw['diagnosa']) && $rw['diagnosa'] != '-') ? htmlspecialchars($rw['diagnosa']) : '<span style="color:#94a3b8;">Dalam proses</span>'; ?></td>
                                    <td style="padding: 10px;"><?php echo (!empty($rw['terapi_penanganan']) && $rw['terapi_penanganan'] != '-') ? htmlspecialchars($rw['terapi_penanganan']) : '<span style="color:#94a3b8;">-</span>'; ?></td>
                                    <td style="padding: 10px;">
                                        <?php if ($rw['status_antrean'] == 'Selesai'): ?>
                                            <span style="background: #dcfce7; color: #166534; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 12px;">Selesai</span>
                                        <?php elseif ($rw['status_antrean'] == 'Diperiksa'): ?>
                                            <span style="background: #e0f2fe; color: #0284c7; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 12px;">Sedang Diperiksa</span>
                                        <?php else: ?>
                                            <span style="background: #fef3c7; color: #b45309; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 12px;">Menunggu Antrean</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- MODAL 1: KARTU PASIEN DIGITAL -->
    <div class="modal-overlay" id="modalKartu">
        <div class="modal-card">
            <button class="modal-close" onclick="closeModalKartu()">&times;</button>
            <h3 style="color: #0f172a; margin-bottom: 4px;">💳 Kartu Pasien Digital</h3>
            <p style="color: #64748b; font-size: 13px;">Tunjukkan kartu ini kepada petugas loket saat berkunjung ke klinik.</p>

            <div class="digital-id-card">
                <div class="card-top-row">
                    <div>
                        <div class="card-clinic-title">KLINIK SEHAT PRATAMA</div>
                        <div class="card-clinic-sub">KARTU IDENTITAS PASIEN RESMI</div>
                    </div>
                    <div class="card-chip"></div>
                </div>

                <div class="card-mid-row">
                    <div>
                        <div style="font-size: 10px; opacity: 0.8; text-transform: uppercase;">Nama Pasien</div>
                        <div class="card-patient-name"><?php echo htmlspecialchars($pasien['nama_lengkap']); ?></div>
                        <div style="font-size: 11px; color: #e0f2fe; margin-top: 2px;">
                            NIK: <?php echo !empty($pasien['nik']) ? htmlspecialchars($pasien['nik']) : '3204234567890001'; ?>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 10px; opacity: 0.8; text-transform: uppercase;">No. Rekam Medis</div>
                        <div class="card-rm-code"><?php echo htmlspecialchars($no_rm); ?></div>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.25); padding-top: 12px; font-size: 11px;">
                    <div>Status: <strong><?php echo htmlspecialchars($status_bpjs); ?></strong></div>
                    <div>Gol. Darah: <strong><?php echo htmlspecialchars($golongan_darah); ?></strong></div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 15px;">
                <button type="button" onclick="window.print()" class="btn-primary-action" style="width: 100%; justify-content: center;">
                    🖨️ Cetak / Simpan Kartu PDF
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 2: PERBARUI DATA PASIEN -->
    <div class="modal-overlay" id="modalEdit">
        <div class="modal-card">
            <button class="modal-close" onclick="closeModalEdit()">&times;</button>
            <h3 style="color: #0f172a; margin-bottom: 4px;">✏️ Perbarui Data Kontak & Keluhan</h3>
            <p style="color: #64748b; font-size: 13px; margin-bottom: 16px;">Sesuaikan alamat domisili, nomor telepon, dan catatan keluhan Anda.</p>

            <form action="dashboard_pasien.php" method="POST">
                <div style="margin-bottom: 12px;">
                    <label style="display:block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Nomor Telepon / WhatsApp:</label>
                    <input type="text" name="no_hp" value="<?php echo htmlspecialchars($no_hp); ?>" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px;">
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="display:block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Alamat Domisili:</label>
                    <textarea name="alamat" rows="2" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px;"><?php echo htmlspecialchars($pasien['alamat']); ?></textarea>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="display:block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Keluhan Utama Saat Ini:</label>
                    <input type="text" name="keluhan" value="<?php echo htmlspecialchars($judul_keluhan); ?>" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px;">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display:block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Detail Riwayat / Catatan Gejala Tambahan:</label>
                    <textarea name="detail_keluhan" rows="3" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px;"><?php echo htmlspecialchars($detail_keluhan); ?></textarea>
                </div>

                <button type="submit" name="update_profil" class="btn-primary-action" style="width:100%; justify-content:center;">
                    Simpan Perubahan Data
                </button>
            </form>
        </div>
    </div>

    <!-- MODAL 3: AMBIL ANTREAN / KUNJUNGAN BARU -->
    <div class="modal-overlay" id="modalAntrean">
        <div class="modal-card">
            <button class="modal-close" onclick="closeModalAntrean()">&times;</button>
            <h3 style="color: #0f172a; margin-bottom: 4px;">➕ Daftar Berobat Hari Ini</h3>
            <p style="color: #64748b; font-size: 13px; margin-bottom: 16px;">Ambil tiket antrean klinik secara online.</p>

            <form action="dashboard_pasien.php" method="POST">
                <div style="margin-bottom: 12px;">
                    <label style="display:block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Keluhan Utama:</label>
                    <input type="text" name="keluhan_antre" placeholder="Contoh: Pusing demam sejak semalam" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px;">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display:block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Deskripsi Gejala / Keluhan Tambahan:</label>
                    <textarea name="detail_antre" rows="3" placeholder="Tuliskan jika ada demam, mual, alergi obat, dll" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px;"></textarea>
                </div>

                <button type="submit" name="daftar_antrean_baru" class="btn-primary-action" style="width:100%; justify-content:center;">
                    Kirim Pendaftaran & Ambil Antrean
                </button>
            </form>
        </div>
    </div>

    <script>
        function openModalKartu() {
            document.getElementById('modalKartu').style.display = 'flex';
        }
        function closeModalKartu() {
            document.getElementById('modalKartu').style.display = 'none';
        }

        function openModalEdit() {
            document.getElementById('modalEdit').style.display = 'flex';
        }
        function closeModalEdit() {
            document.getElementById('modalEdit').style.display = 'none';
        }

        function openModalAntrean() {
            document.getElementById('modalAntrean').style.display = 'flex';
        }
        function closeModalAntrean() {
            document.getElementById('modalAntrean').style.display = 'none';
        }
    </script>
</body>
</html>

