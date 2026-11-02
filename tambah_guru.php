<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta'); // Set to your timezone

session_start();

if (!isset($_SESSION["username"])) {
    header('Location: index.php');
    exit;
}

// Define variables and set them to empty values
$nip = $nama = $tanggal_lahir = $alamat = $jenis_kelamin = $agama = $pendidikan_terakhir = "";
$tanggal_mulai_bekerja = $id_sekolah = $jabatan = $status = $username = $password = $password_confirm = "";
$error_message = $success_message = "";

// Handling the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nip = mysqli_real_escape_string($conn, trim($_POST['nip']));
    $nama = mysqli_real_escape_string($conn, trim($_POST['nama']));
    $tanggal_lahir = mysqli_real_escape_string($conn, trim($_POST['tanggal_lahir']));
    $alamat = mysqli_real_escape_string($conn, trim($_POST['alamat']));
    $jenis_kelamin = mysqli_real_escape_string($conn, trim($_POST['jenis_kelamin']));
    $agama = mysqli_real_escape_string($conn, trim($_POST['agama']));
    $pendidikan_terakhir = mysqli_real_escape_string($conn, trim($_POST['pendidikan_terakhir']));
    $tanggal_mulai_bekerja = mysqli_real_escape_string($conn, trim($_POST['tanggal_mulai_bekerja']));
    $id_sekolah = mysqli_real_escape_string($conn, trim($_POST['id_sekolah']));

    // Check if 'Lainnya' (Other) was selected and update jabatan accordingly
    if (isset($_POST['jabatan']) && $_POST['jabatan'] === 'Lainnya') {
        $jabatan = mysqli_real_escape_string($conn, trim($_POST['jabatanLainnya']));
    } else {
        $jabatan = mysqli_real_escape_string($conn, trim($_POST['jabatan']));
    }

    $status = mysqli_real_escape_string($conn, trim($_POST['status']));
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = mysqli_real_escape_string($conn, trim($_POST['password']));
    $password_confirm = mysqli_real_escape_string($conn, trim($_POST['password_confirm']));

    // Password validation
    if ($password !== $password_confirm) {
        $error_message = "Password dan konfirmasi password tidak sesuai.";
    } else {
        // Check if NIP already exists
        $check_query = "SELECT NIP FROM guru WHERE NIP = '$nip'";
        $result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($result) > 0) {
            $error_message = "NIP sudah ada. Silakan gunakan NIP lain.";
        } else {
            // Check if Kepala Sekolah already exists for the selected school
            if ($jabatan === 'Kepala Sekolah') {
                $check_kepala_query = "SELECT * FROM guru WHERE IDSEKOLAH = '$id_sekolah' AND JABATAN = 'Kepala Sekolah' AND VOID = 0";
                $kepala_result = mysqli_query($conn, $check_kepala_query);

                if (mysqli_num_rows($kepala_result) > 0) {
                    $error_message = "Kepala Sekolah sudah ada. Silakan gunakan Jabatan lain.";
                }
            }

            // If no error message, proceed with the insertion
            if (empty($error_message)) {
                $query = "INSERT INTO guru (NIP, NAMA, TANGGALLAHIR, ALAMAT, JENISKELAMIN, AGAMA, PENDIDIKANTERAKHIR, TANGGALMULAIBEKERJA, IDSEKOLAH, JABATAN, STATUS, USERNAME, PASSWORD, DATERECORD, DATEMODIFIED, USERRECORD, USERMODIFIED, VOID, VERIFIED)
                          VALUES ('$nip', '$nama', '$tanggal_lahir', '$alamat', '$jenis_kelamin', '$agama', '$pendidikan_terakhir', '$tanggal_mulai_bekerja', '$id_sekolah', '$jabatan', '$status', '$username', '$password', NOW(), NOW(), '$username', '$username', 0, 0)";

                if (mysqli_query($conn, $query)) {
                    $success_message = "Data Guru Berhasil Ditambahkan.";
                } else {
                    $error_message = "Error: " . mysqli_error($conn);
                }
            }
        }
        mysqli_free_result($result);
    }
}

// Fetch list of schools from the 'sekolah' table
$sekolah_query = "SELECT idsekolah, namasekolah FROM sekolah WHERE status = 'Aktif' AND void = 0";
$sekolah_result = mysqli_query($conn, $sekolah_query);

if (!$sekolah_result) {
    die("Error fetching schools: " . mysqli_error($conn));
}

mysqli_close($conn);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Tambah Data Gurur</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        .password-field {
            border: none;
            /* Remove all borders */
            border-bottom: 1px solid #ccc;
            /* Add bottom border */
            border-radius: 0;
            /* Remove rounded corners */
            padding-right: 30px;
            /* Space for the icon */
            background: none;
            /* Transparent background */
            box-shadow: none;
            /* Remove shadow */
        }

        .password-field:focus {
            border-bottom: 1px solid #000;
            /* Highlighted bottom border on focus */
            outline: none;
            /* Remove focus outline */
        }

        .w3-input-container {
            margin-top: 20px;
            position: relative;
            /* Ensure relative positioning */
        }

        i.fa-eye,
        i.fa-eye-slash {
            position: absolute;
            /* Absolute positioning for the icon */
            right: 10px;
            /* Right margin for positioning */
            top: 35px;
            /* Adjust to align with input */
            cursor: pointer;
            /* Cursor pointer for better UX */
            z-index: 1;
            /* Higher z-index to ensure clickability */
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
            <h3 style="margin: 0; line-height: 3.5rem; margin-bottom:10px;"><b>Tambah Data Guru</b></h3>
        </div>
    </div>

    <body>

        <?php if ($error_message): ?>
            <div id="idgagal" class="w3-modal" style="display:block;" onclick="this.style.display='none'">
                <div class="w3-modal-content w3-animate-top">
                    <header class="w3-container w3-red">
                        <span class="w3-button w3-hover-red w3-xlarge w3-display-topright">&times;</span>
                        <h2>Informasi</h2>
                    </header>
                    <div class="w3-container">
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                </div>
            </div>
        <?php elseif ($success_message): ?>
            <div id="idberhasil" class="w3-modal" style="display:block;">
                <div class="w3-modal-content w3-animate-top">
                    <header class="w3-container w3-green">
                        <span class="w3-button w3-hover-red w3-xlarge w3-display-topright"
                            onclick="document.getElementById('idberhasil').style.display='none'">&times;</span>
                        <h2>Informasi</h2>
                    </header>
                    <div class="w3-container">
                        <p><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    var modal = document.getElementById("idberhasil");

                    // Set a timeout of 1.5 seconds (1500 milliseconds)
                    setTimeout(function () {
                        modal.style.display = "none"; // Close the modal
                        window.location.href = "list_guru.php"; // Redirect to list_guru.php
                    }, 1500); // 1.5 seconds
                });
            </script>
        <?php endif; ?>

        <form class="w3-container w3-card-4 w3-light-grey w3-padding-16 w3-margin" action="" method="post">

            <label for="nip">NIP</label>
            <input type="text" id="nip" class="w3-input w3-border" name="nip" required><br>

            <label for="nama">Nama</label>
            <input type="text" id="nama" class="w3-input w3-border" name="nama" required><br>

            <label for="tanggal_lahir">Tanggal Lahir</label>
            <input type="date" id="tanggal_lahir" class="w3-input w3-border" name="tanggal_lahir" required><br>

            <label for="alamat">Alamat</label>
            <textarea type="text" id="alamat" class="w3-input w3-border" name="alamat" required></textarea><br>

            <label for="jenis_kelamin">Jenis Kelamin</label>
            <select id="jenis_kelamin" class="w3-select" name="jenis_kelamin" required>
                <option value="" disabled selected>Pilih Jenis Kelamin</option>
                <option value="Laki-laki">Laki-laki</option>
                <option value="Perempuan">Perempuan</option>
            </select><br><br>

            <label for="agama">Agama</label>
            <input type="text" id="agama" class="w3-input w3-border" name="agama" required><br>

            <label for="pendidikan_terakhir">Pendidikan Terakhir</label>
            <input type="text" id="pendidikan_terakhir" class="w3-input w3-border" name="pendidikan_terakhir"
                required><br>

            <label for="tanggal_mulai_bekerja">Tanggal Mulai Bekerja</label>
            <input type="date" id="tanggal_mulai_bekerja" class="w3-input w3-border" name="tanggal_mulai_bekerja"
                required><br>

            <label for="id_sekolah">Sekolah</label>
            <select id="id_sekolah" class="w3-select" name="id_sekolah" required>
                <option value="" disabled selected>Pilih Sekolah</option>
                <?php while ($sekolah = mysqli_fetch_assoc($sekolah_result)): ?>
                    <option value="<?php echo $sekolah['idsekolah']; ?>"><?php echo $sekolah['namasekolah']; ?></option>
                <?php endwhile; ?>
            </select><br><br>

            <label for="jabatan">Jabatan</label>
            <div style="display: flex; align-items: center;">
                <select id="jabatan" class="w3-select" name="jabatan" onchange="handleJabatanChange()" required>
                    <option value="" disabled selected>Pilih Jabatan</option>
                    <option value="Guru">Guru</option>
                    <option value="Kepala Sekolah">Kepala Sekolah</option>
                    <option value="Lainnya">Lainnya</option>
                </select>

                <!-- Input field that appears when "Lainnya" is selected, positioned next to the dropdown -->
                <input type="text" id="jabatanLainnya" name="jabatanLainnya" style="display:none; margin-left: 10px;"
                    placeholder="Masukkan jabatan lainnya">
            </div><br>



            <label for="status">Status</label>
            <select id="status" class="w3-select" name="status" required>
                <option value="Bekerja">Bekerja</option>
            </select><br><br>


            <label for="username">Username</label>
            <input type="text" id="username" class="w3-input w3-border" name="username" required>

            <!-- Password Field -->
            <div class="w3-input-container" style="position: relative; margin-bottom: 30px;">
                <label for="password">Password</label>
                <input type="password" id="password" class="w3-input w3-white w3-border-bottom password-field"
                    name="password" required>
                <!-- Eye icon inside the input field -->
                <i id="togglePassword" class="fa fa-eye"
                    style="position: absolute; right: 10px; top: 35px; cursor: pointer; z-index: 1;"></i>
            </div>

            <!-- Confirm Password Field -->
            <div class="w3-input-container" style="position: relative; margin-bottom: 30px;">
                <label for="password_confirm">Konfirmasi Password</label>
                <input type="password" id="password_confirm" class="w3-input w3-white w3-border-bottom password-field"
                    name="password_confirm" required>
                <!-- Eye icon inside the input field -->
                <i id="togglePasswordConfirm" class="fa fa-eye"
                    style="position: absolute; right: 10px; top: 35px; cursor: pointer; z-index: 1;"></i>
            </div><br>

            <input type="hidden" name="USERRECORD" value="<?php echo htmlspecialchars($username); ?>">

            <div class="w3-row">
                <div class="w3-half">
                    <a href="list_guru.php" class="w3-button w3-container w3-orange w3-padding-16"
                        style="width: 100%;">Kembali</a>
                </div>
                <div class="w3-half">
                    <input type="hidden" name="action" value="tambah">
                    <input type="submit" value="Simpan" class="w3-button w3-container w3-green w3-padding-16"
                        style="width: 100%;">
                </div>
            </div>
        </form>

        <script>
            function w3_open() {
                document.getElementById("mySidebar").classList.add('show');
                document.getElementById("sidebarOverlay").classList.add('show');
            }

            function w3_close() {
                document.getElementById("mySidebar").classList.remove('show');
                document.getElementById("sidebarOverlay").classList.remove('show');
            }

            function handleJabatanChange() {
                var jabatan = document.getElementById('jabatan').value;
                var jabatanLainnyaInput = document.getElementById('jabatanLainnya');

                // Show the input field only when "Lainnya" is selected
                if (jabatan === 'Lainnya') {
                    jabatanLainnyaInput.style.display = 'block';
                } else {
                    jabatanLainnyaInput.style.display = 'none';
                }
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

            function trimInputs() {
                document.getElementById('username').value = document.getElementById('username').value.trim();
                document.getElementById('nama').value = document.getElementById('nama').value.trim();
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