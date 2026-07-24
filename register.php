<?php
require_once "db.php";

// Membaca data JSON yang dikirimkan dari React Native (register.tsx)
$input = json_decode(file_get_contents("php://input"), true);

$username = isset($input['username']) ? trim($input['username']) : '';
$email    = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? trim($input['password']) : '';

// 1. Validasi Input Kosong
if (empty($username) || empty($email) || empty($password)) {
    echo json_encode([
        "success" => false,
        "message" => "Semua kolom wajib diisi!"
    ]);
    exit();
}

// 2. Validasi Format Email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "Format email tidak valid!"
    ]);
    exit();
}

// 3. Cek Apakah Email Sudah Terdaftar di Database MySQL
$check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check_stmt->bind_param("s", $email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Email sudah terdaftar! Silakan gunakan email lain atau masuk."
    ]);
    $check_stmt->close();
    $conn->close();
    exit();
}
$check_stmt->close();

// 4. Enkripsi Kata Sandi dengan BCRYPT
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// 5. Simpan Data User Baru ke Tabel `users`
$stmt = $conn->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("sss", $username, $email, $hashed_password);

if ($stmt->execute()) {
    // Dipanggil jika pendaftaran sukses -> langsung memicu Toast Success di React
    echo json_encode([
        "success" => true,
        "message" => "Registrasi berhasil! Silakan masuk ke akun kamu."
    ]);
} else {
    // Dipanggil jika query gagal -> memicu Toast Error di React
    echo json_encode([
        "success" => false,
        "message" => "Gagal mendaftarkan akun ke database. Silakan coba lagi!"
    ]);
}

$stmt->close();
$conn->close();
?>