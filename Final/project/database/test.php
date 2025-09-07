<?php
// Quick test to verify database connection and setup
echo "<h2>🔧 CrowdFund Database Test</h2>\n";

try {
    // Test database connection
    echo "<p>1. Testing database connection...</p>\n";
    require_once 'database.php';
    echo "<p style='color:green;'>✅ Database connection successful!</p>\n";
    
    // Test if tables exist
    echo "<p>2. Checking database tables...</p>\n";
    $tables = ['users', 'funds', 'categories', 'donations', 'comments'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<span style='color:green;'>✅ Table '$table' exists</span><br>\n";
        } else {
            echo "<span style='color:red;'>❌ Table '$table' missing</span><br>\n";
        }
    }
    echo "<br>\n";
    
    // Test if we have data
    echo "<p>3. Checking for sample data...</p>\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $adminCount = $stmt->fetchColumn();
    echo "Admin users: $adminCount<br>\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'fundraiser'");
    $fundraiserCount = $stmt->fetchColumn();
    echo "Fundraiser users: $fundraiserCount<br>\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'backer'");
    $backerCount = $stmt->fetchColumn();
    echo "Backer users: $backerCount<br>\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM funds");
    $fundCount = $stmt->fetchColumn();
    echo "Funds: $fundCount<br>\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $categoryCount = $stmt->fetchColumn();
    echo "Categories: $categoryCount<br><br>\n";
    
    if ($fundCount > 0) {
        echo "<p>4. Testing function includes...</p>\n";
        require_once '../includes/functions.php';
        $fundManager = new FundManager();
        $funds = $fundManager->getFeaturedFunds(3);
        echo "<p style='color:green;'>✅ Functions loaded successfully!</p>\n";
        echo "<p style='color:green;'>✅ Featured funds retrieved: " . count($funds) . " funds</p>\n";
        
        if (!empty($funds)) {
            echo "<p>5. Sample fund data:</p>\n";
            echo "<div style='background:white;padding:15px;border-radius:5px;margin:10px 0;'>\n";
            foreach ($funds as $i => $fund) {
                echo "<strong>Fund " . ($i + 1) . ":</strong> " . htmlspecialchars($fund['title']) . "<br>\n";
                echo "&nbsp;&nbsp;- Goal: $" . number_format($fund['goal_amount']) . "<br>\n";
                echo "&nbsp;&nbsp;- Raised: $" . number_format($fund['current_amount']) . "<br>\n";
                echo "&nbsp;&nbsp;- Backers: " . $fund['backer_count'] . "<br><br>\n";
            }
            echo "</div>\n";
        }
        
        echo "<div style='background:#d4edda;padding:20px;border-radius:5px;margin:20px 0;'>\n";
        echo "<h3 style='color:green;'>🎉 Everything is working correctly!</h3>\n";
        echo "<a href='../home/view/index.php' style='background:#28a745;color:white;padding:15px 25px;text-decoration:none;border-radius:5px;'>Visit Homepage</a>\n";
        echo "</div>\n";
    } else {
        echo "<div style='background:#fff3cd;padding:15px;border-radius:5px;margin:10px 0;'>\n";
        echo "<strong>⚠️ No funds found.</strong> Please run generate_dummy_data.php first.<br>\n";
        echo "<a href='generate_dummy_data.php' style='background:#ffc107;color:black;padding:10px 15px;text-decoration:none;border-radius:3px;'>Generate Sample Data</a>\n";
        echo "</div>\n";
    }
    
} catch (Exception $e) {
    echo "<div style='background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;'>\n";
    echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>\n";
    
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "<strong>🔧 Solution:</strong> Run setup.php first to create the database<br>\n";
        echo "<a href='setup.php' style='background:#007bff;color:white;padding:10px 15px;text-decoration:none;border-radius:3px;'>Setup Database</a>\n";
    } elseif (strpos($e->getMessage(), 'Connection refused') !== false || strpos($e->getMessage(), 'Connection failed') !== false) {
        echo "<strong>🔧 Solution:</strong> Make sure XAMPP MySQL service is running<br>\n";
    } else {
        echo "<strong>🔧 Solution:</strong> Check your database configuration in config/database.php<br>\n";
    }
    echo "</div>\n";
}
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f8f9fa; }
h2 { color: #333; margin-bottom: 20px; }
p { margin: 10px 0; }
a { display: inline-block; margin: 5px; }
div { margin: 10px 0; }
</style>
