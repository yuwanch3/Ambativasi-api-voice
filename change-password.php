<?php
require_once "db.php";

$input = json_decode(file_get_contents("php://input"), true);
$email = isset($input['email']) ? trim($input['email']) : '';
$old_password = isset($input['old_password']) ? trim($input['old_password']) : '';
$new_password = isset($input['new_password']) ? trim($input['new_password']) : '';

if (empty($email) || empty($old_password) || empty($new_password)) {
    echo json_encode(["status" => "error", "message" => "Semua field harus diisi!"]);
    exit();
}

$stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($old_password, $user['password']) || $old_password === $user['password']) {
        $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);
        $update_stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE email = ?");
        $update_stmt->bind_param("ss", $hashed_new_password, $email);

        if ($update_stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Kata sandi berhasil diperbarui!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal memperbarui kata sandi."]);
        }
        $update_stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Kata sandi lama tidak sesuai!"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Pengguna tidak ditemukan!"]);
}

$stmt->close();
$conn->close();
?>