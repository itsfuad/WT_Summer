<?php
// Database initialization script
require_once __DIR__ . '/../config/database.php';

// Create categories table and insert sample data
try {
    // Create categories table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            icon VARCHAR(50) DEFAULT 'fas fa-folder',
            color VARCHAR(20) DEFAULT '#667eea',
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Check if categories exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        // Insert sample categories
        $categories = [
            ['Technology', 'fas fa-laptop-code', '#667eea', 'Tech innovations and startups'],
            ['Health & Medical', 'fas fa-heartbeat', '#e53e3e', 'Medical treatments and health initiatives'],
            ['Education', 'fas fa-graduation-cap', '#38b2ac', 'Educational projects and scholarships'],
            ['Arts & Culture', 'fas fa-palette', '#ed8936', 'Creative arts and cultural projects'],
            ['Community', 'fas fa-users', '#48bb78', 'Local community development'],
            ['Environment', 'fas fa-leaf', '#38a169', 'Environmental and sustainability projects'],
            ['Sports', 'fas fa-running', '#3182ce', 'Sports teams and athletic programs'],
            ['Business', 'fas fa-briefcase', '#805ad5', 'Business ventures and entrepreneurship'],
            ['Emergency', 'fas fa-exclamation-triangle', '#f56565', 'Emergency and disaster relief'],
            ['Travel', 'fas fa-plane', '#319795', 'Travel and adventure projects']
        ];

        $stmt = $pdo->prepare("
            INSERT INTO categories (name, icon, color, description) 
            VALUES (?, ?, ?, ?)
        ");

        foreach ($categories as $category) {
            $stmt->execute($category);
        }

        echo "Categories table created and populated with sample data.\n";
    } else {
        echo "Categories already exist. Skipping initialization.\n";
    }

    // Create funds table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS funds (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            short_description VARCHAR(255),
            description TEXT NOT NULL,
            goal_amount DECIMAL(10,2) NOT NULL,
            current_amount DECIMAL(10,2) DEFAULT 0.00,
            category_id INT,
            fundraiser_id INT NOT NULL,
            end_date DATE NOT NULL,
            featured BOOLEAN DEFAULT 0,
            status ENUM('active', 'completed', 'paused', 'cancelled') DEFAULT 'active',
            views_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id),
            FOREIGN KEY (fundraiser_id) REFERENCES users(id)
        )
    ");

    // Create donations table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS donations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fund_id INT NOT NULL,
            backer_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
            payment_method VARCHAR(50),
            transaction_id VARCHAR(100),
            message TEXT,
            anonymous BOOLEAN DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (fund_id) REFERENCES funds(id),
            FOREIGN KEY (backer_id) REFERENCES users(id)
        )
    ");

    echo "Database tables created successfully!\n";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
