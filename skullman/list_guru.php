<?php
include 'koneksi.php';
session_start();

// Pastikan pengguna sudah login
if (!isset($_SESSION["username"])) {
    header("Location: index.php"); // Redirect ke halaman login jika belum login
    exit;
}

// Ambil data dari session
$username = $_SESSION["username"];
$jabatan_login = '';

// Jika bukan admin, ambil jabatan dari session
if ($username !== 'admin') {
    $jabatan_login = isset($_SESSION['jabatan']) ? $_SESSION['jabatan'] : '';
}

$idsekolah = '';

// Jika pengguna bukan admin, ambil idsekolah dari database dan simpan di session
if ($username !== 'admin') {
    // Ambil ID Sekolah berdasarkan username
    $query = "SELECT g.IDSEKOLAH 
              FROM guru g 
              WHERE g.USERNAME = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $idsekolah = $row['IDSEKOLAH'];
        $_SESSION['idsekolah'] = $idsekolah; // Simpan ID Sekolah di session
    } else {
        die("Error: ID Sekolah tidak ditemukan untuk pengguna.");
    }
} else {
    // Jika admin, set session ID Sekolah ke 'all' untuk menunjukkan akses ke semua data
    $_SESSION['idsekolah'] = 'all';
}

// Query untuk mendapatkan data guru
$sql = "
    SELECT 
        guru.NIP,
        guru.NAMA,
        guru.TANGGALLAHIR,
        guru.ALAMAT,
        guru.JENISKELAMIN,
        guru.AGAMA,
        guru.PENDIDIKANTERAKHIR,
        guru.TANGGALMULAIBEKERJA,
        sekolah.NAMASEKOLAH,
        guru.JABATAN,
        guru.STATUS
    FROM guru
    LEFT JOIN sekolah ON guru.IDSEKOLAH = sekolah.IDSEKOLAH
    WHERE guru.VOID = 0 
";

// Jika bukan admin, hanya tampilkan guru yang sesuai dengan ID Sekolah pengguna
if ($username !== 'admin') {
    $sql .= " AND guru.IDSEKOLAH = ? "; // Filter berdasarkan ID Sekolah
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $idsekolah);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    // Jika admin, tampilkan semua guru kecuali admin dan tidak perlu field jabatan
    $sql .= " AND guru.USERNAME != 'admin' ORDER BY guru.NIP DESC";
    $result = mysqli_query($conn, $sql);
}

// Periksa hasil query
if (!$result) {
    die("Error fetching data: " . mysqli_error($conn));
}

// Tutup koneksi
mysqli_close($conn);
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Guru | SKULLMAN</title>

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

        .data-panel {
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

        .guru-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .guru-table th {
            background: #f1f5f9;
            color: #475569;
            padding: 15px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .guru-table td {
            padding: 16px 15px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
            vertical-align: middle;
        }

        .guru-table tr:hover td {
            background: #f8faff;
        }

        .nip-text {
            color: #2563eb;
            font-weight: bold;
        }

        .nama-text {
            color: #1e293b;
            font-weight: 600;
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
            align-items: center;
            gap: 7px;
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
            max-width: 600px;
            width: 100%;
            margin: 35px auto;
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

        .detail-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .detail-item {
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
            min-width: 0;
        }

        .detail-item small {
            display: block;
            color: #64748b;
            font-size: 11px;
            margin-bottom: 6px;
        }

        .detail-item strong {
            color: #1e293b;
            font-size: 13px;
            overflow-wrap: anywhere;
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

            .data-panel {
                padding: 15px;
            }

            .panel-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .detail-list {
                grid-template-columns: 1fr;
            }

            .add-button {
                right: 20px;
                bottom: 20px;
            }
        }
    </style>
</head>

<body>

    <?php
    $isPengelola = ($jabatan_login == 'Kepala Sekolah' || $username == 'admin');
    ?>

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

        <a href="list_absensi.php">
            <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Absensi
        </a>

        <a href="list_guru.php" class="active">
            <i class="fa-solid fa-users"></i> Data Guru
        </a>

        <a href="list_kelas.php">
            <i class="fa-solid fa-chalkboard"></i> Data Kelas
        </a>

        <a href="list_kelastahun.php">
            <i class="fa-solid fa-calendar-days"></i> Kelas Tahun
        </a>

        <?php if ($username == 'admin') { ?>
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
            <h2>Data Guru</h2>

            <div class="profile">
                <strong>
                    <?php echo htmlspecialchars($_SESSION['nama'] ?? $_SESSION['username']); ?>
                </strong>
                <?php echo htmlspecialchars($_SESSION['jabatan'] ?? 'Pengguna'); ?>
            </div>
        </div>

        <section class="page-heading">
            <h1>Data Guru</h1>
            <p>Kelola dan lihat informasi guru yang terdaftar.</p>
        </section>

        <section class="data-panel">

            <div class="panel-header">
                <div>
                    <h3>Daftar Guru</h3>
                    <p>Informasi guru yang terdaftar dalam sistem SKULLMAN.</p>
                </div>

                <?php if ($isPengelola) { ?>
                    <a href="tambah_guru.php" class="btn btn-blue">
                        <i class="fa-solid fa-plus"></i> Tambah Guru
                    </a>
                <?php } ?>
            </div>

            <div class="table-wrapper">
                <table class="guru-table">
                    <thead>
                        <tr>
                            <th>NIP</th>
                            <th>Nama Guru</th>
                            <th>Detail</th>
                            <?php if ($isPengelola) { ?>
                                <th>Aksi</th>
                            <?php } ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($result->num_rows > 0) { ?>
                            <?php while ($row = $result->fetch_assoc()) { ?>
                                <tr>
                                    <td>
                                        <span class="nip-text">
                                            <?php echo htmlspecialchars($row['NIP']); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="nama-text">
                                            <?php echo htmlspecialchars($row['NAMA']); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-blue"
                                            onclick="openDetail(<?php echo json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)">
                                            <i class="fa-solid fa-eye"></i> Lihat Detail
                                        </button>
                                    </td>

                                    <?php if ($isPengelola) { ?>
                                        <td>
                                            <div class="action-group">
                                                <a
                                                    href="edit_guru.php?NIP=<?php echo urlencode($row['NIP']); ?>"
                                                    class="btn btn-edit"
                                                    title="Edit Guru">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>

                                                <button
                                                    type="button"
                                                    class="btn btn-delete"
                                                    onclick="deleteGuru(<?php echo json_encode($row['NIP'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)"
                                                    title="Hapus Guru">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    <?php } ?>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="<?php echo $isPengelola ? '4' : '3'; ?>">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-folder-open"></i>
                                        <h3>Belum Ada Data Guru</h3>
                                        <p>Data guru yang tersedia akan muncul di sini.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

        </section>
    </main>

    <?php if ($isPengelola) { ?>
        <a href="tambah_guru.php" class="add-button" title="Tambah Guru">
            <i class="fa-solid fa-plus"></i>
        </a>
    <?php } ?>

    <!-- Modal Detail Guru -->
    <div id="detailModal" class="custom-modal" onclick="closeDetail()">
        <div class="modal-content" onclick="event.stopPropagation()">

            <div class="modal-header">
                <h3><i class="fa-solid fa-user"></i> Detail Data Guru</h3>
                <button class="modal-close" onclick="closeDetail()">&times;</button>
            </div>

            <div class="modal-body">
                <div class="detail-list" id="detailContent"></div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeDetail()">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="deleteModal" class="custom-modal" onclick="closeDelete()">
        <div class="modal-content" style="max-width: 420px;" onclick="event.stopPropagation()">

            <div class="modal-header" style="background: #dc2626;">
                <h3>Konfirmasi Hapus</h3>
                <button class="modal-close" onclick="closeDelete()">&times;</button>
            </div>

            <div class="modal-body">
                <p id="deleteMessage">
                    Apakah Anda yakin ingin menghapus data guru ini?
                </p>

                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeDelete()">
                        Batal
                    </button>

                    <a id="confirmButton" href="#" class="btn btn-delete" style="margin-left: 8px;">
                        <i class="fa-solid fa-trash"></i> Hapus
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openDetail(guru) {
            const fields = [
                ['NIP', 'NIP'],
                ['Nama', 'NAMA'],
                ['Tanggal Lahir', 'TANGGALLAHIR'],
                ['Alamat', 'ALAMAT'],
                ['Jenis Kelamin', 'JENISKELAMIN'],
                ['Agama', 'AGAMA'],
                ['Pendidikan Terakhir', 'PENDIDIKANTERAKHIR'],
                ['Tanggal Mulai Bekerja', 'TANGGALMULAIBEKERJA'],
                ['Sekolah', 'NAMASEKOLAH'],
                ['Jabatan', 'JABATAN'],
                ['Status', 'STATUS']
            ];

            const container = document.getElementById('detailContent');
            container.innerHTML = '';

            fields.forEach(function(field) {
                const item = document.createElement('div');
                item.className = 'detail-item';

                const label = document.createElement('small');
                label.textContent = field[0];

                const value = document.createElement('strong');
                value.textContent = guru[field[1]] ?? '-';

                item.appendChild(label);
                item.appendChild(value);
                container.appendChild(item);
            });

            document.getElementById('detailModal').style.display = 'block';
        }

        function closeDetail() {
            document.getElementById('detailModal').style.display = 'none';
        }

        function deleteGuru(nip) {
            document.getElementById('deleteMessage').textContent =
                'Apakah Anda yakin ingin menghapus data guru dengan NIP: ' + nip + '?';

            document.getElementById('confirmButton').href =
                'delete_guru.php?NIP=' + encodeURIComponent(nip);

            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDelete() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        window.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeDetail();
                closeDelete();
            }
        });
    </script>

</body>

</html>