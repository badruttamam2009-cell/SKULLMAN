<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');
session_start();

if (!isset($_SESSION["username"])) {
    header('Location: index.php');
    exit;
}

// Ambil username dari session
$username = mysqli_real_escape_string($conn, $_SESSION['username']);

// Ambil data NIP dari URL
$nip = isset($_GET['NIP']) ? mysqli_real_escape_string($conn, $_GET['NIP']) : '';

if (!$nip) {
    die("NIP tidak ditemukan.");
}

// Ambil data guru berdasarkan NIP
$query = "SELECT * FROM guru WHERE NIP = '$nip'";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Data guru tidak ditemukan.");
}

$data = mysqli_fetch_assoc($result);

// Modal error dan konfirmasi
$errorModal = false;
$errorMessage = '';
$confirmationModal = false;

// Jika form di-submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $nip_baru = $_POST['NIP_BARU'] ?? $nip;
    $nama = $_POST['NAMA'] ?? $data['NAMA'];
    $tanggal_lahir = $_POST['TANGGALLAHIR'] ?? $data['TANGGALLAHIR'];
    $alamat = $_POST['ALAMAT'] ?? $data['ALAMAT'];
    $jenis_kelamin = $_POST['JENISKELAMIN'] ?? $data['JENISKELAMIN'];
    $agama = $_POST['AGAMA'] ?? $data['AGAMA'];
    $pendidikan_terakhir = $_POST['PENDIDIKANTERAKHIR'] ?? $data['PENDIDIKANTERAKHIR'];
    $tanggal_mulai_kerja = $_POST['TANGGALMULAIBEKERJA'] ?? $data['TANGGALMULAIBEKERJA'];
    $id_sekolah = $_POST['IDSEKOLAH'] ?? $data['IDSEKOLAH'];
    $jabatan = $_POST['JABATAN'] ?? $data['JABATAN'];
    $status = $_POST['STATUS'] ?? $data['STATUS'];
    $datemodified = date('Y-m-d H:i:s');
    $usermodified = $username;
    $verified = isset($_POST['VERIFIED']) ? 1 : 0;

    if (isset($_POST['JABATAN']) && $_POST['JABATAN'] === 'Lainnya') {
        // Ambil nilai dari input 'jabatanLainnya'
        $jabatan = mysqli_real_escape_string($conn, trim($_POST['jabatanLainnya']));
    } else {
        // Ambil nilai dari dropdown
        $jabatan = mysqli_real_escape_string($conn, trim($_POST['JABATAN']));
    }

    // Validasi kepala sekolah
    if ($jabatan === 'Kepala Sekolah') {
        $check_kepala_query = "SELECT * FROM guru WHERE IDSEKOLAH = '$id_sekolah' AND JABATAN = 'Kepala Sekolah' AND VOID = 0 AND NIP != '$nip'";
        $check_kepala_result = mysqli_query($conn, $check_kepala_query);

        if ($check_kepala_result && mysqli_num_rows($check_kepala_result) > 0) {
            $errorModal = true;
            $errorMessage = "Kepala Sekolah sudah ada. Silakan gunakan Jabatan lain.";
        }
    }

    // Cek apakah NIP baru sudah ada
    if ($nip_baru !== $nip) {
        $check_nip_query = "SELECT NIP FROM guru WHERE NIP = '$nip_baru' AND NIP != '$nip'";
        $check_nip_result = mysqli_query($conn, $check_nip_query);

        if ($check_nip_result && mysqli_num_rows($check_nip_result) > 0) {
            $errorModal = true;
            $errorMessage = "NIP '$nip_baru' sudah ada. Gunakan NIP lain.";
        } else {
            $confirmationModal = true; // Tampilkan modal konfirmasi
        }
    }

    // Jika tidak ada error dan tidak ada perubahan NIP
    if (!$errorModal && !$confirmationModal) {
        $update_query = "UPDATE guru SET 
                            NAMA = '$nama', 
                            TANGGALLAHIR = '$tanggal_lahir', 
                            ALAMAT = '$alamat', 
                            JENISKELAMIN = '$jenis_kelamin', 
                            AGAMA = '$agama', 
                            PENDIDIKANTERAKHIR = '$pendidikan_terakhir', 
                            TANGGALMULAIBEKERJA = '$tanggal_mulai_kerja', 
                            IDSEKOLAH = '$id_sekolah', 
                            JABATAN = '$jabatan', 
                            STATUS = '$status', 
                            DATEMODIFIED = '$datemodified', 
                            USERMODIFIED = '$usermodified',
                            VERIFIED = '$verified'
                        WHERE NIP = '$nip'";

        if (mysqli_query($conn, $update_query)) {
            header('Location: list_guru.php');
            exit;
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }

    // Jika konfirmasi "Ya"
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        $update_query_confirm = "UPDATE guru SET 
                                    NIP = '$nip_baru', 
                                    NAMA = '$nama', 
                                    TANGGALLAHIR = '$tanggal_lahir', 
                                    ALAMAT = '$alamat', 
                                    JENISKELAMIN = '$jenis_kelamin', 
                                    AGAMA = '$agama', 
                                    PENDIDIKANTERAKHIR = '$pendidikan_terakhir', 
                                    TANGGALMULAIBEKERJA = '$tanggal_mulai_kerja', 
                                    IDSEKOLAH = '$id_sekolah', 
                                    JABATAN = '$jabatan', 
                                    STATUS = '$status', 
                                    DATEMODIFIED = '$datemodified', 
                                    USERMODIFIED = '$usermodified',
                                    VERIFIED = '$verified'
                                WHERE NIP = '$nip'";

        if (mysqli_query($conn, $update_query_confirm)) {
            header('Location: list_guru.php');
            exit;
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Guru</title>
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

        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            /* Lebar slider */
            height: 34px;
            /* Tinggi slider */
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            /* Warna default slider */
            transition: 0.4s;
            border-radius: 34px;
            /* Membuat slider bulat */
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            /* Tinggi lingkaran */
            width: 26px;
            /* Lebar lingkaran */
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
            /* Membuat lingkaran */
        }

        input:checked+.slider {
            background-color: rgb(76, 175, 80);
            /* Warna slider saat aktif */
        }

        input:checked+.slider:before {
            transform: translateX(26px);
            /* Geser lingkaran ke kanan */
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
        <a href="#" class="w3-bar-item w3-button w3-border">
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
            <h3 style="margin: 0; line-height: 3.5rem; margin-bottom:10px;"><b>Edit Data Guru</b></h3>
        </div>
    </div>

    <div class="w3-container">

        <form action="" method="post" class="w3-container w3-card-4 w3-light-grey w3-padding-16 w3-margin-top">

            <label>NIP</label>
            <input class="w3-input w3-border w3-round" type="text" id="NIP" name="NIP_BARU"
                value="<?php echo htmlspecialchars($data['NIP'], ENT_QUOTES, 'UTF-8'); ?>" required><br>

            <label for="nama">Nama</label>
            <input type="text" id="nama" class="w3-input w3-border" name="nama"
                value="<?php echo htmlspecialchars($data['NAMA']); ?>" required><br>

            <label for="tanggal_lahir">Tanggal Lahir</label>
            <input type="date" id="tanggal_lahir" class="w3-input w3-border" name="tanggal_lahir"
                value="<?php echo htmlspecialchars($data['TANGGALLAHIR']); ?>" required><br>

            <label for="alamat">Alamat</label>
            <textarea id="alamat" class="w3-input w3-border" name="alamat"
                required><?php echo htmlspecialchars($data['ALAMAT']); ?></textarea><br>

            <label for="jenis_kelamin">Jenis Kelamin</label>
            <select id="jenis_kelamin" class="w3-select" name="jenis_kelamin" required>
                <option value="Laki-laki" <?php if ($data['JENISKELAMIN'] == "Laki-laki")
                    echo "selected"; ?>>Laki-laki
                </option>
                <option value="Perempuan" <?php if ($data['JENISKELAMIN'] == "Perempuan")
                    echo "selected"; ?>>Perempuan
                </option>
            </select><br><br>

            <label for="agama">Agama</label>
            <input type="text" id="agama" class="w3-input w3-border" name="agama"
                value="<?php echo htmlspecialchars($data['AGAMA']); ?>" required><br>

            <label for="pendidikan_terakhir">Pendidikan Terakhir</label>
            <input type="text" id="pendidikan_terakhir" class="w3-input w3-border" name="pendidikan_terakhir"
                value="<?php echo htmlspecialchars($data['PENDIDIKANTERAKHIR']); ?>" required><br>

            <label for="tanggal_mulai_kerja">Tanggal Mulai Kerja</label>
            <input type="date" id="tanggal_mulai_kerja" class="w3-input w3-border" name="tanggal_mulai_kerja"
                value="<?php echo htmlspecialchars($data['TANGGALMULAIBEKERJA']); ?>" required><br>

            <input type="hidden" id="id_sekolah" name="id_sekolah" value="<?php echo $data['IDSEKOLAH']; ?>">

            <label for="jabatan">Jabatan</label>
            <div style="display: flex; align-items: center;">
                <select id="jabatan" class="w3-select" name="JABATAN" required onchange="handleJabatanChange()">
                    <option value="" disabled selected>Pilih Jabatan</option>
                    <option value="Guru" <?php if ($data['JABATAN'] == "Guru")
                        echo "selected"; ?>>Guru</option>
                    <option value="Kepala Sekolah" <?php if ($data['JABATAN'] == "Kepala Sekolah")
                        echo "selected"; ?>>
                        Kepala Sekolah</option>
                    <option value="Lainnya" <?php if ($data['JABATAN'] == "Lainnya")
                        echo "selected"; ?>>Lainnya</option>
                </select>
                <!-- Input field for "Lainnya" -->
                <input type="text" id="jabatanLainnya" name="jabatanLainnya" style="display: none; margin-left: 10px;"
                    placeholder="Masukkan jabatan lainnya"
                    value="<?php echo $data['JABATAN'] == 'Lainnya' ? htmlspecialchars($data['jabatanLainnya'] ?? '') : ''; ?>">
            </div><br>

            <label for="status">Status</label>
            <select id="status" class="w3-select" name="STATUS" required>
                <option value="" disabled <?= empty($data['STATUS']) ? "selected" : ""; ?>>Pilih Status</option>
                <option value="Bekerja" <?= ($data['STATUS'] === "Bekerja" ? "selected" : ""); ?>>Bekerja</option>
                <option value="Tidak Bekerja" <?= ($data['STATUS'] === "Tidak Bekerja" ? "selected" : ""); ?>>Tidak Bekerja
                </option>
            </select><br><br>

            <label for="username">Username</label>
            <input type="text" id="username" class="w3-input w3-border" name="username"
                value="<?php echo htmlspecialchars($data['USERNAME']); ?>" required><br>

            <!-- Password Field -->
            <div class="w3-input-container" style="position: relative; margin-bottom: 30px;">
                <label for="password">Password</label>
                <input type="password" id="password" class="w3-input w3-white w3-border-bottom password-field"
                    name="password" value="<?php echo htmlspecialchars($data['PASSWORD']); ?>" required>
                <!-- Eye icon inside the input field -->
                <i id="togglePassword" class="fa fa-eye"
                    style="position: absolute; right: 10px; top: 35px; cursor: pointer; z-index: 1;"></i>
            </div>

            <!-- Confirm Password Field -->
            <div class="w3-input-container" style="position: relative; margin-bottom: 30px;">
                <label for="password_confirm">Konfirmasi Password</label>
                <input type="password" id="password_confirm" class="w3-input w3-white w3-border-bottom password-field"
                    name="password_confirm" value="<?php echo htmlspecialchars($data['PASSWORD']); ?>" required>
                <!-- Eye icon inside the input field -->
                <i id="togglePasswordConfirm" class="fa fa-eye"
                    style="position: absolute; right: 10px; top: 35px; cursor: pointer; z-index: 1;"></i>
            </div>

            <label>Verifikasi</label><br>
            <label class="switch">
                <input type="checkbox" id="VERIFIED" name="VERIFIED" <?php echo $data['VERIFIED'] ? 'checked' : ''; ?>>
                <span class="slider"></span>
            </label><br><br>

            <label>User Modified</label>
            <input type="text" id="usermodified" name="usermodified" class="w3-input w3-border w3-light-grey"
                value="<?php echo htmlspecialchars($data['USERMODIFIED']); ?>" readonly><br>

            <div class="w3-row">
                <div class="w3-half">
                    <a href="list_guru.php" class="w3-button w3-container w3-orange w3-padding-16"
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

    <!-- Modal for Error (NIP already exists) -->
    <?php if ($errorModal): ?>
        <div id="errorModal" class="w3-modal" style="display: block;">
            <div class="w3-modal-content w3-animate-top w3-card-4">
                <header class="w3-container w3-red">
                    <span class="w3-button w3-display-topright"
                        onclick="document.getElementById('errorModal').style.display='none'">&times;</span>
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
                    <span class="w3-button w3-display-topright"
                        onclick="document.getElementById('confirmationModal').style.display='none'">&times;</span>
                    <h2>Konfirmasi</h2>
                </header>
                <div class="w3-container">
                    <p>Apakah Anda yakin ingin mengubah NIP menjadi <?php echo htmlspecialchars($nip_baru); ?>?</p>
                    <form method="POST" action="">
                        <!-- Kirim data yang sama saat konfirmasi -->
                        <input type="hidden" name="NIP_BARU" value="<?php echo htmlspecialchars($nip_baru); ?>">
                        <input type="hidden" name="NAMA" value="<?php echo htmlspecialchars($nama); ?>">
                        <input type="hidden" name="TANGGALLAHIR" value="<?php echo htmlspecialchars($tanggal_lahir); ?>">
                        <input type="hidden" name="ALAMAT" value="<?php echo htmlspecialchars($alamat); ?>">
                        <input type="hidden" name="JENISKELAMIN" value="<?php echo htmlspecialchars($jenis_kelamin); ?>">
                        <input type="hidden" name="AGAMA" value="<?php echo htmlspecialchars($agama); ?>">
                        <input type="hidden" name="PENDIDIKANTERAKHIR"
                            value="<?php echo htmlspecialchars($pendidikan_terakhir); ?>">
                        <input type="hidden" name="TANGGALMULAIBEKERJA"
                            value="<?php echo htmlspecialchars($tanggal_mulai_kerja); ?>">
                        <input type="hidden" name="IDSEKOLAH" value="<?php echo htmlspecialchars($id_sekolah); ?>">
                        <input type="hidden" name="JABATAN" value="<?php echo htmlspecialchars($jabatan); ?>">
                        <input type="hidden" name="STATUS" value="<?php echo htmlspecialchars($status); ?>">
                        <input type="hidden" name="USERNAME" value="<?php echo htmlspecialchars($username); ?>">
                        <input type="hidden" name="PASSWORD" value="<?php echo htmlspecialchars($password); ?>">
                        <input type="hidden" name="USERMODIFIED" value="<?php echo htmlspecialchars($usermodified); ?>">
                        <input type="hidden" name="confirm" value="yes">
                        <button type="submit" class="w3-button w3-red w3-right">Ya</button>
                        <button type="button" class="w3-button w3-grey w3-right w3-margin-right"
                            onclick="document.getElementById('confirmationModal').style.display='none'">Tidak</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Close error modal when user clicks outside of it
        window.onclick = function (event) {
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

        function handleJabatanChange() {
            const jabatanDropdown = document.getElementById('jabatan');
            const jabatanLainnyaInput = document.getElementById('jabatanLainnya');

            // Show or hide the input field based on selection
            if (jabatanDropdown.value === 'Lainnya') {
                jabatanLainnyaInput.style.display = 'block';
                jabatanLainnyaInput.required = true; // Make it required
            } else {
                jabatanLainnyaInput.style.display = 'none';
                jabatanLainnyaInput.required = false; // Remove required if not needed
            }
        }

        // Call function on page load to ensure correct visibility
        document.addEventListener('DOMContentLoaded', handleJabatanChange);

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

        function trimInputs() {
            document.getElementById('username').value = document.getElementById('username').value.trim();
            return true;
        }

        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordField = document.getElementById('password');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                this.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordField.type = 'password';
                this.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        document.getElementById('togglePasswordConfirm').addEventListener('click', function () {
            const confirmPasswordField = document.getElementById('password_confirm');
            if (confirmPasswordField.type === 'password') {
                confirmPasswordField.type = 'text';
                this.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                confirmPasswordField.type = 'password';
                this.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    </script>
</body>

</html>