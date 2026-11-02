<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$nama = $_SESSION['nama'] ?? 'Pengguna';
$jabatan = $_SESSION['jabatan'] ?? 'Guru';
$username = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SKULLMAN</title>

    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            margin: 0;
            background: #f5f8ff;
            font-family: Arial, sans-serif;
            color: #1e293b;
        }

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: #172554;
            color: white;
            padding: 25px 18px;
            flex-shrink: 0;
        }

        .brand {
            font-size: 23px;
            font-weight: bold;
            margin-bottom: 35px;
            padding: 0 10px;
        }

        .brand i {
            color: #60a5fa;
            margin-right: 10px;
        }

        .menu-label {
            color: #94a3b8;
            font-size: 11px;
            font-weight: bold;
            margin: 25px 10px 10px;
            letter-spacing: 1px;
        }

        .sidebar a {
            display: block;
            color: #cbd5e1;
            text-decoration: none;
            padding: 13px 14px;
            border-radius: 9px;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .sidebar a i {
            width: 23px;
            margin-right: 8px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #2563eb;
            color: white;
        }

        .main-content {
            flex: 1;
            min-width: 0;
        }

        .topbar {
            background: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
        }

        .topbar h3 {
            margin: 0;
            font-size: 19px;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .profile-icon {
            width: 42px;
            height: 42px;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
        }

        .profile small {
            color: #64748b;
        }

        .content {
            padding: 30px;
        }

        .welcome {
            background: linear-gradient(120deg, #1d4ed8, #3b82f6);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 28px;
        }

        .welcome h2 {
            margin: 0 0 10px;
            font-size: 25px;
        }

        .welcome p {
            margin: 0;
            color: #dbeafe;
            line-height: 1.6;
        }

        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin: 28px 0 17px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .stat-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            padding: 22px;
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 11px;
            background: #dbeafe;
            color: #2563eb;
            font-size: 19px;
        }

        .stat-card h2 {
            margin: 18px 0 5px;
            font-size: 27px;
        }

        .stat-card p {
            margin: 0;
            color: #64748b;
            font-size: 13px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 18px;
            margin-top: 20px;
        }

        .panel {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            padding: 22px;
        }

        .panel h3 {
            font-size: 17px;
            margin: 0 0 20px;
        }

        .attendance-item {
            margin-bottom: 20px;
        }

        .attendance-info {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .progress {
            height: 9px;
            background: #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 20px;
        }

        .shortcut-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .shortcut {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            color: #1e293b;
            text-decoration: none;
            transition: .2s;
        }

        .shortcut:hover {
            border-color: #2563eb;
            background: #eff6ff;
        }

        .shortcut i {
            color: #2563eb;
            font-size: 19px;
        }

        .shortcut span {
            font-size: 13px;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
            padding: 25px;
        }

        @media (max-width: 1000px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 700px) {
            .dashboard-layout {
                display: block;
            }

            .sidebar {
                width: auto;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .content {
                padding: 18px;
            }

            .topbar {
                padding: 17px;
            }
        }

        @media (max-width: 450px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .shortcut-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="dashboard-layout">

        <aside class="sidebar">
            <div class="brand">
                <i class="fa-solid fa-graduation-cap"></i> SKULLMAN
            </div>

            <div class="menu-label">MENU UTAMA</div>

            <a href="dashboard.php" class="active">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>

            <a href="list_siswa.php">
                <i class="fa-solid fa-users"></i> Data Siswa
            </a>

            <a href="absensi.php">
                <i class="fa-solid fa-clipboard-check"></i> Absensi
            </a>

            <a href="list_absensi.php">
                <i class="fa-solid fa-clipboard-list"></i> Riwayat Absensi
            </a>

            <div class="menu-label">MANAJEMEN</div>

            <a href="list_guru.php">
                <i class="fa-solid fa-user-tie"></i> Data Guru
            </a>

            <a href="list_kelas.php">
                <i class="fa-solid fa-school"></i> Data Kelas
            </a>
        </aside>

        <main class="main-content">

            <header class="topbar">
                <h3>Dashboard</h3>

                <div class="profile">
                    <div class="profile-icon">
                        <i class="fa-solid fa-user"></i>
                    </div>

                    <div>
                        <strong><?= htmlspecialchars($nama) ?></strong><br>
                        <small><?= htmlspecialchars($jabatan) ?></small>
                    </div>
                </div>
            </header>

            <section class="content">

                <div class="welcome">
                    <h2>Selamat Datang, <?= htmlspecialchars($nama) ?>!</h2>
                    <p>
                        Selamat datang di SKULLMAN.
                        Kelola data siswa dan kegiatan absensi sekolah
                        dengan lebih mudah melalui satu aplikasi.
                    </p>
                </div>

                <div class="section-title">Ringkasan Aplikasi</div>

                <div class="stats-grid">

                    <div class="stat-card">
                        <div class="stat-top">
                            <div>
                                <p>Total Siswa</p>
                                <h2>--</h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fa-solid fa-users"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-top">
                            <div>
                                <p>Total Guru</p>
                                <h2>--</h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fa-solid fa-user-tie"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-top">
                            <div>
                                <p>Total Kelas</p>
                                <h2>--</h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fa-solid fa-school"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-top">
                            <div>
                                <p>Absensi Hari Ini</p>
                                <h2>--</h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fa-solid fa-calendar-check"></i>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="dashboard-grid">

                    <div class="panel">
                        <h3>Ringkasan Kehadiran</h3>

                        <div class="attendance-item">
                            <div class="attendance-info">
                                <span>Hadir</span>
                                <strong>--</strong>
                            </div>
                            <div class="progress">
                                <div class="progress-fill"
                                    style="width: 75%; background:#2563eb;"></div>
                            </div>
                        </div>

                        <div class="attendance-item">
                            <div class="attendance-info">
                                <span>Izin</span>
                                <strong>--</strong>
                            </div>
                            <div class="progress">
                                <div class="progress-fill"
                                    style="width: 35%; background:#f59e0b;"></div>
                            </div>
                        </div>

                        <div class="attendance-item">
                            <div class="attendance-info">
                                <span>Sakit</span>
                                <strong>--</strong>
                            </div>
                            <div class="progress">
                                <div class="progress-fill"
                                    style="width: 20%; background:#0ea5e9;"></div>
                            </div>
                        </div>

                        <div class="attendance-item">
                            <div class="attendance-info">
                                <span>Alfa</span>
                                <strong>--</strong>
                            </div>
                            <div class="progress">
                                <div class="progress-fill"
                                    style="width: 10%; background:#ef4444;"></div>
                            </div>
                        </div>

                        <small style="color:#94a3b8;">
                            Tampilan sementara. Data akan dihubungkan ke backend.
                        </small>
                    </div>

                    <div class="panel">
                        <h3>Akses Cepat</h3>

                        <div class="shortcut-grid">

                            <a href="list_siswa.php" class="shortcut">
                                <i class="fa-solid fa-users"></i>
                                <span>Data Siswa</span>
                            </a>

                            <a href="absensi.php" class="shortcut">
                                <i class="fa-solid fa-clipboard-check"></i>
                                <span>Absensi</span>
                            </a>

                            <a href="list_absensi.php" class="shortcut">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                <span>Riwayat Absensi</span>
                            </a>

                            <a href="list_guru.php" class="shortcut">
                                <i class="fa-solid fa-user-tie"></i>
                                <span>Data Guru</span>
                            </a>

                            <a href="list_kelas.php" class="shortcut">
                                <i class="fa-solid fa-school"></i>
                                <span>Data Kelas</span>
                            </a>

                        </div>
                    </div>

                </div>

            </section>

            <footer class="footer">
                SKULLMAN &copy; <?= date('Y') ?> — Sistem Absensi Sekolah
            </footer>

        </main>
    </div>

</body>

</html>