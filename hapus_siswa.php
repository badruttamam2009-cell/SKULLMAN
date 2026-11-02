<?php
include 'koneksi.php';

if (isset($_GET['nis'])) {
    $nis = mysqli_real_escape_string($conn, $_GET['nis']);

    // Update kolom VOID atau STATUS untuk menandai siswa sebagai tidak aktif
    $sql = "UPDATE siswa SET STATUS = 0, VOID = 1 WHERE NIS = '$nis'";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: list_siswa.php?message=void_success");
        exit;
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    header("Location: list_siswa.php?message=invalid_request");
    exit;
}
?>
