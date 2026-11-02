<?php
include 'koneksi.php'; // Koneksi ke database
date_default_timezone_set('Asia/Jakarta');
session_start();

// Periksa apakah pengguna sudah login
if (!isset($_SESSION["username"])) {
    header('Location: index.php');
    exit;
}

$username = mysqli_real_escape_string($conn, $_SESSION['username']); // Get logged-in username

// Check if the logged-in user is 'admin'
if ($username == 'admin') {
    // Admin can access all schools
    $query_sekolah = "SELECT idsekolah FROM sekolah";
    $idsekolah = null; // Admin does not need to filter by school
} else {
    // Retrieve idsekolah based on the username from the guru table
    $query_sekolah = "SELECT s.idsekolah 
                      FROM sekolah s 
                      JOIN guru g ON s.idsekolah = g.idsekolah 
                      WHERE g.username = ?";
    $stmt_sekolah = mysqli_prepare($conn, $query_sekolah);
    mysqli_stmt_bind_param($stmt_sekolah, 's', $username);
    mysqli_stmt_execute($stmt_sekolah);
    $result_sekolah = mysqli_stmt_get_result($stmt_sekolah);

    if ($result_sekolah && mysqli_num_rows($result_sekolah) > 0) {
        $row_sekolah = mysqli_fetch_assoc($result_sekolah);
        $idsekolah = $row_sekolah['idsekolah'];
        $_SESSION['idsekolah'] = $idsekolah; // Store idsekolah in session
    } else {
        die("Error: School ID not found for the user.");
    }
}

// Fungsi untuk menghasilkan IDABSENSI
function generate_id_absensi($conn)
{
    $current_date = date('Ymd'); // Format: YYYYMMDD
    $prefix = "AB";

    $sql = "SELECT IFNULL(MAX(CAST(SUBSTR(idabsensi, 11, 3) AS UNSIGNED)), 0) AS last_number
            FROM absensi
            WHERE SUBSTR(idabsensi, 3, 8) = '$current_date'";

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        die("Query error: " . mysqli_error($conn));
    }

    $row = mysqli_fetch_assoc($result);
    $last_number = $row['last_number'];

    $new_sequence = str_pad($last_number + 1, 3, '0', STR_PAD_LEFT);

    return $prefix . $current_date . $new_sequence;
}

$showModal = false;

// Menyimpan data absensi dan detilabsensi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kehadiran'])) {
    // Cek apakah kelastahun dipilih
    if (isset($_POST['kelastahun']) && !empty($_POST['kelastahun'])) {
        // Ambil nilai ID Kelas Tahun dari form
        $idKelastahun = $_POST['kelastahun'];
        $tanggal = date('Y-m-d'); // Tanggal absensi hari ini
        $userrecord = $_SESSION['username'];
        $usermodified = $_SESSION['username'];
        $daterecord = date('Y-m-d H:i:s');
        $datemodified = date('Y-m-d H:i:s');

        // Generate IDABSENSI
        $idAbsensi = generate_id_absensi($conn);

        // Insert data ke tabel absensi
        $queryAbsensi = "
            INSERT INTO absensi (IDABSENSI, TANGGAL, IDKELASTAHUN, USERRECORD, USERMODIFIED, DATERECORD, DATEMODIFIED)
            VALUES ('$idAbsensi', '$tanggal', '$idKelastahun', '$userrecord', '$usermodified', '$daterecord', '$datemodified')
        ";

        if (!mysqli_query($conn, $queryAbsensi)) {
            die("Error inserting absensi: " . mysqli_error($conn));
        }

        // Insert data ke tabel detilabsensi
        foreach ($_POST['kehadiran'] as $nis => $status) {
            $queryDetilabsensi = "
                INSERT INTO detilabsensi (IDABSENSI, NIS, STATUS, IDIJIN, DATERECORD, DATEMODIFIED, USERRECORD, USERMODIFIED)
                VALUES ('$idAbsensi', '$nis', '$status', 0, '$daterecord', '$datemodified', '$userrecord', '$usermodified')
            ";

            if (!mysqli_query($conn, $queryDetilabsensi)) {
                die("Error inserting detilabsensi: " . mysqli_error($conn));
            }
        }

        $showModal = true;
    } else {
        echo "Kelas tahun belum dipilih!";
    }
}

// Ambil data kelas tahun dari database dengan status = 1
$query_kelastahun = "
    SELECT kt.IDKELASTAHUN, kt.TAHUNAJARAN, k.NAMAKELAS
    FROM kelastahun kt
    JOIN kelas k ON kt.IDKELAS = k.IDKELAS
    WHERE kt.STATUS = 1
";

if ($idsekolah !== null) {
    $query_kelastahun .= " AND k.IDSEKOLAH = '$idsekolah'";
}

$result_kelastahun = mysqli_query($conn, $query_kelastahun);
if (!$result_kelastahun) {
    die("Error fetching class data: " . mysqli_error($conn));
}


// Ambil data siswa berdasarkan kelas tahun yang dipilih
$daftar_siswa = [];
if (isset($_POST['kelastahun']) && $_POST['kelastahun'] != "") {
    $idKelastahun = $_POST['kelastahun'];

    $query_siswa = "
        SELECT s.NIS, s.NAMA
        FROM kelastahunsiswa ks
        JOIN siswa s ON ks.NIS = s.NIS
        JOIN kelastahun kt ON ks.IDKELASTAHUN = kt.IDKELASTAHUN
        JOIN kelas k ON kt.IDKELAS = k.IDKELAS
    ";
    if ($idsekolah !== null) {
        $query_siswa .= " WHERE k.IDSEKOLAH = '$idsekolah' AND ks.IDKELASTAHUN = '$idKelastahun'";
    } else {
        $query_siswa .= " WHERE ks.IDKELASTAHUN = '$idKelastahun'";
    }

    $result_siswa = mysqli_query($conn, $query_siswa);
    if (!$result_siswa) {
        die("Error fetching students: " . mysqli_error($conn));
    }
    $daftar_siswa = mysqli_fetch_all($result_siswa, MYSQLI_ASSOC);
}

$idabsensi_baru = generate_id_absensi($conn);
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi - SKULLMAN</title>

    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            margin: 0;
            background: #f5f8ff;
            font-family: Arial, sans-serif;
            color: #1e293b;
        }

        .app-layout {
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

        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h2 {
            margin: 0 0 8px;
            font-size: 25px;
        }

        .page-heading p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .panel {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 22px;
        }

        .panel-title {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 8px;
        }

        .panel-description {
            color: #64748b;
            font-size: 13px;
            margin: 0 0 22px;
        }

        .class-form {
            display: flex;
            align-items: end;
            gap: 14px;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 9px;
        }

        .form-group select,
        .id-input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            background: white;
            font-size: 14px;
            box-sizing: border-box;
            color: #1e293b;
        }

        .form-group select:focus {
            outline: none;
            border-color: #2563eb;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 9px;
            padding: 12px 22px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 550px;
        }

        .attendance-table th {
            background: #eff6ff;
            color: #1e40af;
            font-size: 13px;
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dbeafe;
        }

        .attendance-table td {
            padding: 14px 15px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }

        .attendance-table tr:hover {
            background: #f8fafc;
        }

        .attendance-table select {
            width: 100%;
            min-width: 130px;
            padding: 9px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            font-size: 13px;
        }

        .attendance-table select:focus {
            outline: none;
            border-color: #2563eb;
        }

        .id-section {
            margin-bottom: 22px;
        }

        .id-section label {
            display: block;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 9px;
        }

        .table-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .student-count {
            background: #eff6ff;
            color: #1d4ed8;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 22px;
        }

        .btn-back,
        .btn-save {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 13px 22px;
            border-radius: 9px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            border: none;
        }

        .btn-back {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-back:hover {
            background: #e2e8f0;
        }

        .btn-save {
            background: #2563eb;
            color: white;
        }

        .btn-save:hover {
            background: #1d4ed8;
        }

        .empty-state {
            background: #eff6ff;
            border: 1px solid #dbeafe;
            color: #1e40af;
            padding: 25px;
            text-align: center;
            border-radius: 12px;
            font-size: 14px;
        }

        .empty-state i {
            display: block;
            font-size: 30px;
            margin-bottom: 12px;
        }

        .success-modal {
            display: block;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .5);
            z-index: 9999;
            padding: 20px;
        }

        .success-box {
            max-width: 400px;
            margin: 15vh auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
        }

        .success-icon {
            width: 65px;
            height: 65px;
            background: #dcfce7;
            color: #16a34a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 18px;
        }

        .success-box h3 {
            margin: 0 0 10px;
        }

        .success-box p {
            color: #64748b;
            font-size: 14px;
        }

        .footer {
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
            padding: 25px;
        }

        @media (max-width: 850px) {
            .app-layout {
                display: block;
            }

            .sidebar {
                width: auto;
            }
        }

        @media (max-width: 600px) {
            .content {
                padding: 17px;
            }

            .topbar {
                padding: 17px;
            }

            .class-form {
                align-items: stretch;
            }

            .class-form button {
                width: 100%;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .btn-back,
            .btn-save {
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>

<body>

    <div class="app-layout">

        <aside class="sidebar">
            <div class="brand">
                <i class="fa-solid fa-graduation-cap"></i> SKULLMAN
            </div>

            <div class="menu-label">MENU UTAMA</div>

            <a href="dashboard.php">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>

            <a href="list_siswa.php">
                <i class="fa-solid fa-users"></i> Data Siswa
            </a>

            <a href="absensi.php" class="active">
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

            <a href="list_kelastahun.php">
                <i class="fa-solid fa-calendar-check"></i> Kelas Tahun
            </a>

            <?php if ($_SESSION['username'] == 'admin') { ?>
                <a href="list_sekolah.php">
                    <i class="fa-solid fa-building"></i> Data Sekolah
                </a>
            <?php } ?>

            <a href="logout.php">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </aside>

        <main class="main-content">

            <header class="topbar">
                <h3>Absensi</h3>

                <div class="profile">
                    <div class="profile-icon">
                        <i class="fa-solid fa-user"></i>
                    </div>

                    <div>
                        <strong><?= htmlspecialchars($_SESSION['nama'] ?? 'Pengguna') ?></strong><br>
                        <small><?= htmlspecialchars($_SESSION['jabatan'] ?? 'Guru') ?></small>
                    </div>
                </div>
            </header>

            <section class="content">

                <div class="page-heading">
                    <h2>Absensi Siswa</h2>
                    <p>Pilih kelas dan catat kehadiran siswa dengan mudah.</p>
                </div>

                <div class="panel">
                    <h3 class="panel-title">Pilih Kelas</h3>
                    <p class="panel-description">
                        Pilih kelas dan tahun ajaran untuk menampilkan daftar siswa.
                    </p>

                    <form method="POST" id="kelasForm" class="class-form">
                        <div class="form-group">
                            <label for="kelastahun">Kelas dan Tahun Ajaran</label>

                            <select name="kelastahun" id="kelastahun" required>
                                <option value="">Pilih kelas terlebih dahulu</option>

                                <?php while ($row = mysqli_fetch_assoc($result_kelastahun)) { ?>
                                    <option value="<?= htmlspecialchars($row['IDKELASTAHUN']) ?>"
                                        <?= (isset($_POST['kelastahun']) && $_POST['kelastahun'] == $row['IDKELASTAHUN']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($row['NAMAKELAS'] . ' - ' . $row['TAHUNAJARAN']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <button type="submit" class="btn-primary">
                            <i class="fa-solid fa-magnifying-glass"></i> Tampilkan Siswa
                        </button>
                    </form>
                </div>

                <?php if (isset($_POST['kelastahun']) && $_POST['kelastahun'] != ""): ?>

                    <div class="panel">

                        <div class="table-heading">
                            <div>
                                <h3 class="panel-title">Daftar Siswa</h3>
                                <p class="panel-description" style="margin:7px 0 0;">
                                    Tentukan status kehadiran setiap siswa.
                                </p>
                            </div>

                            <span class="student-count">
                                <?= count($daftar_siswa) ?> Siswa
                            </span>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="kelastahun"
                                value="<?= htmlspecialchars($_POST['kelastahun']) ?>">

                            <div class="id-section">
                                <label for="idabsensi">ID Absensi</label>
                                <input
                                    class="id-input"
                                    type="text"
                                    id="idabsensi"
                                    value="<?= htmlspecialchars($idabsensi_baru) ?>"
                                    readonly>
                            </div>

                            <?php if (count($daftar_siswa) > 0): ?>

                                <div class="table-wrapper">
                                    <table class="attendance-table">
                                        <thead>
                                            <tr>
                                                <th style="width:20%;">NIS</th>
                                                <th>Nama Siswa</th>
                                                <th style="width:30%;">Status Kehadiran</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php foreach ($daftar_siswa as $siswa): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($siswa['NIS']) ?></td>
                                                    <td><?= htmlspecialchars($siswa['NAMA']) ?></td>
                                                    <td>
                                                        <select name="kehadiran[<?= htmlspecialchars($siswa['NIS']) ?>]">
                                                            <option value="Hadir">Hadir</option>
                                                            <option value="Tidak Hadir">Tidak Hadir</option>
                                                            <option value="Izin">Izin</option>
                                                            <option value="Sakit">Sakit</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="form-actions">
                                    <a href="list_absensi.php" class="btn-back">
                                        <i class="fa-solid fa-arrow-left"></i> Kembali
                                    </a>

                                    <button type="submit" class="btn-save">
                                        <i class="fa-solid fa-floppy-disk"></i> Simpan Absensi
                                    </button>
                                </div>

                            <?php else: ?>

                                <div class="empty-state">
                                    <i class="fa-solid fa-users"></i>
                                    Belum ada siswa yang terdaftar pada kelas ini.
                                </div>

                            <?php endif; ?>

                        </form>
                    </div>

                <?php else: ?>

                    <div class="empty-state">
                        <i class="fa-solid fa-clipboard-list"></i>
                        Silakan pilih kelas terlebih dahulu untuk mulai melakukan absensi.
                    </div>

                <?php endif; ?>

            </section>

            <footer class="footer">
                SKULLMAN &copy; <?= date('Y') ?> — Sistem Absensi Sekolah
            </footer>

        </main>
    </div>

    <?php if ($showModal): ?>
        <div class="success-modal" id="successModal">
            <div class="success-box">
                <div class="success-icon">
                    <i class="fa-solid fa-check"></i>
                </div>

                <h3>Absensi Berhasil!</h3>
                <p>Data kehadiran siswa berhasil disimpan.</p>
                <p style="font-size:12px;">Anda akan diarahkan ke riwayat absensi...</p>
            </div>
        </div>
    <?php endif; ?>

    <script>
        <?php if ($showModal): ?>
            setTimeout(function() {
                window.location.href = 'list_absensi.php';
            }, 1500);
        <?php endif; ?>
    </script>

</body>

</html>