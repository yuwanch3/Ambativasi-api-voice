<?php
require_once "db.php";

const GEMINI_API_KEY = "AQ.Ab8RN6I_CnjapZultXopx6426xra2BgvrCPmhTgjIJ5d3Ics_Q";

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input["sumberData"])) {
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Parameter 'sumberData' wajib dikirim."
    ]);
    exit();
}

$sumberData = trim($input["sumberData"]);
$jumlahSoal = isset($input["jumlahSoal"]) ? (int)$input["jumlahSoal"] : 10;
$bahasaSoal = isset($input["bahasaSoal"]) ? $input["bahasaSoal"] : "Indonesia";

$lowerSumber = strtolower($sumberData);

$mataPelajaran = "Umum";
$levelMateri = "-";
$judulMateri = $sumberData;
$panduanSkripAsli = "";

if (
    strpos($lowerSumber, "nihongo") !== false ||
    strpos($lowerSumber, "bj") !== false ||
    strpos($lowerSumber, "jepang") !== false ||
    strpos($lowerSumber, "bab") !== false
) {
    $mataPelajaran = "Bahasa Jepang";
    $levelMateri = "N5";
    $judulMateri = "Bahasa Jepang Bab 1 / N5";
    $panduanSkripAsli = "Wajib menggunakan aksara Jepang asli (Kanji, Hiragana, atau Katakana) secara menyeluruh pada komponen soal tersebut.";
} elseif (
    strpos($lowerSumber, "fatihah") !== false ||
    strpos($lowerSumber, "tajwid") !== false ||
    strpos($lowerSumber, "tj") !== false
) {
    $mataPelajaran = "Tajwid";
    $levelMateri = "Dasar";
    $judulMateri = "Tajwid / Al-Fatihah";
    $panduanSkripAsli = "Wajib menggunakan potongan ayat Al-Qur'an bertuliskan teks Arab asli beserta tanda bacanya secara utuh.";
} elseif (
    strpos($lowerSumber, "petro") !== false ||
    strpos($lowerSumber, "petrofisika") !== false ||
    strpos($lowerSumber, "fundamental") !== false
) {
    $mataPelajaran = "Petrofisika";
    $levelMateri = "Fundamental / Teknik Perminyakan";
    $judulMateri = "Petrofisika Fundamental";
    $panduanSkripAsli = "Wajib menggunakan istilah teknis utama seperti Porositas, Permeabilitas, Saturasi Air (Sw), Log Gamma Ray, Log Resistivitas, Hukum Darcy, dan istilah Geologi/Reservoar yang relevan.";
} elseif (
    strpos($lowerSumber, "chemical") !== false ||
    strpos($lowerSumber, "eor") !== false ||
    strpos($lowerSumber, "polymer") !== false ||
    strpos($lowerSumber, "surfactant") !== false ||
    strpos($lowerSumber, "alkaline") !== false
) {
    $mataPelajaran = "Enhanced Oil Recovery";
    $levelMateri = "Dasar / Chemical EOR";
    $judulMateri = "Chemical EOR Dasar";
    $panduanSkripAsli = "Wajib menggunakan istilah teknis utama seperti Polymer Flooding (HPAM, Xanthan Gum), Surfactant, Alkaline, Mobility Ratio, Viskositas, Interfacial Tension (IFT), Sweep Efficiency, dan Capillary Number.";
} else {
    $panduanSkripAsli = "Wajib menggunakan istilah teknis utama atau kosakata bahasa asing asli yang paling relevan dengan subjek {$mataPelajaran}.";
}

$instruksiSistem = "
Anda adalah seorang guru pakar kurikulum profesional untuk mata pelajaran: \"{$mataPelajaran}\" (Level: {$levelMateri}).
Tugas Anda adalah meracik tepat {$jumlahSoal} soal pilihan ganda bermutu tinggi berdasarkan materi referensi.

⚠️ ATURAN DISTRIBUSI VARIASI SOAL (MUTLAK):
Dari total {$jumlahSoal} soal yang Anda buat, Anda WAJIB membaginya secara seimbang ke dalam 4 tipe berikut:
- Tipe \"standar\"
- Tipe \"full\"
- Tipe \"drag_drop\"
- Tipe \"fill_blank\"
Sebarkan urutan tipenya secara acak dari nomor 1 sampai {$jumlahSoal}.

DEFINISI 4 KARAKTERISTIK TIPE SOAL:
1. \"standar\" -> Kalimat pertanyaan dan pilihan A, B, C, D disajikan dalam bahasa pengantar reguler ({$bahasaSoal}).
2. \"full\" -> Aturan khusus: {$panduanSkripAsli} Seluruh string kalimat pertanyaan serta opsi pilihan A, B, C, D wajib ditulis menggunakan format khusus skrip asli tersebut.
3. \"drag_drop\" -> Soal susun kepingan kata interaktif. Kalimat 'pertanyaan' WAJIB mengandung satu simbol placeholder kosong yaitu \"___\" (tiga buah garis bawah). Sediakan kepingan kata pelengkap tunggal pada objek pilihan 'A', 'B', 'C', 'D' dan tentukan abjad jawaban yang benar ('A', 'B', 'C', atau 'D').
4. \"fill_blank\" -> Soal isian teks langsung (Keyboard). Kalimat 'pertanyaan' WAJIB mengandung satu simbol placeholder kosong yaitu \"___\".
   Khusus tipe 'fill_blank' ini, kolom 'jawaban_benar' HARUS berupa ARRAY DARI STRING yang menampung segala alternatif variasi penulisan jawaban yang sah dan diterima.
   ⚠️ WAJIB DITERAPKAN: Anda WAJIB memasukkan variasi penulisan teks Romaji / Alphabet / Latin (termasuk huruf kecil dan kapital, contoh: \"jin\", \"Jin\", \"sugoi\", \"al-fatihah\", \"mad wajib\") ke dalam array 'jawaban_benar', di samping variasi aksara/skrip asli (seperti Kanji/Hiragana/Katakana atau Teks Arab). Kolom 'pilihan' tetap diisi dengan 4 opsi dummy kata pengecoh pelengkap.

PANDUAN REFERENSI MATERI:
- Judul Materi: {$judulMateri}
- Subjek: {$mataPelajaran}

PANDUAN STRUKTUR JSON (GUARDRAILS):
Output HARUS berupa JSON Array murni dari objek-objek kuis. Objek di dalamnya wajib berstruktur:
- 'no' (integer)
- 'tipe_soal' (string: \"standar\", \"full\", \"drag_drop\", atau \"fill_blank\")
- 'pertanyaan' (string)
- 'pilihan' (objek dengan key 'A', 'B', 'C', 'D')
- 'jawaban_benar' (String 'A','B','C','D' untuk standar/full/drag_drop. Array of Strings khusus untuk 'fill_blank').
";

$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => "Buatkan tepat {$jumlahSoal} kuis campuran interaktif untuk materi '{$judulMateri}' sesuai aturan sistem."]
            ]
        ]
    ],
    "systemInstruction" => [
        "parts" => [["text" => $instruksiSistem]]
    ],
    "generationConfig" => [
        "responseMimeType" => "application/json",
        "temperature" => 0.85
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite:generateContent?key=" . GEMINI_API_KEY);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    $err = json_decode($response, true);
    $errMsg = isset($err["error"]["message"]) ? $err["error"]["message"] : "Gagal meracik soal via Gemini API.";
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => $errMsg
    ]);
    exit();
}

$data = json_decode($response, true);
$rawText = $data["candidates"][0]["content"]["parts"][0]["text"];
$parsedData = json_decode($rawText, true);

if (!$parsedData) {
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Gagal parse JSON dari respons Gemini."
    ]);
    exit();
}

$listSoal = isset($parsedData[0]) ? $parsedData : ($parsedData["soal"] ?? $parsedData["data"] ?? []);

echo json_encode([
    "success" => true,
    "status" => "success",
    "soal" => $listSoal
]);
?>