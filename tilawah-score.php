<?php
require_once "db.php";
require_once "env.php";

const GEMINI_API_KEY = getenv('GEMINI_API_KEY');

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "status" => "error", "message" => "Method not allowed"]);
    exit();
}

if (!isset($_FILES["audio"]) || $_FILES["audio"]["error"] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "status" => "error", "message" => "File audio wajib dikirim"]);
    exit();
}

$surah = isset($_POST["surah"]) ? intval($_POST["surah"]) : 0;
$ayah = isset($_POST["ayah"]) ? intval($_POST["ayah"]) : 0;
$words = isset($_POST["words"]) ? json_decode($_POST["words"], true) : [];

if ($surah === 0 || $ayah === 0 || empty($words)) {
    echo json_encode(["success" => false, "status" => "error", "message" => "Parameter surah, ayah, dan words wajib"]);
    exit();
}

$audioFile = $_FILES["audio"]["tmp_name"];
$audioType = $_FILES["audio"]["type"];

$audioData = base64_encode(file_get_contents($audioFile));

$wordsJson = json_encode($words, JSON_UNESCAPED_UNICODE);

$promptText = <<<PROMPT
Kamu adalah ahli Tajwid Quran. Transkrip bacaan Quran dari audio berikut.

Surah Al-Fatihah ayat {$ayah}.

Kata-kata yang diharapkan (berurutan):
{$wordsJson}

Tugasmu:
1. Transkrip audio bacaan ini ke teks Arab
2. Cocokkan hasil transkrip dengan kata-kata yang diharapkan di atas secara berurutan
3. Beri status per kata: "correct" (bacaan tepat), "warning" (mirip tapi kurang tepat), "incorrect" (tidak sesuai atau tidak diucapkan)

Perhatikan kaidah Tajwid:
- "correct" jika makhraj dan panjang bacaan sesuai
- "warning" jika ada sedikit kesalahan tajwid (misal panjang pendek)
- "incorrect" jika kata berbeda atau tidak terbaca

Balas dalam format JSON SAJA tanpa markdown atau teks lain:
{
  "transcript": "teks arab hasil transkrip",
  "wordStatuses": ["correct", "warning", ...]
}
Array wordStatuses harus berisi tepat {$wordsJson} jumlah kata (setiap kata dapat 1 status).
PROMPT;

$payload = [
    "contents" => [[
        "parts" => [
            ["inline_data" => ["mime_type" => $audioType ?: "audio/m4a", "data" => $audioData]],
            ["text" => $promptText]
        ]
    ]],
    "generationConfig" => [
        "temperature" => 0.2,
        "maxOutputTokens" => 2048
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . GEMINI_API_KEY);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    $err = json_decode($response, true);
    $errMsg = isset($err["error"]["message"]) ? $err["error"]["message"] : "Gagal terhubung ke Gemini API.";
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => $errMsg
    ]);
    exit();
}

$data = json_decode($response, true);
$replyText = $data["candidates"][0]["content"]["parts"][0]["text"] ?? "";

$result = json_decode($replyText, true);

if (!$result || !isset($result["wordStatuses"])) {
    preg_match('/\{[^}]+\}/', $replyText, $matches);
    if (!empty($matches)) {
        $result = json_decode($matches[0], true);
    }
}

if (!$result || !isset($result["wordStatuses"])) {
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Gagal parsing respons Gemini",
        "raw" => $replyText
    ]);
    exit();
}

$wordStatuses = $result["wordStatuses"];
$transcript = $result["transcript"] ?? "";

$correctCount = count(array_filter($wordStatuses, fn($s) => $s === "correct"));
$warningCount = count(array_filter($wordStatuses, fn($s) => $s === "warning"));
$total = count($wordStatuses);
$score = $total > 0 ? round((($correctCount * 1 + $warningCount * 0.5) / $total) * 100) : 0;

echo json_encode([
    "success" => true,
    "status" => "success",
    "score" => $score,
    "transcript" => $transcript,
    "wordStatuses" => $wordStatuses
]);
