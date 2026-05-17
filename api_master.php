<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

// Koneksi ke database kamu
$conn = new mysqli("localhost", "root", "", "db_penjualan_toko");

if ($conn->connect_error) {
    echo json_encode(["status" => false, "message" => "Koneksi gagal"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$resource = isset($_GET['resource']) ? $_GET['resource'] : '';

// Pastikan resource-nya kalau bukan produk ya pelanggan
if ($resource !== 'produk' && $resource !== 'pelanggan') {
    echo json_encode(["status" => false, "message" => "Resource tidak valid. Pilih 'produk' atau 'pelanggan'"]);
    exit;
}

$table = $resource;
$id_column = "id_" . $resource;

switch ($method) {
    case 'GET':
        // 1. READ DATA
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $query = "SELECT * FROM $table WHERE $id_column = $id";
            $result = $conn->query($query)->fetch_assoc();
            echo json_encode(["status" => true, "data" => $result]);
        } else {
            $query = "SELECT * FROM $table";
            $result = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
            echo json_encode(["status" => true, "count" => count($result), "data" => $result]);
        }
        break;

    case 'POST':
        // 2. CREATE DATA
        $input = json_decode(file_get_contents('php://input'), true);
        if ($resource === 'produk') {
            $nama = $input['nama_produk'];
            $harga = $input['harga'];
            $stok = $input['stok'];
            $id_kategori = $input['id_kategori'];
            $query = "INSERT INTO produk (nama_produk, harga, stok, id_kategori) VALUES ('$nama', $harga, $stok, $id_kategori)";
        } else {
            $nama = $input['nama_pelanggan'];
            $email = $input['email'];
            $telepon = $input['telepon'];
            $query = "INSERT INTO pelanggan (nama_pelanggan, email, telepon) VALUES ('$nama', '$email', '$telepon')";
        }
        
        if ($conn->query($query)) {
            echo json_encode(["status" => true, "message" => "Data master $resource berhasil ditambahkan"]);
        } else {
            echo json_encode(["status" => false, "message" => "Gagal tambah data: " . $conn->error]);
        }
        break;

    case 'PUT':
        // 3. UPDATE DATA
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($_GET['id']);
        
        if ($resource === 'produk') {
            $nama = $input['nama_produk'];
            $harga = $input['harga'];
            $stok = $input['stok'];
            $query = "UPDATE produk SET nama_produk='$nama', harga=$harga, stok=$stok WHERE id_produk=$id";
        } else {
            $nama = $input['nama_pelanggan'];
            $email = $input['email'];
            $telepon = $input['telepon'];
            $query = "UPDATE pelanggan SET nama_pelanggan='$nama', email='$email', telepon='$telepon' WHERE id_pelanggan=$id";
        }

        if ($conn->query($query)) {
            echo json_encode(["status" => true, "message" => "Data master $resource berhasil diubah"]);
        } else {
            echo json_encode(["status" => false, "message" => "Gagal mengubah data"]);
        }
        break;

    case 'DELETE':
        // 4. DELETE DATA
        if (!isset($_GET['id'])) {
            echo json_encode(["status" => false, "message" => "ID diperlukan untuk menghapus"]);
            exit;
        }
        $id = intval($_GET['id']);
        $query = "DELETE FROM $table WHERE $id_column = $id";
        
        if ($conn->query($query)) {
            echo json_encode(["status" => true, "message" => "Data master $resource dengan ID $id berhasil dihapus"]);
        } else {
            echo json_encode(["status" => false, "message" => "Gagal menghapus data, kemungkinan data terikat transaksi"]);
        }
        break;
}
?>