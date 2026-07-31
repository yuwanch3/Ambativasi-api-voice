<?php
require_once "db.php";

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input["email"]) || !isset($input["correct"]) || !isset($input["total"])) {
    echo json_encode([
        "status" => "error",
        "message" => "Parameter email, correct, dan total wajib dikirim."
    ]);
    exit();
}

$email = trim($input["email"]);
$type = isset($input["type"]) && $input["type"] === "ujian" ? "ujian" : "latihan";
$correct = intval($input["correct"]);
$total = intval($input["total"]);
$streak = isset($input["streak"]) ? max(0, intval($input["streak"])) : 0;

if ($total <= 0) {
    echo json_encode(["status" => "error", "message" => "Total soal tidak valid."]);
    exit();
}

// Cari user berdasarkan email
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    echo json_encode(["status" => "error", "message" => "Pengguna tidak ditemukan."]);
    exit();
}
$userId = $row["id"];
$stmt->close();

// Hitung XP
$xpHit = $correct * 10;
$typeBonus = $type === "ujian" ? 100 : 50;
$perfectBonus = ($correct === $total) ? 50 : 0;

// Bonus streak: hanya sekali per hari
$streakBonus = 0;
$today = date("Y-m-d");

$stmt = $conn->prepare("SELECT total_xp, attempts, last_streak_bonus_date FROM user_xp WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$xpRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

$currentXp = $xpRow ? intval($xpRow["total_xp"]) : 0;
$attempts = $xpRow ? intval($xpRow["attempts"]) : 0;
$lastStreakDate = $xpRow && $xpRow["last_streak_bonus_date"] ? $xpRow["last_streak_bonus_date"] : null;

if ($streak > 0 && $lastStreakDate !== $today) {
    $streakBonus = $streak * 20;
}

$totalXpGained = $xpHit + $typeBonus + $perfectBonus + $streakBonus;
$newTotalXp = $currentXp + $totalXpGained;
$newAttempts = $attempts + 1;

// Upsert
if ($xpRow) {
    $stmt = $conn->prepare("UPDATE user_xp SET total_xp = ?, attempts = ?, last_streak_bonus_date = ? WHERE user_id = ?");
    $newStreakDate = ($streakBonus > 0) ? $today : $lastStreakDate;
    $stmt->bind_param("iisi", $newTotalXp, $newAttempts, $newStreakDate, $userId);
} else {
    $stmt = $conn->prepare("INSERT INTO user_xp (user_id, total_xp, attempts, last_streak_bonus_date) VALUES (?, ?, ?, ?)");
    $newStreakDate = ($streakBonus > 0) ? $today : null;
    $stmt->bind_param("iiis", $userId, $newTotalXp, $newAttempts, $newStreakDate);
}
$stmt->execute();
$stmt->close();

$level = floor($newTotalXp / 500) + 1;

echo json_encode([
    "status" => "success",
    "total_xp" => $newTotalXp,
    "level" => $level,
    "xp_gained" => $totalXpGained,
    "breakdown" => [
        "jawaban_benar" => $xpHit,
        "bonus_" . $type => $typeBonus,
        "bonus_perfect" => $perfectBonus,
        "bonus_streak" => $streakBonus
    ]
]);

$conn->close();
