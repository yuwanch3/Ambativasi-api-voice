<?php
require_once "db.php";

$input = json_decode(file_get_contents("php://input"), true);
$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? trim($input['password']) : '';

if (empty($email) || empty($password)) {
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Email dan password wajib diisi!"
    ]);
    exit();
}

$stmt = $conn->prepare("SELECT id, username, email, password, profile_image FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    // Memeriksa password (hash BCRYPT atau plain text)
    if (password_verify($password, $user['password']) || $password === $user['password']) {
        echo json_encode([
            "success" => true,
            "status" => "success",
            "message" => "Login berhasil!",
            "user" => [
                "id" => $user['id'],
                "username" => $user['username'],
                "email" => $user['email'],
                "profile_image" => $user['profile_image']
            ]
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "status" => "error",
            "message" => "Password salah!"
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Email tidak terdaftar!"
    ]);
}

$stmt->close();
$conn->close();
?>