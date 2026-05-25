<?php
function validasiToken($conn) {
    // Kita cek melalui Header Postman dengan nama 'Authorization'
    $headers = getallheaders();
    $token = null;

    if (isset($headers['Authorization'])) {
        $token = $headers['Authorization'];
    }

    // Kalau user sama sekali gak bawa token/kunci
    if (empty($token)) {
        echo json_encode([
            "status" => false, 
            "message" => "Akses ditolak! Token autentikasi tidak ditemukan."
        ]);
        exit;
    }

    // 2. Cek ke database apakah token ini bener/cocok ada di tabel users
    $query = "SELECT * FROM users WHERE token = '$token'";
    $result = $conn->query($query);

    if ($result->num_rows === 0) {
        // Kalau tokennya salah / palsu / sudah kadaluwarsa
        echo json_encode([
            "status" => false, 
            "message" => "Akses ditolak! Token tidak valid atau salah."
        ]);
        exit;
    }

    // Jika token benar, fungsi ini diam saja dan memperbolehkan kodingan bawahnya berjalan
}
?>