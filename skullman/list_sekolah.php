<?php
include('koneksi.php');

session_start();

if (!isset($_SESSION["username"])) {
    header('Location: index.php');
    exit;
}

$sql = "SELECT * FROM sekolah WHERE VOID = 0 ORDER BY IDSEKOLAH DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Data Sekolah</title>
    <link rel="stylesheet" href="w3.css">
    <link rel="icon" type="image/png" href="skullman.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .action-icons {
            display: flex;
            justify-content: space-around;
            margin-top: 10px;
        }

        .bottom-right {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.3);
        }

        .bottom-right i {
            margin: 0;
        }

        .w3-sidebar {
            z-index: 1100;
            position: fixed;
            left: -250px;
            width: 250px;
            height: 100%;
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 0;
            background-color: #f4f4f4;
            border-right: 1px solid #ccc;
        }

        .w3-sidebar.show {
            left: 0;
        }

        .w3-sidebar.hide {
            left: -250px;
        }

        .w3-sidebar a {
            padding: 10px;
            text-decoration: none;
            font-size: 18px;
            color: black;
            display: block;
        }

        .w3-sidebar a:hover {
            background-color: #ddd;
        }

        .w3-sidebar .close-button {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 15px;
            background-color: #f44336;
            color: white;
            font-size: 20px;
            text-align: center;
            border: none;
            cursor: pointer;
        }

        .w3-sidebar-overlay {
            display: none;
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .w3-sidebar-overlay.show {
            display: block;
        }
    </style>
</head>

<body>
    <!-- Sidebar Overlay -->
    <div id="sidebarOverlay" class="w3-sidebar-overlay" onclick="w3_close()"></div>
     <!-- Sidebar -->
     <div class="w3-sidebar w3-bar-block w3-border-right w3-light-grey" id="mySidebar">
    <button onclick="w3_close()" class="w3-bar-item w3-button w3-red w3-center close-button">
        <b>Close</b><i class="fa fa-close" style="font-size:20px"></i>
    </button>
    <a href="list_siswa.php" class="w3-bar-item w3-button w3-border">
        <i class="fa fa-user-graduate" style="font-size:20px;"></i> List Siswa
    </a>
    <a href="list_absensi.php" class="w3-bar-item w3-button w3-border">
    <i class="fa fa-clipboard-list" style="font-size:20px;"></i> List Absensi
    </a>
    <a href="list_guru.php" class="w3-bar-item w3-button w3-border">
        <i class="fa fa-users" style="font-size:20px;"></i> List Guru
    </a>
    <a href="list_kelas.php" class="w3-bar-item w3-button w3-border">
        <i class="fa fa-chalkboard-teacher" style="font-size:20px;"></i> List Kelas
    </a>
    <a href="list_kelastahun.php" class="w3-bar-item w3-button w3-border">
        <i class="fas fa-calendar-check" style="font-size:20px;"></i> List Kelas Tahun
    </a>
    <?php if ($_SESSION['username'] == 'admin') { ?>
        <a href="list_sekolah.php" class="w3-bar-item w3-button w3-border">
            <i class="fa fa-school" style="font-size:20px;"></i> List Sekolah
        </a>
    <?php } ?>
    <a href="logout.php" class="w3-bar-item w3-button w3-red w3-center">
        <b>Log Out </b><i class="fas fa-sign-out-alt" style="font-size:20px"></i>
    </a>
</div>

    <!-- Header -->
    <div id="mainContent" style="margin-left: 0; transition: margin-left 0.5s;">
        <div class="w3-red sticky-header" style="display: flex; align-items: center; padding: 10px;">
            <button class="w3-button w3-xlarge" onclick="w3_open()">☰</button>
            <div style="flex-grow: 1; display: flex; flex-direction: column; justify-content: center;">
                <h1
                    style="margin: 0; line-height: 1.5rem; text-align: center; font-size: 30px; margin-top:5px; margin-bottom: 10px;">
                    <b>List Data Sekolah</b>
                </h1>
                <div style="display: flex; font-size: 14px;">
                    <div style="flex: 1; text-align: center;">
                        <span style="font-size: 15px; font-weight: bold;">Nama Sekolah</span> <br>
                    </div>
                    <div style="flex: 1; text-align: center;">
                        <span style="font-size: 15px; font-weight: bold;">Alamat</span>
                    </div>
                    <div style="flex: 1; text-align: center;">
                        <span style="font-size: 16px; font-weight: bold;">Action</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <br>
    <div class="w3-responsive">
        <table class="w3-table-all w3-centered" border="1" style="border-collapse: collapse; width: 100%;">
            <!-- <thead>
                <tr class="w3-light-grey">
                    <th>Nama Sekolah</th>
                    <th>Alamat</th>
                    <th>Aksi</th>
                </tr>
            </thead> -->
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td style="flex: 1; text-align: center; font-size:14px; width:35%">
                            <?php echo htmlspecialchars($row['NAMASEKOLAH']); ?>
                        </td>
                        <td
                            style="flex: 1; text-align: center; font-size:14px; max-width: 300px; word-wrap: break-word; word-break: break-word;">
                            <?php echo htmlspecialchars($row['ALAMAT']); ?>
                        </td>
                        <td class="action-icons" style="font-size: 14px; text-align: center; width:30%;">
                            <!-- Tombol Lihat Lainnya -->
                            <button
                                onclick="document.getElementById('detailModal<?php echo $row['IDSEKOLAH']; ?>').style.display='block'"
                                class="w3-button w3-brown w3-round" style="margin-left: 5px; font-size: 12px;">
                                Lihat Lainnya
                            </button>

                            <!-- Tombol Edit -->
                            <a href="edit_sekolah.php?id=<?php echo urlencode($row['IDSEKOLAH']); ?>"
                                class="material-icons w3-yellow w3-btn w3-button w3-round"
                                style="font-size: 15px;">&#xe22b;</a>

                            <!-- Tombol Delete -->
                            <a href="#" onclick="openModal('<?= $row['IDSEKOLAH']; ?>')"
                                class="fa fa-trash w3-btn w3-button w3-round w3-red" style="font-size: 15px;">
                            </a>

                            <!-- Modal -->
                            <div id="deleteModal" class="w3-modal"
                                style="align-items:center; padding-top: 15%; display: none;">
                                <div class="w3-modal-content w3-animate-top w3-card-4">
                                    <header class="w3-container w3-red">
                                        <span onclick="document.getElementById('deleteModal').style.display='none'"
                                            class="w3-button w3-display-topright">&times;</span>
                                        <h2 style="text-align: left;">Konfirmasi</h2>
                                    </header>
                                    <div class="w3-container">
                                        <p id="modalMessage" style="text-align: left;">Apakah Anda yakin ingin menghapus
                                            data ini?</p>
                                        <div class="w3-right">
                                            <button class="w3-button w3-grey"
                                                onclick="document.getElementById('deleteModal').style.display='none'">Batal</button>
                                            <a id="confirmButton" href="#" class="w3-button w3-red">Hapus</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </td>
                    </tr>

                    <!-- Modal Detail -->
                    <div id="detailModal<?php echo $row['IDSEKOLAH']; ?>" class="w3-modal">
                        <div class="w3-modal-content w3-card-4 w3-animate-top" style="max-width:600px">
                            <header class="w3-container w3-brown w3-center">
                                <span
                                    onclick="document.getElementById('detailModal<?php echo $row['IDSEKOLAH']; ?>').style.display='none'"
                                    class="w3-button w3-display-topright">&times;</span>
                                <h2><b>Detail Data Sekolah</b></h2>
                            </header>
                            <div class="w3-container">
                                <p><strong>ID Sekolah:</strong> <?php echo htmlspecialchars($row['IDSEKOLAH']); ?></p>
                                <p><strong>Nama Sekolah:</strong> <?php echo htmlspecialchars($row['NAMASEKOLAH']); ?></p>
                                <p><strong>Alamat:</strong> <?php echo htmlspecialchars($row['ALAMAT']); ?></p>
                                <p><strong>No. Telp:</strong> <?php echo htmlspecialchars($row['NOTELP']); ?></p>
                                <p><strong>Status:</strong> <?php echo htmlspecialchars($row['STATUS']); ?></p>
                                <p><strong>Expired Date:</strong> <?php echo htmlspecialchars($row['EXPIREDDATE']); ?></p>
                            </div>
                            <footer class="w3-container">
                                <button
                                    onclick="document.getElementById('detailModal<?php echo $row['IDSEKOLAH']; ?>').style.display='none'"
                                    class="w3-button w3-red w3-right">Tutup</button>
                            </footer>
                        </div>
                    </div>
                <?php } ?>
            </tbody>
        </table>
    </div>


    <!-- Add New Customer Button -->
    <a href="tambah_sekolah.php" class="w3-btn w3-round-xlarge w3-brown bottom-right">
        <i class="fa fa-plus" style="font-size:30px"></i>
    </a>

    <script>
        function w3_open() {
            document.getElementById("mySidebar").classList.add('show');
            document.getElementById("sidebarOverlay").classList.add('show');
        }

        function w3_close() {
            document.getElementById("mySidebar").classList.remove('show');
            document.getElementById("sidebarOverlay").classList.remove('show');
        }

        function openModal(IDSEKOLAH) {
            // Tampilkan modal
            document.getElementById('deleteModal').style.display = 'block';

            // Update the delete URL dynamically with the correct ID
            document.getElementById('confirmButton').href = `delete_sekolah.php?id=${encodeURIComponent(IDSEKOLAH)}`;
        }
    </script>
</body>

</html>