<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');
session_start();

if (!isset($_SESSION["username"])) {
    header('Location: index.php');
    exit;
}

$username = mysqli_real_escape_string($conn, $_SESSION['username']);

// Fetch school ID
$query_sekolah = ($username == 'admin') ? 
    "SELECT idsekolah FROM sekolah" : 
    "SELECT s.idsekolah 
     FROM sekolah s 
     JOIN guru g ON s.idsekolah = g.idsekolah 
     WHERE g.username = ?";
     
$stmt_sekolah = mysqli_prepare($conn, $query_sekolah);
if ($username != 'admin') {
    mysqli_stmt_bind_param($stmt_sekolah, 's', $username);
}
mysqli_stmt_execute($stmt_sekolah);
$result_sekolah = mysqli_stmt_get_result($stmt_sekolah);

if ($result_sekolah && mysqli_num_rows($result_sekolah) > 0) {
    $row_sekolah = mysqli_fetch_assoc($result_sekolah);
    $idsekolah = $row_sekolah['idsekolah'];
    $_SESSION['idsekolah'] = $idsekolah;
} else {
    echo "Error: School ID not found for the user.";
    exit;
}

$nis = $_GET['nis'];

// Fetch existing student data
$sql = "SELECT * FROM siswa WHERE NIS = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 's', $nis);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo "Error: Data siswa tidak ditemukan.";
    exit;
}

$errorModal = false;
$errorMessage = '';
$confirmationModal = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis_baru = $_POST['NIS_BARU'] ?? '';
    $nama = $_POST['NAMA'] ?? '';
    $tempatlahir = $_POST['TEMPATLAHIR'] ?? '';
    $tanggallahir = $_POST['TANGGALLAHIR'] ?? '';
    $jeniskelamin = $_POST['JENISKELAMIN'] ?? '';
    $alamat = $_POST['ALAMAT'] ?? '';
    $namaayah = $_POST['NAMAAYAH'] ?? '';
    $namaibu = $_POST['NAMAIBU'] ?? '';
    $kontak1 = $_POST['KONTAK1'] ?? '';
    $kontak2 = $_POST['KONTAK2'] ?? '';
    $email = $_POST['EMAIL'] ?? '';
    $status = $_POST['STATUS'] ?? '';
    $datemodified = date('Y-m-d H:i:s');
    $usermodified = $username;

    // Cek apakah NIS baru sama dengan NIS lama
    if ($nis_baru !== $nis) {
        // Cek apakah NIS baru sudah ada
        $checkNISQuery = "SELECT NIS FROM siswa WHERE NIS = '$nis_baru' AND NIS != '$nis'";
        $result_checkNIS = mysqli_query($conn, $checkNISQuery);

        if ($result_checkNIS && mysqli_num_rows($result_checkNIS) > 0) {
            // Jika NIS sudah ada, tampilkan error modal
            $errorModal = true;
            $errorMessage = "NIS '$nis_baru' sudah ada. Silakan gunakan NIS yang lain.";
        } else {
            // Jika NIS baru belum ada, tampilkan modal konfirmasi
            $confirmationModal = true;
        }
    } else {
        // Jika NIS tidak berubah, langsung lakukan update tanpa konfirmasi
        $sql_update = "UPDATE siswa 
                       SET NAMA = '$nama', TEMPATLAHIR = '$tempatlahir', TANGGALLAHIR = '$tanggallahir', 
                           JENISKELAMIN = '$jeniskelamin', ALAMAT = '$alamat', NAMAAYAH = '$namaayah', 
                           NAMAIBU = '$namaibu', KONTAK1 = '$kontak1', KONTAK2 = '$kontak2', 
                           EMAIL = '$email', STATUS = '$status', DATEMODIFIED = '$datemodified', 
                           USERMODIFIED = '$usermodified' 
                       WHERE NIS = '$nis'";
        if (mysqli_query($conn, $sql_update)) {
            header('Location: list_siswa.php');
            exit;
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}

// Cek apakah konfirmasi "Ya" ditekan
if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    // Lakukan update ketika konfirmasi "Ya" ditekan
    $sql_update = "UPDATE siswa 
                   SET NIS = '$nis_baru', NAMA = '$nama', TEMPATLAHIR = '$tempatlahir', TANGGALLAHIR = '$tanggallahir', 
                       JENISKELAMIN = '$jeniskelamin', ALAMAT = '$alamat', NAMAAYAH = '$namaayah', 
                       NAMAIBU = '$namaibu', KONTAK1 = '$kontak1', KONTAK2 = '$kontak2', 
                       EMAIL = '$email', STATUS = '$status', DATEMODIFIED = '$datemodified', 
                       USERMODIFIED = '$usermodified' 
                   WHERE NIS = '$nis'";
    if (mysqli_query($conn, $sql_update)) {
        header('Location: list_siswa.php');
        exit;
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Siswa</title>
    <link rel="stylesheet" href="w3.css">
    <link rel="icon" type="image/png" href="skullman.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
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
    <div class="w3-red" style="display: flex; align-items: center;">
        <button class="w3-button w3-xlarge" onclick="w3_open()">☰</button>
        <div style="flex-grow: 1; display: flex; justify-content: center;">
            <h3 style="margin: 0; line-height: 3.5rem; margin-bottom:10px;"><b>Edit Data Siswa</b></h3>
        </div>
    </div>

    <form method="POST" class="w3-container w3-card-4 w3-light-grey w3-padding-16 w3-margin">
    <label>NIS</label>
    <input class="w3-input w3-border w3-round" type="text" id="NIS" name="NIS_BARU" 
           value="<?php echo htmlspecialchars($data['NIS'], ENT_QUOTES, 'UTF-8'); ?>" required><br>

    <label>Nama Siswa</label>
    <input class="w3-input w3-border w3-round" type="text" name="NAMA" value="<?php echo htmlspecialchars($data['NAMA'], ENT_QUOTES, 'UTF-8'); ?>" required><br>

    <label>Tempat Lahir</label>
    <input class="w3-input w3-border w3-round" type="text" name="TEMPATLAHIR" value="<?php echo htmlspecialchars($data['TEMPATLAHIR'], ENT_QUOTES, 'UTF-8'); ?>"><br>

    <label>Tanggal Lahir</label>
    <input class="w3-input w3-border w3-round" type="date" name="TANGGALLAHIR" value="<?php echo htmlspecialchars($data['TANGGALLAHIR'], ENT_QUOTES, 'UTF-8'); ?>"><br>

    <label for="JENISKELAMIN">Jenis Kelamin</label>
        <select id="JENISKELAMIN" class="w3-select" name="JENISKELAMIN" required>
            <option value="Laki-laki" <?php echo ($data['JENISKELAMIN'] == "Laki-laki") ? "selected" : ""; ?>>Laki-laki</option>
            <option value="Perempuan" <?php echo ($data['JENISKELAMIN'] == "Perempuan") ? "selected" : ""; ?>>Perempuan</option>
        </select><br><br>

    <label>Alamat</label>
    <textarea class="w3-input w3-border w3-round" name="ALAMAT" rows="4"><?php echo htmlspecialchars($data['ALAMAT'], ENT_QUOTES, 'UTF-8'); ?></textarea><br>

    <label>Nama Ayah</label>
    <input class="w3-input w3-border w3-round" type="text" name="NAMAAYAH" value="<?php echo htmlspecialchars($data['NAMAAYAH'], ENT_QUOTES, 'UTF-8'); ?>"><br>

    <label>Nama Ibu</label>
    <input class="w3-input w3-border w3-round" type="text" name="NAMAIBU" value="<?php echo htmlspecialchars($data['NAMAIBU'], ENT_QUOTES, 'UTF-8'); ?>"><br>

    <label>Kontak 1</label>
    <input class="w3-input w3-border w3-round" type="number" name="KONTAK1" value="<?php echo htmlspecialchars($data['KONTAK1'], ENT_QUOTES, 'UTF-8'); ?>"><br>

    <label>Kontak 2</label>
    <input class="w3-input w3-border w3-round" type="number" name="KONTAK2" value="<?php echo htmlspecialchars($data['KONTAK2'], ENT_QUOTES, 'UTF-8'); ?>"><br>

    <label>Email</label>
    <input class="w3-input w3-border w3-round" type="email" name="EMAIL" value="<?php echo htmlspecialchars($data['EMAIL'], ENT_QUOTES, 'UTF-8'); ?>"><br>


    <label for="STATUS">Status:</label>
        <select name="STATUS" id="STATUS" required class="w3-select w3-border">
            <option value="1" <?= ($data['STATUS'] == 1) ? 'selected' : ''; ?>>Aktif</option>
            <option value="0" <?= ($data['STATUS'] == 0) ? 'selected' : ''; ?>>Tidak Aktif</option>
        </select><br><br>

    <label>User Modified</label>
    <input class="w3-input w3-border w3-round" type="text" name="USERMODIFIED" value="<?php echo htmlspecialchars($data['USERMODIFIED'], ENT_QUOTES, 'UTF-8'); ?>" readonly><br>

    <input type="hidden" name="IDSEKOLAH" value="<?php echo htmlspecialchars($data['IDSEKOLAH'], ENT_QUOTES, 'UTF-8'); ?>">

    <div class="w3-half">
        <a href="list_siswa.php" class="w3-button w3-container w3-orange w3-padding-16" style="width: 100%;">Kembali</a>
    </div>
    <div class="w3-half">
        <input type="hidden" name="action" value="edit">
        <input type="submit" value="Simpan" class="w3-button w3-container w3-green w3-padding-16" style="width: 100%;">
    </div>
</form>

<!-- Modal for Error (NIS already exists) -->
<?php if ($errorModal): ?>
    <div id="errorModal" class="w3-modal" style="display: block;">
        <div class="w3-modal-content w3-animate-top w3-card-4">
            <header class="w3-container w3-red">
                <span class="w3-button w3-display-topright" onclick="document.getElementById('errorModal').style.display='none'">&times;</span>
                <h2>Informasi</h2>
            </header>
            <div class="w3-container">
                <p><?php echo htmlspecialchars($errorMessage); ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Modal for Confirmation -->
<?php if ($confirmationModal): ?>
    <div id="confirmationModal" class="w3-modal" style="display: block;">
        <div class="w3-modal-content w3-animate-top w3-card-4">
            <header class="w3-container w3-brown">
                <span class="w3-button w3-display-topright" onclick="document.getElementById('confirmationModal').style.display='none'">&times;</span>
                <h2>Konfirmasi</h2>
            </header>
            <div class="w3-container">
                <p>Apakah Anda yakin ingin mengubah NIS menjadi <?php echo htmlspecialchars($nis_baru); ?>?</p>
                <form method="POST" action="">
                    <!-- Kirim data yang sama saat konfirmasi -->
                    <input type="hidden" name="NIS_BARU" value="<?php echo htmlspecialchars($nis_baru); ?>">
                    <input type="hidden" name="NAMA" value="<?php echo htmlspecialchars($nama); ?>">
                    <input type="hidden" name="TEMPATLAHIR" value="<?php echo htmlspecialchars($tempatlahir); ?>">
                    <input type="hidden" name="TANGGALLAHIR" value="<?php echo htmlspecialchars($tanggallahir); ?>">
                    <input type="hidden" name="JENISKELAMIN" value="<?php echo htmlspecialchars($jeniskelamin); ?>">
                    <input type="hidden" name="ALAMAT" value="<?php echo htmlspecialchars($alamat); ?>">
                    <input type="hidden" name="NAMAAYAH" value="<?php echo htmlspecialchars($namaayah); ?>">
                    <input type="hidden" name="NAMAIBU" value="<?php echo htmlspecialchars($namaibu); ?>">
                    <input type="hidden" name="KONTAK1" value="<?php echo htmlspecialchars($kontak1); ?>">
                    <input type="hidden" name="KONTAK2" value="<?php echo htmlspecialchars($kontak2); ?>">
                    <input type="hidden" name="EMAIL" value="<?php echo htmlspecialchars($email); ?>">
                    <input type="hidden" name="STATUS" value="<?php echo htmlspecialchars($status); ?>">
                    <input type="hidden" name="USERMODIFIED" value="<?php echo htmlspecialchars($usermodified); ?>">
                    <input type="hidden" name="confirm" value="yes">
                    <button type="submit" class="w3-button w3-red w3-right">Ya</button>
                    <button type="button" class="w3-button w3-grey w3-right w3-margin-right" onclick="document.getElementById('confirmationModal').style.display='none'">Tidak</button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>


    <script>
         // Close error modal when user clicks outside of it
  window.onclick = function(event) {
        if (event.target == document.getElementById('errorModal')) {
            document.getElementById('errorModal').style.display = 'none';
        }
        if (event.target == document.getElementById('confirmationModal')) {
            document.getElementById('confirmationModal').style.display = 'none';
        }
    };

        function w3_open() {
            document.getElementById("mySidebar").classList.add('show');
            document.getElementById("sidebarOverlay").classList.add('show');
        }

        function w3_close() {
            document.getElementById("mySidebar").classList.remove('show');
            document.getElementById("sidebarOverlay").classList.remove('show');
        }
    </script>
</body>

</html>