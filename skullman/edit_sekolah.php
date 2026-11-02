<?php
include('koneksi.php');

session_start();

if (!isset($_SESSION["username"])) {
    header('Location: index.php');
    exit;
}

$username = $_SESSION['username']; // Pastikan session sudah diinisialisasi dan username tersedia

$idsekolah = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $namasekolah = mysqli_real_escape_string($conn, $_POST['NAMASEKOLAH']);
    $alamat = mysqli_real_escape_string($conn, $_POST['ALAMAT']);
    $notelp = mysqli_real_escape_string($conn, $_POST['NOTELP']);
    $status = mysqli_real_escape_string($conn, $_POST['STATUS']);
    $expireddate = mysqli_real_escape_string($conn, $_POST['EXPIREDDATE']);

    $sql = "UPDATE sekolah 
            SET NAMASEKOLAH='$namasekolah', 
                ALAMAT='$alamat', 
                NOTELP='$notelp', 
                STATUS='$status',
                EXPIREDDATE='$expireddate',
                USERMODIFIED='$username', 
                DATEMODIFIED=NOW() 
            WHERE IDSEKOLAH='$idsekolah'";

    if ($conn->query($sql) === TRUE) {
        echo "
<div class='w3-modal' style='display:block' id='successModal'>
        <div class='w3-modal-content w3-animate-top'>
            <header class='w3-container w3-green'>
                <span class='w3-button w3-hover-red w3-xlarge w3-display-topright' onclick=\"document.getElementById('successModal').style.display='none';\">&times;</span>
                <h2>Konfirmasi</h2>
            </header>
            <div class='w3-container'>
                <p>Data sekolah berhasil diperbarui.</p>
            </div>
        </div>
    </div>

    <script>
        // Automatically redirect after 1.5 seconds (1500ms)
        setTimeout(function() {
            window.location.href = 'list_sekolah.php';
        }, 1500);
    </script>
";
    } else {
        echo "
<div class='w3-modal' style='display:block' id='errorModal'>
    <div class='w3-modal-content w3-animate-top'>
        <header class='w3-container w3-red'>
            <span class='w3-button w3-hover-red w3-xlarge w3-display-topright'>&times;</span>
            <h2>Informasi</h2>
        </header>
        <div class='w3-container'>
            <p>Error: " . htmlspecialchars($conn->error) . "</p>
        </div>
    </div>
</div>

<script>
    setTimeout(function() {
        document.getElementById('errorModal').style.display = 'none';
    }, 3000);
</script>
";
    }
}

// Menampilkan data sekolah berdasarkan IDSEKOLAH
$sql = "SELECT * FROM sekolah WHERE IDSEKOLAH='$idsekolah'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
} else {
    die("Data sekolah tidak ditemukan.");
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Sekolah</title>
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
            <h3 style="margin: 0; line-height: 3.5rem; margin-bottom:10px;"><b>Edit Data Sekolah</b></h3>
        </div>
    </div>

    <div class="w3-padding-16">
        <form method="POST" class="w3-container w3-card-4 w3-light-grey w3-padding-16 w3-margin">
            <label>Nama Sekolah</label>
            <input type="text" name="NAMASEKOLAH" class="w3-input w3-border" value="<?php echo $row['NAMASEKOLAH']; ?>"
                required><br>

            <label>Alamat</label>
            <textarea name="ALAMAT" class="w3-input w3-border"><?php echo $row['ALAMAT']; ?></textarea><br>

            <label>No. Telp</label>
            <input type="text" name="NOTELP" class="w3-input w3-border" value="<?php echo $row['NOTELP']; ?>"><br>

            <label>Status</label>
            <select name="STATUS" class="w3-input w3-border">
                <option value="" disabled selected>Pilih Status</option>
                <option value="Aktif" <?php echo ($row['STATUS'] === 'Aktif') ? 'selected' : ''; ?>>Aktif</option>
                <option value="Tidak Aktif" <?php echo ($row['STATUS'] === 'Tidak Aktif') ? 'selected' : ''; ?>>Tidak
                    Aktif</option>
            </select><br>

            <label>Tanggal Expired</label>
            <input type="date" id="EXPIREDDATE" name="EXPIREDDATE" class="w3-input w3-border"
                value="<?php echo $row['EXPIREDDATE']; ?>"><br>


            <label>User Modified</label>
            <input type="text" id="usermodified" name="usermodified" class="w3-input w3-border w3-light-grey"
                value="<?php echo htmlspecialchars($row['USERMODIFIED']); ?>" readonly><br>

            <div class="w3-row">
                <div class="w3-half">
                    <a href="list_sekolah.php" class="w3-button w3-container w3-orange w3-padding-16"
                        style="width: 100%;">Kembali</a>
                </div>
                <div class="w3-half">
                    <input type="hidden" name="action" value="edit">
                    <input type="submit" value="Simpan" class="w3-button w3-container w3-green w3-padding-16"
                        style="width: 100%;">
                </div>
            </div>

        </form>
    </div>

    <script>
        function w3_open() {
            document.getElementById("mySidebar").classList.add('show');
            document.getElementById("sidebarOverlay").classList.add('show');
        }

        function w3_close() {
            document.getElementById("mySidebar").classList.remove('show');
            document.getElementById("sidebarOverlay").classList.remove('show');
        }

        document.addEventListener("DOMContentLoaded", function () {
            var inputs = document.querySelectorAll('input[required]');
            inputs.forEach(input => {
                input.addEventListener('invalid', function (event) {
                    event.preventDefault();
                    // Custom validation message
                    let message = "Mohon diisi, tidak boleh kosong";
                    input.setCustomValidity(message);
                    // Display the message
                    if (input.validity.valueMissing) {
                        input.reportValidity();
                    }
                });

                input.addEventListener('input', function () {
                    input.setCustomValidity(""); // Reset custom message on input
                });
            });
        });
    </script>
</body>

</html>