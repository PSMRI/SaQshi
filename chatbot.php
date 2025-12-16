<?php
header('Content-Type: application/json; charset=utf-8');

// Knowledge base file
$QA_JSON = __DIR__ . "/qa_data.json";

/* ==========================================================
   LANGUAGE DETECTION
   ========================================================== */
function detect_language($text) {

    // Hindi written in Devanagari
    if (preg_match('/\p{Devanagari}/u', $text)) return "hi";

    // Hindi transliteration
    $hindi_words = ["kya","kaise","kyu","kyun","kab","kahan","kripya","dhanyavad",
                    "hai","nahi","hindi","kr","karta","karti","mera","meri"];

    $lower = mb_strtolower($text, "UTF-8");
    foreach ($hindi_words as $w) {
        if (strpos($lower, $w) !== false) return "hi";
    }

    return "en";
}

/* ==========================================================
   NORMALIZATION + SCORING
   ========================================================== */
function normalize($s) {
    $s = mb_strtolower($s, "UTF-8");
    $s = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $s);
    return trim(preg_replace('/\s+/', ' ', $s));
}

function overlap_score($a, $b) {
    $a_words = array_unique(explode(' ', normalize($a)));
    $b_words = array_unique(explode(' ', normalize($b)));

    $score = 0;
    foreach ($a_words as $w) if (in_array($w, $b_words)) $score++;
    return $score;
}

/* ==========================================================
   LOAD KNOWLEDGE BASE
   ========================================================== */
if (!file_exists($QA_JSON)) {
    echo json_encode(["ok"=>false, "error"=>"qa_data.json not found"], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents($QA_JSON), true);
$items = $data["questions"] ?? [];

/* ==========================================================
   READ USER QUERY
   ========================================================== */
$query = trim($_POST["query"] ?? $_GET["query"] ?? "");

if ($query === "") {
    echo json_encode(["ok"=>true, "answer"=>"", "lang"=>"en"]);
    exit;
}

$lang = detect_language($query);
$norm_query = normalize($query);

/* ==========================================================
   SMART DOWNLOAD HANDLING
   ========================================================== */

// User typed "download"
if (preg_match('/\b(download|डाउनलोड)\b/i', $query)) {

    echo json_encode([
        "ok"=>true,
        "answer"=>($lang === "hi")
            ? "आप क्या डाउनलोड करना चाहते हैं? कृपया दस्तावेज़ का नाम लिखें।<br><br>उदाहरण:<br>• Guidelines<br>• Registers<br>• ASHA Formats<br>• Protocols<br>• Outcome Formats"
            : "What would you like to download? Please specify the document name.<br><br>Examples:<br>• Guidelines<br>• Registers<br>• ASHA Formats<br>• Protocols<br>• Outcome Formats",
        "lang"=>$lang,
        "score"=>100
    ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    exit;
}

// Facility-only queries → prompt for document
$facility_keywords = ["hwc", "phc", "chc", "dh", "district hospital", "uphc"];

foreach ($facility_keywords as $fk) {
    if (stripos($norm_query, $fk) !== false && strlen($norm_query) <= strlen($fk) + 2) {

        echo json_encode([
            "ok"=>true,
            "answer"=>($lang === "hi")
                ? "$fk से संबंधित कौन-सा दस्तावेज़ चाहिए? उदाहरण: Guidelines, Registers, Protocols, CAPA"
                : "Which document do you want for $fk? Examples: Guidelines, Registers, Protocols, CAPA",
            "lang"=>$lang,
            "score"=>200
        ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        exit;
    }
}

// Recognized document keyword but not found
// =========================
// SMART DOCUMENT AUTO-MATCH
// =========================

$doc_keywords = [
    "guideline", "policy", "register", "format", "protocol", "capa", "asha",
    "iec", "jd", "skill", "outcome", "supporting", "social audit", "work instruction"
];

foreach ($doc_keywords as $dk) {
    if (stripos($norm_query, $dk) !== false) {

        // Try to find matching document entry in qa_data.json
        foreach ($items as $item) {
            if (!isset($item["keywords"])) continue;

            foreach ($item["keywords"] as $key) {
                if (stripos(normalize($key), $dk) !== false) {

                    // FOUND matching document
                    $answer = ($lang === "hi")
                        ? ($item["answer_hi"] ?? $item["answer_en"])
                        : ($item["answer_en"] ?? $item["answer_hi"]);

                    $desc = ($lang === "hi")
                        ? ($item["description_hi"] ?? "")
                        : ($item["description_en"] ?? "");

                    echo json_encode([
                        "ok" => true,
                        "answer" => $answer,
                        "description" => $desc,
                        "lang" => $lang,
                        "score" => 500
                    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    exit;
                }
            }
        }

        // No exact document found → fallback
        echo json_encode([
            "ok" => true,
            "answer" => ($lang === "hi")
                ? "'$dk' से संबंधित दस्तावेज़ उपलब्ध नहीं मिला। यदि उपलब्ध होगा तो मैं ज़रूर दूँगा।"
                : "No document found related to '$dk'. If available, I will definitely provide it.",
            "lang" => $lang,
            "score" => 50
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}


/* ==========================================================
   MATCHING ENGINE
   ========================================================== */
$best_item = null;
$best_score = -1;

foreach ($items as $item) {

    if (!isset($item["keywords"])) continue;

    foreach ($item["keywords"] as $kw) {

        $norm_kw = normalize($kw);
        if ($norm_kw === "") continue;

        // Exact substring match
        if (stripos($norm_query, $norm_kw) !== false || stripos($norm_kw, $norm_query) !== false) {
            $best_item = $item;
            $best_score = 999;
            break 2;
        }

        // Fuzzy match
        $score = overlap_score($norm_query, $norm_kw);
        if ($score > $best_score) {
            $best_score = $score;
            $best_item = $item;
        }
    }
}

/* ==========================================================
   RETURN ANSWER
   ========================================================== */
if ($best_item && $best_score > 0) {

    $answer = ($lang === "hi") 
              ? ($best_item["answer_hi"] ?? $best_item["answer_en"])
              : ($best_item["answer_en"] ?? $best_item["answer_hi"]);

    $desc = ($lang === "hi")
              ? ($best_item["description_hi"] ?? "")
              : ($best_item["description_en"] ?? "");

    echo json_encode([
        "ok"=>true,
        "answer"=>$answer,
        "description"=>$desc,
        "lang"=>$lang,
        "score"=>$best_score
    ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    exit;
}

/* ==========================================================
   FALLBACK
   ========================================================== */
echo json_encode([
    "ok"=>true,
    "answer"=>($lang==="hi")
        ? "क्षमा करें, मुझे इसके बारे में जानकारी नहीं मिली। कृपया अलग तरीके से पूछें।"
        : "Sorry, I could not find information on that. Please try rephrasing.",
    "lang"=>$lang,
    "score"=>0
], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

exit;
?>
