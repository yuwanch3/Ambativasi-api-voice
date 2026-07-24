<?php
require_once "db.php";

$email = isset($_GET['email']) ? trim($_GET['email']) : '';

if (empty($email)) {
    echo json_encode([
        "status" => "error",
        "message" => "Email tidak boleh kosong!"
    ]);
    exit();
}

$stmt = $conn->prepare("SELECT id, username, email, profile_image FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "status" => "success",
        "username" => $row['username'],
        "email" => $row['email'],
        "profile_image" => $row['profile_image']
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Pengguna tidak ditemukan!"
    ]);
}

$stmt->close();
$conn->close();
?>