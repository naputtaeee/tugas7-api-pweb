<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// 1. Koneksi ke database kamu
$conn = new mysqli("localhost", "root", "", "db_penjualan_toko");

if ($conn->connect_error) {
    echo json_encode(["status" => false, "message" => "Koneksi database gagal"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    
    // 2. Ambil data username & password yang dikirim dari Postman
    $input = json_decode(file_get_contents('php://input'), true);
   $username = isset($input['username']) ? $conn->real_escape_string($input['username']) : '';
    $password = isset($input['password']) ? $conn->real_escape_string($input['password']) : '';

    if (empty($username) || empty($password)) {
        echo json_encode(["status" => false, "message" => "Username dan password tidak boleh kosong"]);
        exit;
    }

    // 3. Cek ke tabel users apakah akunnya cocok
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // 4. GENERATE TOKEN (Sesuai request bapak: berupa token, random text atau lainnya)
        $token = bin2hex(random_bytes(16)); 
        $id_user = $user['id_user'];

        // 5. Simpan token baru ini ke database tabel users kamu
        $update_query = "UPDATE users SET token = '$token' WHERE id_user = $id_user";
        $conn->query($update_query);

        // 6. Kirim respon sukses ke Postman beserta kuncinya
        echo json_encode([
            "status" => true,
            "message" => "Login Berhasil!",
            "token" => $token // Token acak yang bakal jadi kunci sakti
        ]);

    } else {
        // Validasi kalau username/password salah
        echo json_encode([
            "status" => false,
            "message" => "Autentikasi gagal! Username atau password salah."
        ]);
    }
} else {
    echo json_encode(["status" => false, "message" => "Method tidak diizinkan. Harus POST"]);
}
?>