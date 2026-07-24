<?php
require_once "db.php";

// Membaca data JSON yang dikirimkan dari React Native (ResetPasswordScreen)
$input = json_decode(file_get_contents("php://input"), true);

$email    = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? trim($input['password']) : '';

// 1. Validasi Input Kosong
if (empty($email) || empty($password)) {
    echo json_encode([
        "success" => false,
        "message" => "Email dan kata sandi baru tidak boleh kosong!"
    ]);
    exit();
}

// 2. Cek Apakah Email Terdaftar di Database MySQL
$check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check_stmt->bind_param("s", $email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Akun dengan email tersebut tidak ditemukan!"
    ]);
    $check_stmt->close();
    $conn->close();
    exit();
}
$check_stmt->close();

// 3. Enkripsi Kata Sandi Baru dengan BCRYPT
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// 4. Update Password Baru ke Database
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $hashed_password, $email);

if ($stmt->execute()) {
    // Memicu json.success = true di React Native -> Menampilkan Toast Success
    echo json_encode([
        "success" => true,
        "message" => "Password berhasil diubah!"
    ]);
} else {
    // Memicu json.success = false di React Native -> Menampilkan Toast Error
    echo json_encode([
        "success" => false,
        "message" => "Gagal mengbarui password di database. Silakan coba lagi!"
    ]);
}

$stmt->close();
$conn->close();
?>