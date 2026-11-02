<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');
session_start();

if (!isset($_SESSION["username"])) {
    header('Location: index.php');
    exit;
}

$username = $_SESSION['username'];

// Check if the logged-in user is 'admin'
$is_admin = ($username === 'admin');

// Hapus data jika parameter aksi delete diterima
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['idabsensi'])) {
    $idabsensi_to_delete = $_GET['idabsensi'];

    mysqli_begin_transaction($conn);

    try {
        // Hapus data terkait di detilabsensi
        $stmt_detilabsensi = mysqli_prepare($conn, "DELETE FROM detilabsensi WHERE IDABSENSI = ?");
        mysqli_stmt_bind_param($stmt_detilabsensi, 's', $idabsensi_to_delete);
        if (!mysqli_stmt_execute($stmt_detilabsensi)) {
            throw new Exception('Gagal menghapus data detilabsensi terkait.');
        }
        mysqli_stmt_close($stmt_detilabsensi);

        // Hapus data utama di absensi
        $stmt_absensi = mysqli_prepare($conn, "DELETE FROM absensi WHERE IDABSENSI = ?");
        mysqli_stmt_bind_param($stmt_absensi, 's', $idabsensi_to_delete);
        if (!mysqli_stmt_execute($stmt_absensi)) {
            throw new Exception('Gagal menghapus data absensi.');
        }
        mysqli_stmt_close($stmt_absensi);

        // Commit transaksi jika tidak ada error
        mysqli_commit($conn);

        // Redirect ke halaman utama setelah berhasil menghapus
        header('Location: list_absensi.php');
        exit;
    } catch (Exception $e) {
        // Rollback transaksi jika terjadi error
        mysqli_rollback($conn);
        echo "Error: " . $e->getMessage();
    }
}

// Get idsekolah for non-admin users
$idsekolah = null;
if (!$is_admin) {
    $query_sekolah = "
        SELECT s.idsekolah 
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
        $_SESSION['idsekolah'] = $idsekolah;
    } else {
        die("Error: School ID not found for the user.");
    }
}

// Query absensi with idsekolah filter for non-admin users
$query_absensi = "
SELECT 
    a.IDABSENSI,
    a.daterecord, 
    a.TANGGAL, 
    kt.TAHUNAJARAN,
    k.NAMAKELAS,
    s.idsekolah,
    GROUP_CONCAT(DISTINCT ss.NIS ORDER BY ss.NIS SEPARATOR ', ') AS daftar_nis,
    COUNT(DISTINCT ss.NIS) AS total_siswa
FROM 
    absensi a
LEFT JOIN kelastahun kt ON a.IDKELASTAHUN = kt.IDKELASTAHUN
LEFT JOIN kelas k ON kt.IDKELAS = k.IDKELAS
LEFT JOIN sekolah s ON k.idsekolah = s.idsekolah
LEFT JOIN detilabsensi da ON a.IDABSENSI = da.IDABSENSI
LEFT JOIN siswa ss ON da.NIS = ss.NIS
";

// If admin, show all data; otherwise, filter by idsekolah
if ($is_admin) {
    $query_absensi .= " WHERE 1 ";
} else {
    $query_absensi .= " WHERE s.idsekolah = ? ";
}

$query_absensi .= "
GROUP BY 
    a.IDABSENSI
ORDER BY 
    a.daterecord DESC, k.IDKELAS;
";

$stmt_absensi = mysqli_prepare($conn, $query_absensi);

// Bind idsekolah for non-admin users
if (!$is_admin) {
    mysqli_stmt_bind_param($stmt_absensi, 's', $idsekolah);
}

mysqli_stmt_execute($stmt_absensi);
$result_absensi = mysqli_stmt_get_result($stmt_absensi);

// Fetch all absensi data into an array
$absensi_data = [];
while ($row = mysqli_fetch_assoc($result_absensi)) {
    // Ambil daterecord untuk logika pengaturan aksi edit/hapus
    $daterecord = new DateTime($row['daterecord']);
    $now = new DateTime();

    // Hitung selisih waktu dalam jam
    $interval = $now->diff($daterecord);
    $hours_diff = ($interval->days * 24) + $interval->h;

    // Tambahkan flag untuk aksi berdasarkan apakah selisih kurang dari 24 jam
    $row['can_edit_delete'] = ($hours_diff < 24);

    // Menambahkan Tanggal untuk ditampilkan di tabel
    $row['TANGGAL'] = date("Y-m-d", strtotime($row['TANGGAL']));  // Menampilkan tanggal dalam format yang sesuai

    $absensi_data[] = $row;
}

mysqli_stmt_close($stmt_absensi);

// Fetch siswa data per IDABSENSI
$siswa_data = [];
$query_siswa = "
SELECT da.IDABSENSI, da.NIS, ss.NAMA, da.STATUS
FROM detilabsensi da
LEFT JOIN siswa ss ON da.NIS = ss.NIS
WHERE da.IDABSENSI = ?
";

$stmt_siswa = mysqli_prepare($conn, $query_siswa);

foreach ($absensi_data as $absensi) {
    mysqli_stmt_bind_param($stmt_siswa, 's', $absensi['IDABSENSI']);
    mysqli_stmt_execute($stmt_siswa);
    $result_siswa = mysqli_stmt_get_result($stmt_siswa);

    $siswa_data[$absensi['IDABSENSI']] = [];
    while ($row_siswa = mysqli_fetch_assoc($result_siswa)) {
        $siswa_data[$absensi['IDABSENSI']][] = [
            'nis' => $row_siswa['NIS'],
            'nama' => $row_siswa['NAMA'],
            'status' => $row_siswa['STATUS'],
        ];
    }
}
mysqli_stmt_close($stmt_siswa);
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Absensi | SKULLMAN</title>
    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="skullman.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f5f8ff;
            color: #1e293b;
            font-family: Arial, sans-serif;
        }

        .app-layout {
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: #172554;
            color: white;
            position: fixed;
            inset: 0 auto 0 0;
            padding: 25px 15px;
            z-index: 1000;
            overflow-y: auto;
        }

        .brand {
            font-size: 23px;
            font-weight: bold;
            padding: 5px 12px 30px;
            letter-spacing: 1px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 13px;
            color: #cbd5e1;
            text-decoration: none;
            padding: 13px 15px;
            margin-bottom: 7px;
            border-radius: 9px;
            font-size: 14px;
            transition: .2s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #2563eb;
            color: white;
        }

        .sidebar a i {
            width: 20px;
            text-align: center;
        }

        .sidebar .logout {
            margin-top: 25px;
            background: rgba(239, 68, 68, .15);
            color: #fecaca;
        }

        .main-content {
            margin-left: 250px;
            padding: 25px 35px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 17px 22px;
            border-radius: 12px;
            margin-bottom: 28px;
            box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        }

        .topbar h2 {
            margin: 0;
            font-size: 20px;
        }

        .profile {
            text-align: right;
            font-size: 13px;
            color: #64748b;
        }

        .profile strong {
            display: block;
            color: #1e293b;
            font-size: 14px;
            margin-bottom: 3px;
        }

        .page-heading {
            margin-bottom: 24px;
        }

        .page-heading h1 {
            margin: 0 0 8px;
            font-size: 27px;
            color: #172554;
        }

        .page-heading p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .history-panel {
            background: white;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 3px 15px rgba(15, 23, 42, .04);
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .panel-header h3 {
            margin: 0;
            color: #172554;
            font-size: 18px;
        }

        .panel-header p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .history-table th {
            background: #f1f5f9;
            color: #475569;
            padding: 15px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .history-table td {
            padding: 16px 15px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
            vertical-align: middle;
        }

        .history-table tr:hover td {
            background: #f8faff;
        }

        .attendance-id {
            color: #2563eb;
            font-weight: bold;
        }

        .class-name {
            color: #334155;
            font-weight: 600;
        }

        .date-text {
            color: #64748b;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            border: none;
            border-radius: 7px;
            padding: 9px 12px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-blue {
            background: #2563eb;
            color: white;
        }

        .btn-blue:hover {
            background: #1d4ed8;
        }

        .btn-edit {
            background: #eff6ff;
            color: #2563eb;
        }

        .btn-edit:hover {
            background: #dbeafe;
        }

        .btn-delete {
            background: #fef2f2;
            color: #dc2626;
        }

        .btn-delete:hover {
            background: #fee2e2;
        }

        .action-group {
            display: flex;
            gap: 7px;
            align-items: center;
        }

        .empty-state {
            text-align: center;
            padding: 45px 15px;
            color: #64748b;
        }

        .empty-state i {
            font-size: 38px;
            color: #94a3b8;
            margin-bottom: 15px;
        }

        .add-button {
            position: fixed;
            right: 30px;
            bottom: 30px;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: #2563eb;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            text-decoration: none;
            font-size: 21px;
            box-shadow: 0 5px 15px rgba(37, 99, 235, .3);
            z-index: 500;
        }

        .add-button:hover {
            background: #1d4ed8;
        }

        .custom-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 2000;
            background: rgba(15, 23, 42, .55);
            padding: 25px 15px;
            overflow-y: auto;
        }

        .modal-content {
            background: white;
            max-width: 650px;
            width: 100%;
            margin: 40px auto;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0, 0, 0, .15);
        }

        .modal-header {
            background: #172554;
            color: white;
            padding: 18px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
        }

        .modal-close {
            border: none;
            background: transparent;
            color: white;
            font-size: 25px;
            cursor: pointer;
        }

        .modal-body {
            padding: 22px;
        }

        .student-table {
            width: 100%;
            border-collapse: collapse;
        }

        .student-table th,
        .student-table td {
            padding: 11px 8px;
            border-bottom: 1px solid #e2e8f0;
            text-align: center;
            font-size: 13px;
        }

        .student-table th {
            background: #f1f5f9;
            color: #475569;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 20px;
        }

        .summary-item {
            background: #f8fafc;
            padding: 13px 8px;
            border-radius: 9px;
            text-align: center;
        }

        .summary-item span {
            display: block;
            color: #64748b;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .summary-item strong {
            font-size: 20px;
            color: #172554;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #334155;
        }

        @media (max-width: 850px) {
            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
            }

            .main-content {
                margin-left: 0;
                padding: 20px 15px;
            }

            .brand {
                padding-bottom: 15px;
            }
        }

        @media (max-width: 550px) {
            .topbar {
                padding: 14px;
            }

            .topbar h2 {
                font-size: 17px;
            }

            .profile {
                font-size: 11px;
            }

            .page-heading h1 {
                font-size: 23px;
            }

            .history-panel {
                padding: 15px;
            }

            .panel-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .add-button {
                right: 20px;
                bottom: 20px;
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

            <a href="dashboard.php">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>

            <a href="list_siswa.php">
                <i class="fa-solid fa-user-graduate"></i> Data Siswa
            </a>

            <a href="absensi.php">
                <i class="fa-solid fa-clipboard-check"></i> Absensi
            </a>

            <a href="list_absensi.php" class="active">
                <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Absensi
            </a>

            <a href="list_guru.php">
                <i class="fa-solid fa-users"></i> Data Guru
            </a>

            <a href="list_kelas.php">
                <i class="fa-solid fa-chalkboard"></i> Data Kelas
            </a>

            <a href="list_kelastahun.php">
                <i class="fa-solid fa-calendar-days"></i> Kelas Tahun
            </a>

            <?php if ($_SESSION['username'] == 'admin') { ?>
                <a href="list_sekolah.php">
                    <i class="fa-solid fa-school"></i> Data Sekolah
                </a>
            <?php } ?>

            <a href="logout.php" class="logout">
                <i class="fa-solid fa-right-from-bracket"></i> Log Out
            </a>
        </aside>

        <main class="main-content">

            <div class="topbar">
                <h2>Riwayat Absensi</h2>

                <div class="profile">
                    <strong>
                        <?php echo htmlspecialchars($_SESSION['nama'] ?? $_SESSION['username']); ?>
                    </strong>
                    <?php echo htmlspecialchars($_SESSION['jabatan'] ?? 'Pengguna'); ?>
                </div>
            </div>

            <section class="page-heading">
                <h1>Riwayat Absensi</h1>
                <p>Lihat dan kelola catatan kehadiran siswa.</p>
            </section>

            <section class="history-panel">

                <div class="panel-header">
                    <div>
                        <h3>Daftar Riwayat</h3>
                        <p>Berikut adalah data absensi yang telah tersimpan.</p>
                    </div>

                    <a href="absensi.php" class="btn btn-blue">
                        <i class="fa-solid fa-plus"></i> Tambah Absensi
                    </a>
                </div>

                <div class="table-wrapper">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>ID Absensi</th>
                                <th>Kelas</th>
                                <th>Tanggal</th>
                                <th>Data Siswa</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (!empty($absensi_data)) { ?>
                                <?php foreach ($absensi_data as $row) { ?>
                                    <tr>
                                        <td>
                                            <span class="attendance-id">
                                                <?php echo htmlspecialchars($row['IDABSENSI']); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="class-name">
                                                <?php echo htmlspecialchars($row['NAMAKELAS']); ?>
                                            </span>
                                            <br>
                                            <small class="date-text">
                                                <?php echo htmlspecialchars($row['TAHUNAJARAN']); ?>
                                            </small>
                                        </td>

                                        <td>
                                            <span class="date-text">
                                                <i class="fa-regular fa-calendar"></i>
                                                <?php echo htmlspecialchars($row['TANGGAL']); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <button
                                                type="button"
                                                class="btn btn-blue"
                                                onclick='openModal(
                                                <?php echo json_encode(
                                                    $siswa_data[$row['IDABSENSI']] ?? [],
                                                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
                                                ); ?>
                                            )'>
                                                <i class="fa-solid fa-eye"></i> Lihat Siswa
                                            </button>
                                        </td>

                                        <td>
                                            <div class="action-group">
                                                <?php if ($row['can_edit_delete']) { ?>

                                                    <a
                                                        href="edit_absensi.php?idabsensi=<?php echo urlencode($row['IDABSENSI']); ?>"
                                                        class="btn btn-edit"
                                                        title="Edit Absensi">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>

                                                    <button
                                                        type="button"
                                                        class="btn btn-delete"
                                                        onclick='deleteAbsensi(<?php echo json_encode($row['IDABSENSI'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)'
                                                        title="Hapus Absensi">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>

                                                <?php } else { ?>
                                                    <span class="date-text">-</span>
                                                <?php } ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <i class="fa-solid fa-folder-open"></i>
                                            <h3>Belum Ada Riwayat Absensi</h3>
                                            <p>Data absensi yang tersimpan akan muncul di sini.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

            </section>
        </main>
    </div>

    <a href="absensi.php" class="add-button" title="Tambah Absensi">
        <i class="fa-solid fa-plus"></i>
    </a>

    <!-- Modal Data Siswa -->
    <div id="accessModal" class="custom-modal" onclick="closeModal1()">
        <div class="modal-content" onclick="event.stopPropagation()">

            <div class="modal-header">
                <h3><i class="fa-solid fa-users"></i> Data Siswa</h3>
                <button class="modal-close" onclick="closeModal1()">&times;</button>
            </div>

            <div class="modal-body">
                <div class="table-wrapper">
                    <table class="student-table">
                        <thead>
                            <tr>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody id="accessDataList"></tbody>
                    </table>
                </div>

                <div class="summary-grid">
                    <div class="summary-item">
                        <span>Hadir</span>
                        <strong id="totalHadir">0</strong>
                    </div>

                    <div class="summary-item">
                        <span>Tidak Hadir</span>
                        <strong id="totalTidakHadir">0</strong>
                    </div>

                    <div class="summary-item">
                        <span>Sakit</span>
                        <strong id="totalSakit">0</strong>
                    </div>

                    <div class="summary-item">
                        <span>Izin</span>
                        <strong id="totalIzin">0</strong>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal1()">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="deleteModal" class="custom-modal" onclick="closeDeleteModal()">
        <div class="modal-content" style="max-width: 420px;" onclick="event.stopPropagation()">

            <div class="modal-header" style="background: #dc2626;">
                <h3>Konfirmasi Hapus</h3>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>

            <div class="modal-body">
                <p id="modalMessage">Apakah Anda yakin ingin menghapus data ini?</p>

                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeDeleteModal()">
                        Batal
                    </button>

                    <button id="confirmButton" class="btn btn-delete" style="margin-left: 8px;">
                        <i class="fa-solid fa-trash"></i> Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModal(nisList) {
            const tableBody = document.getElementById('accessDataList');
            tableBody.innerHTML = '';

            let hadir = 0;
            let tidakHadir = 0;
            let sakit = 0;
            let izin = 0;

            if (!Array.isArray(nisList)) {
                nisList = [];
            }

            nisList.forEach(function(student) {
                const row = document.createElement('tr');

                const cellNis = document.createElement('td');
                const cellNama = document.createElement('td');
                const cellStatus = document.createElement('td');

                cellNis.textContent = student.nis ?? '';
                cellNama.textContent = student.nama ?? '';
                cellStatus.textContent = student.status ?? '';

                row.appendChild(cellNis);
                row.appendChild(cellNama);
                row.appendChild(cellStatus);

                tableBody.appendChild(row);

                const status = String(student.status ?? '').toLowerCase().trim();

                if (status === 'hadir') {
                    hadir++;
                } else if (status === 'tidak hadir') {
                    tidakHadir++;
                } else if (status === 'sakit') {
                    sakit++;
                } else if (status === 'izin') {
                    izin++;
                }
            });

            document.getElementById('totalHadir').textContent = hadir;
            document.getElementById('totalTidakHadir').textContent = tidakHadir;
            document.getElementById('totalSakit').textContent = sakit;
            document.getElementById('totalIzin').textContent = izin;

            document.getElementById('accessModal').style.display = 'block';
        }

        function closeModal1() {
            document.getElementById('accessModal').style.display = 'none';
        }

        function deleteAbsensi(idabsensi) {
            const modal = document.getElementById('deleteModal');

            document.getElementById('modalMessage').textContent =
                'Apakah Anda yakin ingin menghapus absensi dengan ID: ' + idabsensi + '?';

            document.getElementById('confirmButton').onclick = function() {
                window.location.href =
                    'list_absensi.php?action=delete&idabsensi=' + encodeURIComponent(idabsensi);
            };

            modal.style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        window.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal1();
                closeDeleteModal();
            }
        });
    </script>

</body>

</html>