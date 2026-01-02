<?php
/**
 * Veo 3 Kids Prompt Generator - Main App
 * Features: Multi-scene generation, Language Toggle, API Proxy with Backoff
 */
session_start();

// Helper to load config
function getConfig() {
    $file = 'config.json';
    if (!file_exists($file)) return ['gemini_key' => ''];
    return json_decode(file_get_contents($file), true);
}

// API Proxy Logic
if (isset($_GET['action']) && $_GET['action'] === 'generate') {
    header('Content-Type: application/json');
    $config = getConfig();
    $apiKey = $config['gemini_key'] ?? '';

    if (empty($apiKey)) {
        echo json_encode(['error' => 'API Key is missing. Please set it in the Admin panel.']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    // System Instruction based on parameters
    $systemPrompt = "You are an expert AI Video Prompt Engineer for kids educational animation.
    Create a video generation plan for Veo 3 and matching audio narration.
    Target Profession: {$data['profession']}
    Visual Style: {$data['style']}
    Age Group: {$data['age']}
    Intensity: {$data['intensity']}
    Language: " . ($data['lang'] === 'id' ? 'Bahasa Indonesia' : 'English') . "
    
    STRICT RULES:
    1. Child-safe, no violence, educational, positive.
    2. Professional: {$data['profession']}.
    3. Scenes: {$data['scenes']}.
    4. Progression: Intro -> Tools -> Activity -> Moral Value -> Loopable Ending.
    5. Output must be structured JSON.
    
    JSON SCHEMA:
    {
      \"scenes\": [
        {
          \"title\": \"string\",
          \"video_prompt\": \"string (highly descriptive for Veo 3, cinematic, textures, lighting)\",
          \"camera\": \"string\",
          \"action\": \"string\",
          \"mood\": \"string\",
          \"audio\": \"string (narration + background music cues)\"
        }
      ]
    }";

    $userQuery = "Generate a {$data['scenes']}-scene animation script about being a {$data['profession']}. 
    Duration: {$data['duration']}. Style: {$data['style']}. Tone: {$data['narration']} voice with {$data['music']} music.";

    // Gemini API Request Payload
    $payload = [
        'contents' => [['parts' => [['text' => $userQuery]]]],
        'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
        'generationConfig' => [
            'responseMimeType' => 'application/json',
            'responseSchema' => [
                'type' => 'OBJECT',
                'properties' => [
                    'scenes' => [
                        'type' => 'ARRAY',
                        'items' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'title' => ['type' => 'STRING'],
                                'video_prompt' => ['type' => 'STRING'],
                                'camera' => ['type' => 'STRING'],
                                'action' => ['type' => 'STRING'],
                                'mood' => ['type' => 'STRING'],
                                'audio' => ['type' => 'STRING']
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-09-2025:generateContent?key=" . $apiKey;

    // Implementation of exponential backoff is handled in JS for user feedback, 
    // but here is a basic cURL call.
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo json_encode(['error' => 'API Error: ' . $response]);
    } else {
        echo $response;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veo 3 Kids Prompt Gen</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <div class="container nav-flex">
            <h1 class="logo">✨ KidGen <span>Veo 3</span></h1>
            <div class="nav-links">
                <button id="langToggle" class="btn-secondary">🇮🇩 ID / 🇬🇧 EN</button>
                <a href="admin.php" class="btn-text">Admin</a>
            </div>
        </div>
    </nav>

    <main class="container">
        <section class="generator-card">
            <div class="form-grid">
                <div class="input-group">
                    <label for="profession">Profession / Cita-cita</label>
                    <input type="text" id="profession" placeholder="e.g. Dokter, Astronot..." required>
                </div>

                <div class="input-group">
                    <label for="duration">Duration</label>
                    <select id="duration">
                        <option value="30s">30 Seconds</option>
                        <option value="60s" selected>60 Seconds</option>
                        <option value="90s">90 Seconds</option>
                        <option value="120s">120 Seconds</option>
                    </select>
                </div>

                <div class="input-group">
                    <label for="scenes">Number of Scenes</label>
                    <select id="scenes">
                        <option value="3">3 Scenes</option>
                        <option value="4" selected>4 Scenes</option>
                        <option value="5">5 Scenes</option>
                        <option value="6">6 Scenes</option>
                    </select>
                </div>

                <div class="input-group">
                    <label for="style">Visual Style</label>
                    <select id="style">
                        <option value="3D Cute Pixar-like">3D Cute Pixar-like</option>
                        <option value="2D Flat Kids Animation">2D Flat Kids Animation</option>
                        <option value="Chibi Minimalist">Chibi Minimalist</option>
                    </select>
                </div>

                <div class="input-group">
                    <label for="age">Target Age</label>
                    <select id="age">
                        <option value="3-5">3–5 Years</option>
                        <option value="5-7" selected>5–7 Years</option>
                        <option value="7-9">7–9 Years</option>
                    </select>
                </div>

                <div class="input-group">
                    <label for="intensity">Prompt Intensity</label>
                    <select id="intensity">
                        <option value="Simple">Simple</option>
                        <option value="Detailed" selected>Detailed</option>
                        <option value="Cinematic">Cinematic</option>
                    </select>
                </div>
            </div>

            <div class="advanced-controls">
                <div class="input-group">
                    <label>Narration Style</label>
                    <select id="narration">
                        <option value="Cheerful child voice">Cheerful child voice</option>
                        <option value="Calm storyteller">Calm storyteller</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>Music Mood</label>
                    <select id="music">
                        <option value="Happy">Happy</option>
                        <option value="Warm">Warm</option>
                        <option value="Calm">Calm</option>
                    </select>
                </div>
            </div>

            <div class="btn-group-main">
                <button id="generateBtn" class="btn-primary">GENERATE PROMPT</button>
                <div class="preset-controls">
                    <button id="savePreset" class="btn-text">Save Preset</button>
                    <button id="loadPreset" class="btn-text">Load Preset</button>
                </div>
            </div>
        </section>

        <section id="results" class="results-area hidden">
            <div class="results-header">
                <h2>Generated Scenarios</h2>
                <div class="export-buttons">
                    <button id="copyAll" class="btn-secondary">Copy All</button>
                    <button id="exportTxt" class="btn-secondary">TXT</button>
                    <button id="exportJson" class="btn-secondary">JSON</button>
                </div>
            </div>
            <div id="sceneContainer" class="scene-grid">
                <!-- Scenes appear here -->
            </div>
        </section>

        <div id="loading" class="loading-overlay hidden">
            <div class="spinner"></div>
            <p>Creating magic prompts...</p>
        </div>
    </main>

    <div id="toast" class="toast hidden">Copied to clipboard!</div>

    <script src="script.js"></script>
</body>
</html>
