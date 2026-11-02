<?php

include 'koneksi.php';

session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

$username = mysqli_real_escape_string($conn, $_SESSION['username']);

// Proses hapus kelas
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['idkelas'])) {

    $idkelas_to_delete = $_GET['idkelas'];

    mysqli_begin_transaction($conn);

    try {
        $stmt_kelaskelas = mysqli_prepare($conn, "DELETE FROM kelas WHERE IDKELAS = ?");
        mysqli_stmt_bind_param($stmt_kelaskelas, 's', $idkelas_to_delete);

        if (!mysqli_stmt_execute($stmt_kelaskelas)) {
            throw new Exception('Gagal menghapus data kelas.');
        }

        mysqli_stmt_close($stmt_kelaskelas);

        mysqli_commit($conn);

        header('Location: list_kelas.php');
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "Error: " . $e->getMessage();
    }
}

// Query data kelas
$sql = "";

if ($username === 'admin') {

    $sql = "SELECT * FROM kelas";
} else {

    $query_sekolah = "
        SELECT s.IDSEKOLAH
        FROM sekolah s
        JOIN guru g ON s.IDSEKOLAH = g.IDSEKOLAH
        WHERE g.USERNAME = ?
    ";

    $stmt_sekolah = mysqli_prepare($conn, $query_sekolah);
    mysqli_stmt_bind_param($stmt_sekolah, 's', $username);
    mysqli_stmt_execute($stmt_sekolah);

    $result_sekolah = mysqli_stmt_get_result($stmt_sekolah);

    if ($result_sekolah && mysqli_num_rows($result_sekolah) > 0) {

        $row_sekolah = mysqli_fetch_assoc($result_sekolah);
        $idsekolah = $row_sekolah['IDSEKOLAH'];

        $sql = "SELECT * FROM kelas WHERE IDSEKOLAH = ?";
    } else {

        echo "Error: ID sekolah tidak ditemukan untuk pengguna ini.";
        exit;
    }
}

if ($sql) {

    $stmt = mysqli_prepare($conn, $sql);

    if ($username !== 'admin') {
        mysqli_stmt_bind_param($stmt, 's', $idsekolah);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        die("Error fetching data: " . mysqli_error($conn));
    }
} else {

    die("Error: SQL tidak valid.");
}

mysqli_close($conn);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kelas | SKULLMAN</title>

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
            font-family: Arial, sans-serif;
            color: #1e293b;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: -270px;
            width: 260px;
            height: 100%;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            z-index: 1100;
            transition: 0.3s ease;
            overflow-y: auto;
            padding: 20px 14px;
        }

        .sidebar.show {
            left: 0;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px 25px;
            font-size: 21px;
            font-weight: bold;
            color: #1d4ed8;
        }

        .sidebar-brand i {
            font-size: 25px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 15px;
            margin-bottom: 5px;
            border-radius: 9px;
            color: #475569;
            text-decoration: none;
            font-size: 14px;
            transition: 0.2s;
        }

        .sidebar a:hover {
            background: #eff6ff;
            color: #2563eb;
        }

        .sidebar a.active {
            background: #2563eb;
            color: white;
            font-weight: bold;
        }

        .sidebar a i {
            width: 20px;
            text-align: center;
        }

        .sidebar .logout {
            margin-top: 25px;
            color: #dc2626;
        }

        .sidebar .logout:hover {
            background: #fef2f2;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            z-index: 1000;
        }

        .sidebar-overlay.show {
            display: block;
        }

        /* Main Content */
        .main-content {
            min-height: 100vh;
            transition: 0.3s;
        }

        .topbar {
            height: 75px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .menu-button {
            border: none;
            background: transparent;
            color: #334155;
            font-size: 22px;
            cursor: pointer;
            padding: 8px;
        }

        .topbar-title {
            font-size: 18px;
            font-weight: bold;
            color: #172554;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
        }

        .profile-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Page */
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 35px 30px;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h1 {
            font-size: 27px;
            margin: 0 0 8px;
            color: #172554;
            font-weight: 700;
        }

        .page-heading p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        /* Table Card */
        .table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            padding: 24px;
            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.04);
        }

        .table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 22px;
        }

        .table-header h2 {
            font-size: 17px;
            margin: 0;
            color: #1e293b;
        }

        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #2563eb;
            color: white;
            padding: 11px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s;
        }

        .btn-add:hover {
            background: #1d4ed8;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .class-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px;
        }

        .class-table th {
            background: #f8fafc;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .class-table td {
            padding: 17px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
            color: #334155;
            vertical-align: middle;
        }

        .class-table tbody tr:hover {
            background: #f8fafc;
        }

        .class-name {
            font-weight: 600;
            color: #1e293b;
        }

        .class-description {
            color: #64748b;
            max-width: 400px;
            overflow-wrap: anywhere;
        }

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-edit,
        .btn-delete {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 7px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 13px;
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

        .empty-state {
            text-align: center !important;
            padding: 45px 15px !important;
            color: #94a3b8 !important;
        }

        .empty-state i {
            display: block;
            font-size: 35px;
            margin-bottom: 12px;
        }

        /* Delete Modal */
        .custom-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 2000;
            background: rgba(15, 23, 42, 0.5);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .custom-modal.show {
            display: flex;
        }

        .modal-box {
            background: white;
            width: 100%;
            max-width: 420px;
            border-radius: 13px;
            padding: 25px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            animation: modalIn 0.2s ease;
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #fef2f2;
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 17px;
        }

        .modal-box h3 {
            margin: 0 0 10px;
            font-size: 19px;
            color: #1e293b;
        }

        .modal-box p {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-cancel,
        .btn-confirm {
            padding: 10px 17px;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
        }

        .btn-cancel {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-confirm {
            background: #dc2626;
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .topbar {
                padding: 0 16px;
                height: 65px;
            }

            .topbar-title {
                font-size: 15px;
            }

            .profile span {
                display: none;
            }

            .page-container {
                padding: 25px 15px;
            }

            .page-heading h1 {
                font-size: 23px;
            }

            .table-card {
                padding: 16px;
            }

            .table-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .btn-add {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="w3_close()"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="mySidebar">

        <div class="sidebar-brand">
            <i class="fa-solid fa-graduation-cap"></i>
            <span>SKULLMAN</span>
        </div>

        <a href="dashboard.php">
            <i class="fa-solid fa-house"></i>
            Dashboard
        </a>

        <a href="list_siswa.php">
            <i class="fa-solid fa-user-graduate"></i>
            Data Siswa
        </a>

        <a href="absensi.php">
            <i class="fa-solid fa-clipboard-check"></i>
            Absensi
        </a>

        <a href="list_absensi.php">
            <i class="fa-solid fa-clock-rotate-left"></i>
            Riwayat Absensi
        </a>

        <a href="list_guru.php">
            <i class="fa-solid fa-users"></i>
            Data Guru
        </a>

        <a href="list_kelas.php" class="active">
            <i class="fa-solid fa-chalkboard"></i>
            Data Kelas
        </a>

        <a href="list_kelastahun.php">
            <i class="fa-solid fa-calendar-check"></i>
            Kelas Tahun
        </a>

        <?php if ($_SESSION['username'] == 'admin') { ?>
            <a href="list_sekolah.php">
                <i class="fa-solid fa-school"></i>
                Data Sekolah
            </a>
        <?php } ?>

        <a href="logout.php" class="logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            Log Out
        </a>

    </aside>

    <!-- Main Content -->
    <main class="main-content">

        <!-- Topbar -->
        <header class="topbar">

            <div style="display:flex; align-items:center; gap:15px;">
                <button class="menu-button" onclick="w3_open()">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div class="topbar-title">
                    SKULLMAN
                </div>
            </div>

            <div class="profile">
                <div class="profile-icon">
                    <i class="fa-solid fa-user"></i>
                </div>

                <span>
                    <?php echo htmlspecialchars($_SESSION['username']); ?>
                </span>
            </div>

        </header>

        <!-- Page Content -->
        <section class="page-container">

            <div class="page-heading">
                <h1>Data Kelas</h1>
                <p>Kelola informasi kelas yang terdaftar di sekolah.</p>
            </div>

            <div class="table-card">

                <div class="table-header">
                    <h2>
                        <i class="fa-solid fa-chalkboard" style="color:#2563eb; margin-right:8px;"></i>
                        Daftar Kelas
                    </h2>

                    <a href="tambah_kelas.php" class="btn-add">
                        <i class="fa-solid fa-plus"></i>
                        Tambah Kelas
                    </a>
                </div>

                <div class="table-responsive">

                    <table class="class-table">

                        <thead>
                            <tr>
                                <th style="width:30%;">Nama Kelas</th>
                                <th>Keterangan</th>
                                <th style="width:120px; text-align:center;">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if ($result && mysqli_num_rows($result) > 0) { ?>

                                <?php while ($row = mysqli_fetch_assoc($result)) { ?>

                                    <tr>

                                        <td>
                                            <span class="class-name">
                                                <?php echo htmlspecialchars($row['NAMAKELAS']); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="class-description">
                                                <?php echo htmlspecialchars($row['KETERANGAN'] ?? ''); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <div class="action-buttons" style="justify-content:center;">

                                                <a
                                                    href="edit_kelas.php?id=<?php echo urlencode($row['IDKELAS']); ?>"
                                                    class="btn-edit"
                                                    title="Edit Kelas">

                                                    <i class="fa-solid fa-pen"></i>

                                                </a>

                                                <button
                                                    type="button"
                                                    class="btn-delete"
                                                    title="Hapus Kelas"
                                                    onclick='deleteKelas(
                                                        <?php echo json_encode((string)$row['IDKELAS']); ?>,
                                                        <?php echo json_encode((string)$row['NAMAKELAS']); ?>
                                                    )'>

                                                    <i class="fa-solid fa-trash"></i>

                                                </button>

                                            </div>
                                        </td>

                                    </tr>

                                <?php } ?>

                            <?php } else { ?>

                                <tr>
                                    <td colspan="3" class="empty-state">
                                        <i class="fa-solid fa-folder-open"></i>
                                        Belum ada data kelas.
                                    </td>
                                </tr>

                            <?php } ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </section>

    </main>

    <!-- Modal Konfirmasi Hapus -->
    <div class="custom-modal" id="deleteModal">

        <div class="modal-box">

            <div class="modal-icon">
                <i class="fa-solid fa-trash"></i>
            </div>

            <h3>Hapus Data Kelas?</h3>

            <p id="modalMessage">
                Apakah kamu yakin ingin menghapus data kelas ini?
            </p>

            <div class="modal-actions">

                <button class="btn-cancel" onclick="closeModal()">
                    Batal
                </button>

                <button class="btn-confirm" id="confirmButton">
                    Ya, Hapus
                </button>

            </div>

        </div>

    </div>

    <script>
        function w3_open() {
            document.getElementById("mySidebar").classList.add("show");
            document.getElementById("sidebarOverlay").classList.add("show");
        }

        function w3_close() {
            document.getElementById("mySidebar").classList.remove("show");
            document.getElementById("sidebarOverlay").classList.remove("show");
        }

        function deleteKelas(idkelas, namakelas) {

            const modal = document.getElementById("deleteModal");
            const modalMessage = document.getElementById("modalMessage");
            const confirmButton = document.getElementById("confirmButton");

            modalMessage.textContent =
                "Apakah kamu yakin ingin menghapus kelas '" + namakelas + "'?";

            modal.classList.add("show");

            confirmButton.onclick = function() {
                window.location.href =
                    "list_kelas.php?action=delete&idkelas=" +
                    encodeURIComponent(idkelas);
            };
        }

        function closeModal() {
            document.getElementById("deleteModal").classList.remove("show");
        }

        document.getElementById("deleteModal").addEventListener("click", function(event) {
            if (event.target === this) {
                closeModal();
            }
        });
    </script>

</body>

</html>