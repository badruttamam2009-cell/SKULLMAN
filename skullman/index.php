<?php
session_start();
include 'koneksi.php';

// Check if user is already logged in
if (isset($_SESSION["username"])) {
    echo "<script> window.location.href='dashboard.php' </script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login</title>
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
    </div>
    <form class="w3-container w3-card-4 w3-light-grey w3-padding-16 w3-margin" action="" method="post">
        <h2 class="w3-container w3-center"><b>Login</b></h2>
        <label for="username">Username</label>
        <input type="text" class="w3-input w3-light-grey" name="username" required>

        <!-- Password Field -->
        <div class="w3-input-container" style="position: relative; margin-bottom: 30px;">
            <label for="password">Password</label>
            <input type="password" id="password" class="w3-input w3-border-bottom w3-light-grey password-field" name="password" required>
            <!-- Eye icon inside the input field -->
            <i id="togglePassword" class="fa fa-eye" style="position: absolute; right: 10px; top: 35px; cursor: pointer; z-index: 1;"></i>
        </div>

        <button type="submit" name="submit" value="Login" class="w3-input w3-button w3-round-large w3-red w3-margin-top">Login</button>
        <a href="register.php" class="w3-input w3-button w3-round-large w3-brown w3-margin-top">Register</a>
    </form>

    <!-- Modal for failed login -->
    <div id="idgagal" class="w3-modal" onclick="closeModal(event)">
        <div class="w3-modal-content w3-animate-top w3-card-4">
            <header class="w3-container w3-red">
                <span onclick="document.getElementById('idgagal').style.display='none'"
                    class="w3-button w3-display-topright">&times;</span>
                <h2>Informasi</h2>
            </header>
            <div class="w3-container">
                <p>Login Gagal, periksa Username dan Password Anda.</p>
            </div>
        </div>
    </div>

    <!-- Modal for expired subscription -->
    <div id="idExpired" class="w3-modal" style="display:none;" onclick="closeModal(event)">
        <div class="w3-modal-content w3-animate-top w3-card-4">
            <header class="w3-container w3-red">
                <span onclick="document.getElementById('idExpired').style.display='none'"
                    class="w3-button w3-display-topright">&times;</span>
                <h2>Informasi</h2>
            </header>
            <div class="w3-container">
                <p>Login Gagal, Masa Berlangganan Telah Habis, Mohon Kontak Neev Solusindo</p>
            </div>
        </div>
    </div>


    <!-- Modal for unconfirmed user -->
    <div id="idUnconfirmed" class="w3-modal" onclick="closeModal(event)">
        <div class="w3-modal-content w3-animate-top w3-card-4">
            <header class="w3-container w3-brown">
                <span onclick="document.getElementById('idUnconfirmed').style.display='none'"
                    class="w3-button w3-display-topright">&times;</span>
                <h2>Informasi</h2>
            </header>
            <div class="w3-container">
                <p>Akun Anda belum dikonfirmasi. Silakan tunggu konfirmasi dari admin.</p>
            </div>
        </div>
    </div>
    </div>

    <!-- Modal for idInactive user -->
    <div id="idInactive" class="w3-modal" onclick="closeModal(event)">
        <div class="w3-modal-content w3-animate-top w3-card-4">
            <header class="w3-container w3-red">
                <span onclick="document.getElementById('idInactive').style.display='none'"
                    class="w3-button w3-display-topright">&times;</span>
                <h2>Informasi</h2>
            </header>
            <div class="w3-container">
                <p>Login Gagal, status anda tidak Bekerja.</p>
            </div>
        </div>
    </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var inputs = document.querySelectorAll('input[required]');

            inputs.forEach(input => {
                input.addEventListener('invalid', function(event) {
                    event.preventDefault(); // Prevent the browser's default validation
                    input.setCustomValidity("Mohon diisi, tidak boleh kosong"); // Custom validation message
                    input.reportValidity(); // Display the message
                });

                input.addEventListener('input', function() {
                    input.setCustomValidity(""); // Reset custom message on input
                });
            });

            // Close modal when clicking outside of it
            document.addEventListener('click', function(event) {
                var modals = document.querySelectorAll('.w3-modal');
                modals.forEach(function(modal) {
                    if (event.target === modal) {
                        modal.style.display = 'none';
                    }
                });
            });

            // Toggle password visibility
            document.getElementById('togglePassword').addEventListener('click', function() {
                const passwordField = document.getElementById('password');
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    this.classList.replace('fa-eye', 'fa-eye-slash'); // Change icon to "eye-slash"
                } else {
                    passwordField.type = 'password';
                    this.classList.replace('fa-eye-slash', 'fa-eye'); // Change icon back to "eye"
                }
            });
        });
    </script>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Pastikan koneksi $conn telah dibuat sebelum blok ini
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);

        // Query untuk memeriksa login
        $sql = "SELECT g.NIP, g.USERNAME, g.VERIFIED, g.NAMA, g.STATUS, g.JABATAN, s.EXPIREDDATE 
            FROM guru g
            LEFT JOIN sekolah s ON g.IDSEKOLAH = s.IDSEKOLAH
            WHERE g.USERNAME = '$username' AND g.PASSWORD = '$password'";

        $result = mysqli_query($conn, $sql);

        // Periksa apakah query menghasilkan baris
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);

            // Periksa jika username adalah 'admin'
            if ($row['USERNAME'] === 'admin') {
                // Abaikan VERIFIED, EXPIREDDATE, dan IDSEKOLAH untuk admin
                session_start();
                $_SESSION["username"] = $row['USERNAME'];
                $_SESSION["nama"] = $row['NAMA'];
                $_SESSION["jabatan"] = $row['JABATAN'];
                $_SESSION["nip"] = $row['NIP'];  // Menyimpan NIP admin
                echo "<script>window.location.href='dashboard.php';</script>";
            } else {
                // Cek status pengguna guru
                if ($row['STATUS'] != 'Bekerja') {
                    // Guru tidak aktif, tampilkan modal status tidak aktif
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            document.getElementById('idInactive').style.display = 'block';
                        });
                      </script>";
                    exit;
                }

                // Ambil tanggal hari ini
                $currentDate = date('Y-m-d');

                // Periksa status verifikasi
                if ($row['VERIFIED'] != 1) {
                    // Modal pengguna belum terverifikasi
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            document.getElementById('idUnconfirmed').style.display = 'block';
                        });
                      </script>";
                } else {
                    // Cek masa berlaku langganan untuk pengguna non-admin
                    if (!empty($row['EXPIREDDATE']) && $row['EXPIREDDATE'] < $currentDate) {
                        // Modal masa langganan habis
                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                document.getElementById('idExpired').style.display = 'block';
                            });
                          </script>";
                    } else {
                        // Login berhasil untuk pengguna non-admin
                        session_start();
                        $_SESSION["username"] = $row['USERNAME'];
                        $_SESSION["nama"] = $row['NAMA'];
                        $_SESSION['jabatan'] = $row['JABATAN'];
                        $_SESSION["nip"] = $row['NIP'];  // Menyimpan NIP guru
                        echo "<script>window.location.href='dashboard.php';</script>";
                    }
                }
            }
        } else {
            // Username atau password salah, tampilkan modal gagal login
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('idgagal').style.display = 'block';
                });
              </script>";
        }

        // Bebaskan result set
        if ($result) {
            mysqli_free_result($result);
        }
    }

    // Tutup koneksi
    $conn->close();
    ?>
    </script>
</body>

</html>