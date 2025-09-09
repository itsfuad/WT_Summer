<?php
echo "<h2>🔧 Database Table Creation</h2>\n";
echo "<p>Creating database and table structure for CrowdFund Platform...</p>\n";

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'crowdfund_db';

try {
    // First, connect without selecting a database to create it
    echo "<p>1. Connecting to MySQL server...</p>\n";
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    echo "<p>2. Creating database 'crowdfund_db'...</p>\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database");
    $pdo->exec("USE $database");
    echo "<span style='color:green;'>✓ Database created/connected</span><br>\n";
    
    // Drop existing tables if they exist (in correct order due to foreign keys)
    echo "<p>3. Cleaning up existing tables...</p>\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $dropTables = [
        'password_reset_tokens', 'fund_likes', 'reports', 'comments',
        'donations', 'funds', 'categories', 'users'
    ];
    
    foreach ($dropTables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
        echo "<span style='color:orange;'>• Dropped table '$table' if it existed</span><br>\n";
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Create tables
    echo "<p>4. Creating tables...</p>\n";
    
    // Users table
    echo "<p>Creating users table...</p>\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'fundraiser', 'backer') NOT NULL,
            profile_image VARCHAR(255) DEFAULT NULL,
            bio TEXT DEFAULT NULL,
            phone VARCHAR(20) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            status ENUM('active', 'suspended', 'pending') DEFAULT 'active',
            email_verified BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "<span style='color:green;'>✓ Users table created</span><br>\n";
    
    // Categories table
    echo "<p>Creating categories table...</p>\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            icon VARCHAR(50) DEFAULT 'fas fa-folder',
            color VARCHAR(20) DEFAULT '#007bff',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<span style='color:green;'>✓ Categories table created</span><br>\n";
    
    // Funds table
    echo "<p>Creating funds table...</p>\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS funds (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            description TEXT NOT NULL,
            short_description VARCHAR(500),
            goal_amount DECIMAL(10,2) NOT NULL,
            current_amount DECIMAL(10,2) DEFAULT 0.00,
            fundraiser_id INT NOT NULL,
            category_id INT DEFAULT NULL,
            image_url VARCHAR(255) DEFAULT NULL,
            status ENUM('active', 'paused', 'completed', 'cancelled', 'removed', 'frozen') DEFAULT 'active',
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            featured BOOLEAN DEFAULT FALSE,
            views_count INT DEFAULT 0,
            likes_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (fundraiser_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
            INDEX idx_status (status),
            INDEX idx_featured (featured),
            INDEX idx_end_date (end_date)
        )
    ");
    echo "<span style='color:green;'>✓ Funds table created</span><br>\n";
    
    // Donations table
    echo "<p>Creating donations table...</p>\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS donations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fund_id INT NOT NULL,
            backer_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method VARCHAR(50) DEFAULT 'card',
            payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
            transaction_id VARCHAR(100) UNIQUE,
            comment TEXT,
            anonymous BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (fund_id) REFERENCES funds(id) ON DELETE CASCADE,
            FOREIGN KEY (backer_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_fund_id (fund_id),
            INDEX idx_backer_id (backer_id),
            INDEX idx_payment_status (payment_status)
        )
    ");
    echo "<span style='color:green;'>✓ Donations table created</span><br>\n";
    
    // Comments table
    echo "<p>Creating comments table...</p>\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fund_id INT NOT NULL,
            user_id INT NOT NULL,
            comment TEXT NOT NULL,
            status ENUM('active', 'hidden', 'reported') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (fund_id) REFERENCES funds(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_fund_id (fund_id),
            INDEX idx_status (status)
        )
    ");
    echo "<span style='color:green;'>✓ Comments table created</span><br>\n";
    
    // Reports table
    echo "<p>Creating reports table...</p>\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fund_id INT DEFAULT NULL,
            comment_id INT DEFAULT NULL,
            reported_by INT NOT NULL,
            reason VARCHAR(100) NOT NULL,
            description TEXT,
            status ENUM('pending', 'reviewed', 'resolved', 'dismissed') DEFAULT 'pending',
            admin_notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (fund_id) REFERENCES funds(id) ON DELETE CASCADE,
            FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
            FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_status (status)
        )
    ");
    echo "<span style='color:green;'>✓ Reports table created</span><br>\n";
    
    // Fund likes table
    echo "<p>Creating fund_likes table...</p>\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS fund_likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fund_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (fund_id) REFERENCES funds(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY uniq_fund_user (fund_id, user_id),
            INDEX idx_fund_id (fund_id),
            INDEX idx_user_id (user_id)
        )
    ");
    echo "<span style='color:green;'>✓ Fund likes table created</span><br>\n";
    
    // Password reset tokens table
    echo "<p>Creating password_reset_tokens table...</p>\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) NOT NULL,
            token VARCHAR(6) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            used TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_token (token),
            INDEX idx_expires (expires_at)
        )
    ");
    echo "<span style='color:green;'>✓ Password reset tokens table created</span><br>\n";
    
    
    echo "<div style='background:#d4edda;padding:20px;border-radius:5px;margin:20px 0;'>\n";
    echo "<h3>✅ Database Structure Created Successfully!</h3>\n";
    echo "<p>Database and tables are now ready. Run the data generator to add sample data and admin account.</p>\n";
    echo "</div>\n";
    
    echo "<div style='margin:20px 0;'>\n";
    echo "<a href='generate_dummy_data.php' style='background:#28a745;color:white;padding:15px 25px;text-decoration:none;border-radius:5px;margin-right:10px;'>🎲 Generate Sample Data & Admin</a>\n";
    echo "<a href='test.php' style='background:#6c757d;color:white;padding:15px 25px;text-decoration:none;border-radius:5px;'>🔧 Test Database</a>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div style='background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;'>\n";
    echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>\n";
    echo "<strong>🔧 Solution:</strong><br>\n";
    echo "1. Make sure the database 'crowdfund_db' exists<br>\n";
    echo "3. Check MySQL service is running<br>\n";
    echo "</div>\n";
}
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f8f9fa; }
h2, h3 { color: #333; }
p { margin: 10px 0; }
a { display: inline-block; margin: 5px; }
</style>
