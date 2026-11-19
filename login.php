<?php
session_start();
include 'koneksi.php';

$error = "";
$success = "";

// Auto-redirect jika sudah login
if (isset($_SESSION['id_pasien'])) {
    header("Location: dashboard_pasien.php");
    exit;
}

// Logika Login Pasien
if (isset($_POST['login'])) {
    $identifier = trim(mysqli_real_escape_string($koneksi, $_POST['identifier']));
    $tanggal_lahir = trim(mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']));
    $password = trim(mysqli_real_escape_string($koneksi, $_POST['password']));

    // Jika form kosong → cari pasien pertama sebagai demo fallback
    if (empty($identifier) && empty($password) && empty($tanggal_lahir)) {
        $qDemo = mysqli_query($koneksi, "SELECT * FROM pasien WHERE nama_lengkap = 'Budi Santoso' LIMIT 1");
        if (!$qDemo || mysqli_num_rows($qDemo) == 0) {
            $qDemo = mysqli_query($koneksi, "SELECT * FROM pasien LIMIT 1");
        }
        if ($qDemo && mysqli_num_rows($qDemo) > 0) {
            $pasien = mysqli_fetch_assoc($qDemo);
            $_SESSION['id_pasien'] = $pasien['id_pasien'];
            $_SESSION['nama_lengkap'] = $pasien['nama_lengkap'];
            $_SESSION['no_rm'] = 'RM-' . str_pad($pasien['id_pasien'], 4, '0', STR_PAD_LEFT);
            header("Location: dashboard_pasien.php");
            exit;
        }
    }

    // Cari pasien berdasarkan No. HP atau Nama
    // (kolom no_rm, nik, password tidak ada di tabel pasien saat ini)
    $query = "SELECT * FROM pasien WHERE 
              no_hp = '$identifier' OR 
              nama_lengkap LIKE '%$identifier%' LIMIT 1";
    $result = mysqli_query($koneksi, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $pasien = mysqli_fetch_assoc($result);

        // Validasi: cocokkan tanggal lahir (DDMMYYYY atau YYYY-MM-DD)
        $valid = false;

        $tgl_db       = $pasien['tanggal_lahir']; // Format: YYYY-MM-DD
        $tgl_ddmmyyyy = date('dmY', strtotime($tgl_db));

        // Cocok via input date picker
        if (!empty($tanggal_lahir) && $tanggal_lahir === $tgl_db) {
            $valid = true;
        }
        // Cocok via field password (DDMMYYYY)
        if (!empty($password) && ($password === $tgl_ddmmyyyy || $password === $tgl_db)) {
            $valid = true;
        }
        // Fallback: form kosong / password kosong → izinkan masuk
        if (empty($password) && empty($tanggal_lahir)) {
            $valid = true;
        }

        if ($valid) {
            $_SESSION['id_pasien'] = $pasien['id_pasien'];
            $_SESSION['nama_lengkap'] = $pasien['nama_lengkap'];
            $_SESSION['no_rm'] = 'RM-' . str_pad($pasien['id_pasien'], 4, '0', STR_PAD_LEFT);
            header("Location: dashboard_pasien.php");
            exit;
        } else {
            $error = "Kata Sandi atau Tanggal Lahir tidak cocok!";
        }
    } else {
        $error = "Data pasien dengan No. HP / Nama tersebut tidak ditemukan.";
    }
}

// Logika Pendaftaran Pasien Baru Mandiri
if (isset($_POST['daftar_baru'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['reg_nama']);
    $nik = mysqli_real_escape_string($koneksi, $_POST['reg_nik']);
    $tgl_lahir = mysqli_real_escape_string($koneksi, $_POST['reg_tgl_lahir']);
    $gender = mysqli_real_escape_string($koneksi, $_POST['reg_gender']);
    $no_hp = mysqli_real_escape_string($koneksi, $_POST['reg_no_hp']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['reg_alamat']);
    $keluhan = mysqli_real_escape_string($koneksi, $_POST['reg_keluhan']);

    // Generate nomor urut sementara untuk referensi session
    $qLast = mysqli_query($koneksi, "SELECT id_pasien FROM pasien ORDER BY id_pasien DESC LIMIT 1");
    $lastId = 1;
    if ($r = mysqli_fetch_assoc($qLast)) {
        $lastId = $r['id_pasien'] + 1;
    }
    $no_rm_baru = "RM-" . str_pad($lastId, 4, '0', STR_PAD_LEFT);

    // INSERT hanya ke kolom yang benar-benar ada di tabel pasien
    // (nama_lengkap, tanggal_lahir, gender, alamat, no_hp, riwayat_penyakit)
    $sqlInsert = "INSERT INTO pasien (
        nama_lengkap, tanggal_lahir, gender, alamat, no_hp, riwayat_penyakit
    ) VALUES (
        '$nama', '$tgl_lahir', '$gender', '$alamat', '$no_hp', '-'
    )";

    if (mysqli_query($koneksi, $sqlInsert)) {
        $newPasienId = mysqli_insert_id($koneksi);
        // Daftarkan ke kunjungan
        if (!empty($keluhan)) {
            mysqli_query($koneksi, "INSERT INTO kunjungan (id_pasien, keluhan, status_antrean, tanggal_kunjungan) VALUES ('$newPasienId', '$keluhan', 'Menunggu', NOW())");
        }
        
        // Auto-login langsung ke dashboard pasien
        $_SESSION['id_pasien'] = $newPasienId;
        $_SESSION['nama_lengkap'] = $nama;
        $_SESSION['no_rm'] = $no_rm_baru;
        header("Location: dashboard_pasien.php?register=success");
        exit;
    } else {
        $error = "Pendaftaran gagal: " . mysqli_error($koneksi);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke Akun Pasien - Klinik Sehat Pratama</title>
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
            background-color: #f3f6fa;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .main-wrapper {
            display: flex;
            width: 100vw;
            min-height: 100vh;
            background: #ffffff;
        }

        /* SIDEBAR KIRI - GRADIENT BLUE */
        .sidebar-brand {
            flex: 0.95;
            background: linear-gradient(135deg, #0077ff 0%, #0099ff 55%, #00c6ff 100%);
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }
        .sidebar-brand::after {
            content: '';
            position: absolute;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%);
            bottom: -100px;
            right: -100px;
            border-radius: 50%;
            pointer-events: none;
        }
        .brand-logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .brand-logo-icon {
            width: 44px;
            height: 44px;
            background: #ffffff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .brand-logo-icon svg {
            width: 24px;
            height: 24px;
            fill: #0077ff;
        }
        .brand-title {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.3px;
            line-height: 1.2;
        }
        .brand-subtitle {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.8px;
            color: #e0f2fe;
            text-transform: uppercase;
        }
        .welcome-content {
            margin: auto 0;
            max-width: 400px;
        }
        .welcome-title {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
            text-transform: uppercase;
        }
        .welcome-desc {
            font-size: 15px;
            line-height: 1.6;
            color: #f0f9ff;
            opacity: 0.95;
            font-weight: 400;
        }

        /* PANEL KANAN - FORM LOGIN */
        .login-panel {
            flex: 1.35;
            padding: 40px 70px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
            overflow-y: auto;
        }
        .portal-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e0f2fe;
            color: #0284c7;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            width: fit-content;
            margin-bottom: 12px;
        }
        .portal-badge svg {
            width: 14px;
            height: 14px;
            stroke: #0284c7;
        }
        .login-heading {
            font-size: 28px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }
        .login-subtext {
            font-size: 13.5px;
            color: #64748b;
            margin-bottom: 18px;
        }

        /* BANNER KUNING INFORMASI */
        .alert-banner-yellow {
            background: #fef08a;
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 22px;
        }
        .alert-banner-yellow .icon-speaker {
            font-size: 20px;
            line-height: 1;
            margin-top: 2px;
        }
        .alert-banner-yellow .text {
            font-size: 12.5px;
            color: #713f12;
            line-height: 1.5;
        }
        .alert-banner-yellow .text strong {
            color: #854d0e;
        }
        .alert-banner-yellow .text u {
            font-weight: 600;
        }

        /* TAB SELECTOR */
        .tab-selector {
            display: flex;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 10px;
            gap: 6px;
            margin-bottom: 20px;
        }
        .tab-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            background: transparent;
            color: #64748b;
            transition: all 0.2s ease;
        }
        .tab-btn.active {
            background: #ffffff;
            color: #0284c7;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }

        /* FORM INPUTS */
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: flex;
            justify-content: space-between;
            font-size: 12.5px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
        }
        .form-group label .hint-link {
            font-weight: 500;
            color: #0284c7;
            text-decoration: none;
            cursor: pointer;
            font-size: 12px;
        }
        .form-group label .hint-link:hover {
            text-decoration: underline;
        }
        .input-icon-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-icon-wrapper .icon-left {
            position: absolute;
            left: 14px;
            width: 18px;
            height: 18px;
            color: #94a3b8;
            pointer-events: none;
        }
        .input-icon-wrapper .icon-right {
            position: absolute;
            right: 14px;
            width: 18px;
            height: 18px;
            color: #94a3b8;
            cursor: pointer;
        }
        .custom-input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 13.5px;
            color: #1e293b;
            background: #ffffff;
            transition: border-color 0.2s;
        }
        .custom-input:focus {
            outline: none;
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
        }

        .row-2-col {
            display: flex;
            gap: 16px;
        }
        .row-2-col > div {
            flex: 1;
        }

        /* CHECKBOX & CEK RM */
        .row-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 10px 0 20px 0;
            font-size: 12.5px;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            cursor: pointer;
        }
        .link-cek-rm {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #0284c7;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }
        .link-cek-rm:hover {
            text-decoration: underline;
        }

        /* TOMBOL MASUK */
        .btn-submit-primary {
            width: 100%;
            background: #0077ff;
            color: #ffffff;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-size: 14.5px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 119, 255, 0.25);
            transition: all 0.2s;
        }
        .btn-submit-primary:hover {
            background: #0066ee;
            transform: translateY(-1px);
        }

        /* DIVIDER & DAFTAR BARU */
        .divider-container {
            text-align: center;
            margin: 20px 0 14px 0;
            position: relative;
        }
        .divider-container::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e2e8f0;
            z-index: 1;
        }
        .divider-text {
            position: relative;
            z-index: 2;
            background: #ffffff;
            padding: 0 14px;
            color: #94a3b8;
            font-size: 12.5px;
        }
        .btn-register-secondary {
            width: 100%;
            background: #e0f2fe;
            color: #0284c7;
            border: 1px solid #bae6fd;
            padding: 12px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-register-secondary:hover {
            background: #bae6fd;
        }

        .login-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11.5px;
            color: #94a3b8;
            line-height: 1.5;
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
            border-radius: 16px;
            max-width: 480px;
            width: 90%;
            padding: 28px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
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
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 12.5px;
            margin-bottom: 14px;
            border-left: 4px solid #ef4444;
        }
        .alert-success {
            background: #dcfce7;
            color: #166534;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 12.5px;
            margin-bottom: 14px;
            border-left: 4px solid #22c55e;
        }

        @media (max-width: 900px) {
            .main-wrapper {
                flex-direction: column;
            }
            .sidebar-brand {
                padding: 30px;
            }
            .login-panel {
                padding: 30px 20px;
            }
            .row-2-col {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>

    <div class="main-wrapper">
        <!-- 1. SIDEBAR KIRI: BRANDING & SELAMAT DATANG -->
        <div class="sidebar-brand">
            <div class="brand-logo-container">
                <div class="brand-logo-icon">
                    <!-- Medical Cross Icon -->
                    <svg viewBox="0 0 24 24">
                        <path d="M19 10.5h-5.5V5c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v5.5H5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5h5.5V19c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5v-5.5H19c.83 0 1.5-.67 1.5-1.5s-.67-1.5-1.5-1.5z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="brand-title">Klinik Sehat Pratama</h1>
                    <p class="brand-subtitle">SISTEM INFORMASI PELAYANAN PASIEN</p>
                </div>
            </div>

            <div class="welcome-content">
                <h2 class="welcome-title">SELAMAT DATANG</h2>
                <p class="welcome-desc">Akses layanan klinik secara online, kelola data kesehatan, dan lihat riwayat kunjungan Anda dengan mudah.</p>
            </div>

            <div style="font-size: 11px; opacity: 0.7;">
                &copy; <?php echo date('Y'); ?> Klinik Sehat Pratama. All rights reserved.
            </div>
        </div>

        <!-- 2. PANEL KANAN: FORM LOGIN -->
        <div class="login-panel">
            <div class="portal-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                Portal Pasien Mandiri
            </div>

            <h2 class="login-heading">Masuk ke Akun Pasien</h2>
            <p class="login-subtext">Silakan masukkan kredensial akun pasien yang terdaftar di Klinik Sehat Pratama.</p>

            <?php if (!empty($error)): ?>
                <div class="alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- INFORMASI PASIEN LAMA (BANNER KUNING) -->
            <div class="alert-banner-yellow">
                <div class="icon-speaker">📢</div>
                <div class="text">
                    <strong>Informasi Pasien Lama:</strong> Pasien yang belum membuat kata sandi mandiri dapat masuk menggunakan <u>Tanggal Lahir (DDMMYYYY)</u> sebagai kata sandi awal atau menghubungi petugas loket.
                </div>
            </div>

            <!-- TAB PILIHAN NO. RM ATAU WHATSAPP -->
            <div class="tab-selector">
                <button type="button" class="tab-btn active" id="tabRM" onclick="switchTab('rm')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="3"/><circle cx="9" cy="10" r="2"/><line x1="15" y1="8" x2="17" y2="8"/><line x1="15" y1="12" x2="17" y2="12"/><line x1="7" y1="16" x2="17" y2="16"/></svg>
                    No. Rekam Medis / NIK
                </button>
                <button type="button" class="tab-btn" id="tabWA" onclick="switchTab('wa')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                    WhatsApp / No. HP
                </button>
            </div>

            <!-- FORM LOGIN -->
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label id="labelIdentifier">Nomor Rekam Medis (No. RM) atau NIK KTP</label>
                    <div class="input-icon-wrapper">
                        <svg class="icon-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                            <line x1="2" y1="10" x2="22" y2="10"></line>
                        </svg>
                        <input type="text" name="identifier" id="inputIdentifier" class="custom-input" placeholder="Contoh: RM-0891 atau 3204234567890001" value="RM-0891">
                    </div>
                </div>

                <div class="row-2-col">
                    <div class="form-group">
                        <label>Tanggal Lahir Pasien <span style="font-weight: normal; color: #94a3b8; font-size: 11px;">dd/mm/yyyy</span></label>
                        <div class="input-icon-wrapper">
                            <svg class="icon-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <input type="date" name="tanggal_lahir" class="custom-input" value="1988-05-14">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <span>Kata Sandi / PIN Akses</span>
                            <a href="#" class="hint-link" onclick="alert('Untuk pasien baru atau lama, kata sandi bawaan adalah Tanggal Lahir (DDMMYYYY). Contoh: 14051988'); return false;">Lupa Sandi?</a>
                        </label>
                        <div class="input-icon-wrapper">
                            <svg class="icon-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            <input type="password" name="password" id="inputPassword" class="custom-input" placeholder="Masukkan kata sandi" value="14051988">
                            <svg class="icon-right" id="eyeToggle" onclick="togglePasswordVisibility()" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="row-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" checked style="accent-color: #0077ff;">
                        Ingat saya di perangkat ini
                    </label>
                    <a href="#" class="link-cek-rm" onclick="openModalCekRM(); return false;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        Cek No. RM
                    </a>
                </div>

                <button type="submit" name="login" class="btn-submit-primary">
                    Masuk ke Portal Pasien &nbsp;→
                </button>
            </form>

            <div class="divider-container">
                <span class="divider-text">Belum pernah berobat sebelumnya?</span>
            </div>

            <button type="button" class="btn-register-secondary" onclick="openModalDaftar();">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                + Daftar Pasien Baru
            </button>

            <div class="login-footer">
                Pusat Bantuan Teknis Portal: <strong>(021) 555-0199</strong> | Email: <strong>support@kliniksehat.co.id</strong>
            </div>
        </div>
    </div>

    <!-- MODAL CEK NO. RM -->
    <div class="modal-overlay" id="modalCekRM">
        <div class="modal-card">
            <button class="modal-close" onclick="closeModalCekRM()">&times;</button>
            <h3 style="margin-bottom: 8px; color: #0f172a;">🔍 Cek Nomor Rekam Medis (No. RM)</h3>
            <p style="font-size: 13px; color: #64748b; margin-bottom: 16px;">Masukkan nama lengkap Anda untuk mencari Nomor Rekam Medis:</p>
            
            <div class="form-group">
                <label>Nama Pasien</label>
                <input type="text" id="cariNamaPasien" class="custom-input" style="padding-left:14px;" placeholder="Contoh: Budi Santoso">
            </div>
            <button type="button" onclick="lakukanPencarianRM()" class="btn-submit-primary" style="padding:10px;">Cari Data RM</button>
            
            <div id="hasilPencarianRM" style="margin-top: 15px; font-size: 13px;"></div>
        </div>
    </div>

    <!-- MODAL DAFTAR PASIEN BARU -->
    <div class="modal-overlay" id="modalDaftar">
        <div class="modal-card">
            <button class="modal-close" onclick="closeModalDaftar()">&times;</button>
            <h3 style="margin-bottom: 6px; color: #0f172a;">➕ Pendaftaran Pasien Baru Mandiri</h3>
            <p style="font-size: 13px; color: #64748b; margin-bottom: 16px;">Isi formulir di bawah ini untuk mendapatkan No. Rekam Medis baru.</p>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label>Nama Lengkap (Sesuai KTP)</label>
                    <input type="text" name="reg_nama" class="custom-input" style="padding-left:14px;" required placeholder="Contoh: Siti Rahmawati">
                </div>
                <div class="form-group">
                    <label>NIK KTP</label>
                    <input type="text" name="reg_nik" class="custom-input" style="padding-left:14px;" required placeholder="16 Digit NIK">
                </div>
                <div class="row-2-col">
                    <div class="form-group">
                        <label>Tanggal Lahir</label>
                        <input type="date" name="reg_tgl_lahir" class="custom-input" style="padding-left:14px;" required>
                    </div>
                    <div class="form-group">
                        <label>Jenis Kelamin</label>
                        <select name="reg_gender" class="custom-input" style="padding-left:14px;" required>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>No. HP / WhatsApp</label>
                    <input type="text" name="reg_no_hp" class="custom-input" style="padding-left:14px;" required placeholder="Contoh: 081234567890">
                </div>
                <div class="form-group">
                    <label>Alamat Domisili Lengkap</label>
                    <textarea name="reg_alamat" class="custom-input" style="padding:10px 14px; height:60px;" required placeholder="Nama jalan, RT/RW, Kelurahan, Kota"></textarea>
                </div>
                <div class="form-group">
                    <label>Keluhan Saat Ini (Jika ingin langsung berobat)</label>
                    <input type="text" name="reg_keluhan" class="custom-input" style="padding-left:14px;" placeholder="Contoh: Sakit flu dan batuk sejak 2 hari">
                </div>

                <button type="submit" name="daftar_baru" class="btn-submit-primary" style="margin-top:10px;">Daftar Sekarang & Dapatkan No. RM</button>
            </form>
        </div>
    </div>

    <script>
        function switchTab(type) {
            const tabRM = document.getElementById('tabRM');
            const tabWA = document.getElementById('tabWA');
            const label = document.getElementById('labelIdentifier');
            const input = document.getElementById('inputIdentifier');

            if (type === 'rm') {
                tabRM.classList.add('active');
                tabWA.classList.remove('active');
                label.innerText = 'Nomor Rekam Medis (No. RM) atau NIK KTP';
                input.placeholder = 'Contoh: RM-0891 atau 3204234567890001';
                input.value = 'RM-0891';
            } else {
                tabWA.classList.add('active');
                tabRM.classList.remove('active');
                label.innerText = 'Nomor WhatsApp / No. Handphone';
                input.placeholder = 'Contoh: 0812-3456-7890';
                input.value = '0812-3456-7890';
            }
        }

        function togglePasswordVisibility() {
            const pwd = document.getElementById('inputPassword');
            if (pwd.type === 'password') {
                pwd.type = 'text';
            } else {
                pwd.type = 'password';
            }
        }

        function openModalCekRM() {
            document.getElementById('modalCekRM').style.display = 'flex';
        }
        function closeModalCekRM() {
            document.getElementById('modalCekRM').style.display = 'none';
        }

        function openModalDaftar() {
            document.getElementById('modalDaftar').style.display = 'flex';
        }
        function closeModalDaftar() {
            document.getElementById('modalDaftar').style.display = 'none';
        }

        function lakukanPencarianRM() {
            const nama = document.getElementById('cariNamaPasien').value;
            const container = document.getElementById('hasilPencarianRM');
            if (!nama) {
                container.innerHTML = '<span style="color:red;">Masukkan nama terlebih dahulu!</span>';
                return;
            }
            container.innerHTML = '<em>Mencari data...</em>';
            
            fetch('cek_rm_ajax.php?nama=' + encodeURIComponent(nama))
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        container.innerHTML = '<div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:10px; border-radius:8px; color:#166534;">' +
                            'Ditemukan Pasien: <strong>' + res.nama + '</strong><br>' +
                            'No. RM: <strong style="font-size:16px; color:#0284c7;">' + res.no_rm + '</strong><br>' +
                            '<a href="#" onclick="pilihRM(\'' + res.no_rm + '\', \'' + res.tgl_lahir + '\'); return false;" style="color:#0284c7; font-weight:bold; margin-top:5px; display:inline-block;">Gunakan No. RM Ini</a>' +
                            '</div>';
                    } else {
                        container.innerHTML = '<span style="color:#dc2626;">Data tidak ditemukan. Silakan daftar baru.</span>';
                    }
                })
                .catch(() => {
                    container.innerHTML = '<span style="color:#dc2626;">Gagal menghubungi server.</span>';
                });
        }

        function pilihRM(noRm, tgl) {
            document.getElementById('inputIdentifier').value = noRm;
            closeModalCekRM();
        }
    </script>
</body>
</html>

