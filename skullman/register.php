<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta'); // Set to your timezone
session_start();
if (isset($_SESSION['username'])) {
    echo "<script> window.location.href='list_siswa.php' </script>";
    exit();
}

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
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
            // Check if Kepala Sekolah already exists for the given id_sekolah
            if ($jabatan === "Kepala Sekolah") {
                $check_kepala_query = "SELECT * FROM guru WHERE JABATAN = 'Kepala Sekolah' AND IDSEKOLAH = '$id_sekolah'";
                $kepala_result = mysqli_query($conn, $check_kepala_query);

                if (mysqli_num_rows($kepala_result) > 0) {
                    $error_message = "Kepala Sekolah sudah ada. Silahkan gunakan Jabatan lain.";
                }
                mysqli_free_result($kepala_result);
            }

            // If no errors, insert the data
            if (empty($error_message)) {
                $query = "INSERT INTO guru (NIP, NAMA, TANGGALLAHIR, ALAMAT, JENISKELAMIN, AGAMA, PENDIDIKANTERAKHIR, TANGGALMULAIBEKERJA, IDSEKOLAH, JABATAN, STATUS, USERNAME, PASSWORD, DATERECORD, DATEMODIFIED, USERRECORD, USERMODIFIED, void)
                          VALUES ('$nip', '$nama', '$tanggal_lahir', '$alamat', '$jenis_kelamin', '$agama', '$pendidikan_terakhir', '$tanggal_mulai_bekerja', '$id_sekolah', '$jabatan', '$status', '$username', '$password', NOW(), NOW(), '$username', '$username', 0)";

                if (mysqli_query($conn, $query)) {
                    $success_message = "Data Guru Berhasil Disimpan.";
                } else {
                    $error_message = "Error: " . mysqli_error($conn);
                }
            }
        }
        mysqli_free_result($result);
    }
}

// Fetch list of schools from the 'sekolah' table where void is 0
$sekolah_query = "SELECT idsekolah, namasekolah FROM sekolah WHERE status = 'Aktif' AND void = 0";
$sekolah_result = mysqli_query($conn, $sekolah_query);

// Check for query execution success
if (!$sekolah_result) {
    die("Query failed: " . mysqli_error($conn));
}

mysqli_close($conn);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/png" href="skullman.png">
</head>
<style>
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

    @keyframes heartbeat {
        0% {
            transform: scale(1);
            /* Normal size */
        }

        25% {
            transform: scale(1.1);
            /* Slightly bigger */
        }

        50% {
            transform: scale(1);
            /* Back to normal size */
        }

        75% {
            transform: scale(1.1);
            /* Slightly bigger */
        }

        100% {
            transform: scale(1);
            /* Normal size again */
        }
    }

    .w3-center img {
        animation: heartbeat 2s infinite;
        /* 1.5s duration and repeat forever */
        width: 120px;
        /* Increased image size */
    }
</style>

<body>
    <br>
    <div class="w3-center">
        <img src="skullman.png" alt="Logo" style="vertical-align: middle;">
        <h2 class="w3-container w3-center" style="font-family: 'Arial', sans-serif; color: #333; font-weight: bold;">
            <!-- <b>paus</b> -->
        </h2>
    </div>

    <?php if ($error_message): ?>
        <div id="idgagal" class="w3-modal" style="display:block;" onclick="this.style.display='none'">
            <div class="w3-modal-content">
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
            <div class="w3-modal-content">
                <header class="w3-container w3-green">
                    <span class="w3-button w3-hover-red w3-xlarge w3-display-topright"
                        onclick="document.getElementById('idberhasil').style.display='none'">&times;</span>
                    <h2>Konfirmasi</h2>
                </header>
                <div class="w3-container">
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                </div>
                <footer class="w3-container w3-white" style="text-align: right;">
                    <button id="okButton" class="w3-button w3-green">OK</button>
                </footer>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                var modal = document.getElementById("idberhasil");
                var okButton = document.getElementById("okButton");

                okButton.addEventListener("click", function () {
                    modal.style.display = "none";
                    window.location.href = "index.php";
                });
            });
        </script>
    <?php endif; ?>

    <form class="w3-container w3-card-4 w3-light-grey w3-padding-16 w3-margin" action="" method="post">
        <h2 class="w3-center"><b>Register</b></h2>

        <label for="nip">NIP</label>
        <input type="text" id="nip" class="w3-input w3-light-grey" name="nip" required>

        <label for="nama">Nama</label>
        <input type="text" id="nama" class="w3-input w3-light-grey" name="nama" required>

        <label for="tanggal_lahir">Tanggal Lahir</label>
        <input type="date" id="tanggal_lahir" class="w3-input w3-light-grey" name="tanggal_lahir" required>

        <label for="alamat">Alamat</label>
        <input type="text" id="alamat" class="w3-input w3-light-grey" name="alamat" required>

        <label for="jenis_kelamin">Jenis Kelamin</label>
        <select id="jenis_kelamin" class="w3-select w3-light-grey" name="jenis_kelamin" required>
            <option value="" disabled selected>Pilih Jenis Kelamin</option>
            <option value="Laki-laki">Laki-laki</option>
            <option value="Perempuan">Perempuan</option>
        </select>

        <label for="agama">Agama</label>
        <input type="text" id="agama" class="w3-input w3-light-grey" name="agama" required>

        <label for="pendidikan_terakhir">Pendidikan Terakhir</label>
        <input type="text" id="pendidikan_terakhir" class="w3-input w3-light-grey" name="pendidikan_terakhir" required>

        <label for="tanggal_mulai_bekerja">Tanggal Mulai Bekerja</label>
        <input type="date" id="tanggal_mulai_bekerja" class="w3-input w3-light-grey" name="tanggal_mulai_bekerja"
            required>

        <label for="id_sekolah">Sekolah</label>
        <select id="id_sekolah" class="w3-select w3-light-grey" name="id_sekolah" required>
            <option value="" disabled selected>Pilih Sekolah</option>
            <?php while ($sekolah = mysqli_fetch_assoc($sekolah_result)): ?>
                <option value="<?php echo $sekolah['idsekolah']; ?>"><?php echo $sekolah['namasekolah']; ?></option>
            <?php endwhile; ?>
        </select>

        <label for="jabatan">Jabatan</label>
        <div style="display: flex; align-items: center;">
            <select id="jabatan" class="w3-select w3-light-grey" name="jabatan" onchange="handleJabatanChange()"
                required>
                <option value="" disabled selected>Pilih Jabatan</option>
                <option value="Guru">Guru</option>
                <option value="Kepala Sekolah">Kepala Sekolah</option>
                <option value="Lainnya">Lainnya</option>
            </select>

            <!-- Input field that appears when "Lainnya" is selected, positioned next to the dropdown -->
            <input type="text" id="jabatanLainnya" name="jabatanLainnya" style="display:none; margin-left: 10px;"
                placeholder="Masukkan jabatan lainnya">
        </div>

        <label for="username">Username</label>
        <input type="text" id="username" class="w3-input w3-light-grey" name="username" required>

        <!-- Password Field -->
        <div class="w3-input-container" style="position: relative; margin-bottom: 30px;">
            <label for="password">Password</label>
            <input type="password" id="password" class="w3-input w3-border-bottom w3-light-grey password-field"
                name="password" required>
            <!-- Eye icon inside the input field -->
            <i id="togglePassword" class="fa fa-eye"
                style="position: absolute; right: 10px; top: 35px; cursor: pointer; z-index: 1;"></i>
        </div>

        <!-- Confirm Password Field -->
        <div class="w3-input-container" style="position: relative; margin-bottom: 30px;">
            <label for="password_confirm">Konfirmasi Password</label>
            <input type="password" id="password_confirm" class="w3-input w3-border-bottom w3-light-grey password-field"
                name="password_confirm" required>
            <!-- Eye icon inside the input field -->
            <i id="togglePasswordConfirm" class="fa fa-eye"
                style="position: absolute; right: 10px; top: 35px; cursor: pointer; z-index: 1;"></i>
        </div>
        <input type="hidden" value="Bekerja" name="status">
        <button type="submit" name="submit"
            class="w3-input w3-button w3-round-large w3-brown w3-margin-top">Register</button>
        <a href="index.php" class="w3-input w3-button w3-round-large w3-red w3-margin-top">Login</a>
    </form>

    <script>
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
            var inputs = document.querySelectorAll('input[required], select[required]'); // Menangkap input dan select yang required

            inputs.forEach(input => {
                input.addEventListener('invalid', function (event) {
                    event.preventDefault();
                    // Pesan validasi custom
                    let message = "Mohon diisi, tidak boleh kosong";

                    // Khusus untuk select
                    if (input.tagName.toLowerCase() === 'select') {
                        message = "Silakan pilih salah satu opsi";
                    }

                    input.setCustomValidity(message);

                    // Tampilkan pesan jika nilai belum valid
                    if (input.validity.valueMissing) {
                        input.reportValidity();
                    }
                });

                input.addEventListener('input', function () {
                    input.setCustomValidity(""); // Reset pesan custom ketika ada perubahan input
                });
            })
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

        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordField = document.getElementById('password');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                this.classList.replace('fa-eye', 'fa-eye-slash'); // Change icon to "eye-slash"
            } else {
                passwordField.type = 'password';
                this.classList.replace('fa-eye-slash', 'fa-eye'); // Change icon back to "eye"
            }
        });

        document.getElementById('togglePasswordConfirm').addEventListener('click', function () {
            const confirmPasswordField = document.getElementById('password_confirm');
            if (confirmPasswordField.type === 'password') {
                confirmPasswordField.type = 'text';
                this.classList.replace('fa-eye', 'fa-eye-slash'); // Change icon to "eye-slash"
            } else {
                confirmPasswordField.type = 'password';
                this.classList.replace('fa-eye-slash', 'fa-eye'); // Change icon back to "eye"
            }
        });
        // Toggle Password Visibility for Password Field
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordField = document.getElementById('password');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                this.classList.replace('fa-eye', 'fa-eye-slash'); // Change icon to "eye-slash"
            } else {
                passwordField.type = 'password';
                this.classList.replace('fa-eye-slash', 'fa-eye'); // Change icon back to "eye"
            }
        });

        // Toggle Password Visibility for Confirm Password Field
        document.getElementById('togglePasswordConfirm').addEventListener('click', function () {
            const confirmPasswordField = document.getElementById('password_confirm');
            if (confirmPasswordField.type === 'password') {
                confirmPasswordField.type = 'text';
                this.classList.replace('fa-eye', 'fa-eye-slash'); // Change icon to "eye-slash"
            } else {
                confirmPasswordField.type = 'password';
                this.classList.replace('fa-eye-slash', 'fa-eye'); // Change icon back to "eye"
            }
        });
    </script>
</body>

</html>