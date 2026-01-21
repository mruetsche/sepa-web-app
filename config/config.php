<?php
// Database configuration
define('DB_FILE', __DIR__ . '/../database/sepa_manager.db');

// Application settings
define('APP_NAME', 'SEPA Überweisungsmanager');
define('APP_VERSION', '1.1.0');

// Dynamically detect base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = str_replace('/ajax', '', $scriptPath);
$basePath = str_replace('/pages', '', $basePath);
$basePath = str_replace('/config', '', $basePath);
$basePath = str_replace('/includes', '', $basePath);
$basePath = rtrim($basePath, '/');

define('BASE_URL', $protocol . $host . $basePath);
define('AJAX_URL', BASE_URL . '/ajax');

// Rest of the original config file...
// Security settings
define('HASH_ALGO', PASSWORD_BCRYPT);
define('SESSION_LIFETIME', 3600); // 1 hour

// PDF settings
define('PDF_FONT', 'helvetica');
define('PDF_FONT_SIZE', 10);

// Ensure database directory exists
$dbDir = dirname(DB_FILE);
if (!file_exists($dbDir)) {
    mkdir($dbDir, 0777, true);
}

// Initialize database connection
try {
    $db = new PDO('sqlite:' . DB_FILE);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Enable foreign keys
    $db->exec('PRAGMA foreign_keys = ON');
    
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Create tables if they don't exist
function initDatabase($db) {
    // Users table
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME
        )
    ");
    
    // Addresses table
    $db->exec("
        CREATE TABLE IF NOT EXISTS addresses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            company TEXT,
            street TEXT,
            zip TEXT,
            city TEXT,
            country TEXT DEFAULT 'DE',
            iban TEXT NOT NULL,
            bic TEXT NOT NULL,
            bank_name TEXT,
            is_favorite BOOLEAN DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Senders table (Absender/eigene Bankkonten)
    $db->exec("
        CREATE TABLE IF NOT EXISTS senders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            city TEXT,
            iban TEXT NOT NULL,
            bic TEXT NOT NULL,
            bank_name TEXT,
            is_default BOOLEAN DEFAULT 0,
            color TEXT DEFAULT '#004494',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Transfers table
    $db->exec("
        CREATE TABLE IF NOT EXISTS transfers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            sender_name TEXT NOT NULL,
            sender_iban TEXT NOT NULL,
            sender_bic TEXT NOT NULL,
            sender_bank TEXT,
            recipient_name TEXT NOT NULL,
            recipient_iban TEXT NOT NULL,
            recipient_bic TEXT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            purpose_line1 TEXT,
            purpose_line2 TEXT,
            reference_number TEXT,
            execution_date DATE,
            status TEXT DEFAULT 'draft',
            pdf_file TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Create indexes
    $db->exec("CREATE INDEX IF NOT EXISTS idx_transfers_user ON transfers(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_addresses_user ON addresses(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_senders_user ON senders(user_id)");
    
    // Create default admin user if no users exist
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        $defaultPassword = password_hash('admin123', PASSWORD_BCRYPT);
        $db->exec("
            INSERT INTO users (username, password, email) 
            VALUES ('admin', '$defaultPassword', 'admin@example.com')
        ");
    }
}

// Initialize database tables
initDatabase($db);

// Make database connection available globally
$GLOBALS['db'] = $db;
?>
