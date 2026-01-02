<?php
/**
 * Admin Panel for Veo 3 Prompt Generator
 * Security: Hardcoded Password, Session-based
 */
session_start();

$config_file = 'config.json';
$password = 'admin_veo_2025'; // Default Password

// Authentication
if (isset($_POST['login'])) {
    if ($_POST['password'] === $password) {
        $_SESSION['admin_auth'] = true;
    } else {
        $error = "Incorrect password!";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// Check auth for actions
$is_auth = $_SESSION['admin_auth'] ?? false;

// Save Config
if ($is_auth && isset($_POST['save_config'])) {
    $new_config = [
        'gemini_key' => $_POST['gemini_key'] ?? ''
    ];
    file_put_contents($config_file, json_encode($new_config, JSON_PRETTY_PRINT));
    $success = "Configuration updated!";
}

// Load current config
$current_config = ['gemini_key' => ''];
if (file_exists($config_file)) {
    $current_config = json_decode(file_get_contents($config_file), true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - KidGen Veo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <?php if (!$is_auth): ?>
            <div class="admin-card login-card">
                <h1>Admin Login</h1>
                <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
                <form method="POST">
                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" name="password" required autofocus>
                    </div>
                    <button type="submit" name="login" class="btn-primary">Login</button>
                    <p style="margin-top: 20px; font-size: 0.8rem; color: #666;">Default: admin_veo_2025</p>
                </form>
                <a href="index.php" class="btn-text">← Back to App</a>
            </div>
        <?php else: ?>
            <div class="admin-card">
                <div class="admin-header">
                    <h1>Configuration</h1>
                    <a href="?logout=1" class="btn-text">Logout</a>
                </div>
                
                <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
                
                <form method="POST">
                    <div class="input-group">
                        <label>Gemini API Key</label>
                        <input type="text" name="gemini_key" value="<?php echo htmlspecialchars($current_config['gemini_key']); ?>" placeholder="Enter API Key" required>
                    </div>
                    <div class="admin-actions">
                        <button type="submit" name="save_config" class="btn-primary">Save Settings</button>
                        <a href="index.php" class="btn-secondary">Go to Generator</a>
                    </div>
                </form>

                <div class="info-box">
                    <h3>Help</h3>
                    <p>1. Get your API Key from <a href="https://aistudio.google.com/" target="_blank">Google AI Studio</a>.</p>
                    <p>2. Ensure the key has access to <code>gemini-2.5-flash-preview-09-2025</code>.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
