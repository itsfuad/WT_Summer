<?php
require_once '../config/database.php';

echo "<h2>🎲 Generating Sample Data for CrowdFund Platform</h2>\n";
echo "<p>Creating admin account, sample users, funds, likes, donations, comments, and updates...</p>\n";

try {
    // Check if tables exist
    echo "<p>1. Checking database structure...</p>\n";
    $required_tables = ['users', 'categories', 'funds', 'donations', 'comments', 'fund_likes'];
    foreach ($required_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if (!$stmt->fetch()) {
            throw new Exception("Table '$table' does not exist. Please run create_tables.php first.");
        }
    }
    echo "<span style='color:green;'>✓ All required tables exist</span><br>\n";
    // Sample data arrays
    $fundraiser_names = [
        'Tech Innovators LLC', 'Green Future Co.', 'HealthCare Heroes', 'EcoSolutions Inc.',
        'Creative Collective', 'EduTech Foundation', 'Digital Dreams', 'Smart Solutions',
        'Future Vision', 'Innovation Labs', 'NextGen Ventures', 'Bright Ideas Co.',
        'Progressive Tech', 'Modern Solutions', 'Visionary Projects'
    ];

    $backer_names = [
        'John Smith', 'Emily Johnson', 'Michael Brown', 'Sarah Davis', 'David Wilson',
        'Lisa Anderson', 'Robert Taylor', 'Jennifer Martinez', 'William Garcia', 'Jessica Rodriguez',
        'James Wilson', 'Ashley Thompson', 'Christopher Lee', 'Amanda White', 'Matthew Harris',
        'Stephanie Clark', 'Joshua Lewis', 'Michelle Robinson', 'Andrew Walker', 'Nicole Young'
    ];

    $fund_titles = [
        'AI-Powered Educational Platform',
        'Sustainable Urban Farming Initiative',
        'Community Health Mobile Units',
        'Renewable Energy for Rural Areas',
        'Digital Art Gallery Platform',
        'Open Source Learning Tools',
        'Clean Water Technology',
        'Mental Health Support App',
        'Affordable Housing Project',
        'Food Security Program',
        'Youth Sports Development',
        'Senior Care Innovation',
        'Disaster Relief Technology',
        'Wildlife Conservation Effort',
        'Local Business Recovery Fund',
        'Scholarship Program Initiative',
        'Public Library Modernization',
        'Transportation Accessibility',
        'Community Garden Network',
        'Tech Training for Veterans'
    ];

    $fund_descriptions = [
        'Building an AI-powered educational platform to revolutionize online learning with personalized content and adaptive learning algorithms that adjust to each student\'s pace and learning style.',
        'Creating vertical farming solutions for urban environments to promote sustainable food production, reduce carbon footprint, and provide fresh produce to local communities year-round.',
        'Providing free health screenings and medical services to underserved communities with mobile health units equipped with modern medical equipment and staffed by qualified healthcare professionals.',
        'Installing solar panels and wind turbines in rural communities to provide clean, sustainable energy and reduce dependency on fossil fuels while creating local jobs.',
        'Creating an online platform for emerging digital artists to showcase and sell their work with fair compensation, artist support tools, and community features.',
        'Developing free, open-source educational tools and resources for students and teachers worldwide, making quality education accessible to everyone regardless of economic status.',
        'Developing advanced water purification systems for communities without access to clean drinking water, using innovative filtration technology and sustainable practices.',
        'Creating a comprehensive mental health support application with AI-powered therapy sessions, peer support groups, and professional counseling services.',
        'Building affordable housing units using sustainable materials and innovative construction techniques to address the housing crisis in urban areas.',
        'Establishing food banks and distribution networks to combat hunger in low-income communities while supporting local farmers and reducing food waste.',
        'Developing youth sports programs in underserved areas to promote physical health, teamwork, and leadership skills while providing safe recreational activities.',
        'Innovating senior care solutions including smart home technology, health monitoring systems, and community engagement programs for elderly populations.',
        'Creating emergency response technology and disaster preparedness systems to help communities better respond to natural disasters and emergency situations.',
        'Supporting wildlife conservation efforts through habitat restoration, anti-poaching initiatives, and community education programs to protect endangered species.',
        'Providing financial assistance and resources to help local small businesses recover from economic challenges and adapt to changing market conditions.',
        'Establishing scholarship programs for underprivileged students to access higher education and vocational training opportunities in high-demand fields.',
        'Modernizing public libraries with new technology, expanded digital resources, and community programming to serve as 21st-century learning hubs.',
        'Improving public transportation accessibility for disabled individuals through vehicle modifications, infrastructure improvements, and assistive technologies.',
        'Creating a network of community gardens to promote local food production, environmental education, and neighborhood engagement in urban areas.',
        'Providing technology training and career development programs specifically designed for military veterans transitioning to civilian careers in the tech industry.'
    ];

    $icons = [
        'fas fa-robot', 'fas fa-seedling', 'fas fa-heart', 'fas fa-solar-panel',
        'fas fa-palette', 'fas fa-graduation-cap', 'fas fa-tint', 'fas fa-brain',
        'fas fa-home', 'fas fa-utensils', 'fas fa-running', 'fas fa-user-clock',
        'fas fa-shield-alt', 'fas fa-paw', 'fas fa-store', 'fas fa-award',
        'fas fa-book', 'fas fa-bus', 'fas fa-leaf', 'fas fa-laptop-code'
    ];

    // Clear existing data and create fresh admin
    echo "<p>2. Clearing existing data...</p>\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DELETE FROM donations WHERE id > 0");
    $pdo->exec("DELETE FROM comments WHERE id > 0");
    $pdo->exec("DELETE FROM fund_likes WHERE id > 0");
    $pdo->exec("DELETE FROM funds WHERE id > 0");
    $pdo->exec("DELETE FROM users WHERE id > 0");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "<span style='color:orange;'>✓ Cleared existing data</span><br>\n";
    
    // Create fresh admin account
    echo "<p>3. Creating admin account...</p>\n";
    $adminPassword = 'admin123';
    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status, email_verified) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Admin', 'admin@crowdfund.com', $hashedPassword, 'admin', 'active', 1]);
    echo "<span style='color:green;'>✓ Admin account created (Email: admin@crowdfund.com, Password: $adminPassword)</span><br>\n";

    // Create categories if they don't exist
    echo "<p>📂 Creating/checking categories...</p>\n";
    $category_data = [
        ['Technology', 'fas fa-laptop-code', '#3498db'],
        ['Environment', 'fas fa-seedling', '#27ae60'],
        ['Health', 'fas fa-heart', '#e74c3c'],
        ['Education', 'fas fa-graduation-cap', '#f39c12'],
        ['Arts & Culture', 'fas fa-palette', '#9b59b6'],
        ['Community', 'fas fa-users', '#1abc9c'],
        ['Sports', 'fas fa-running', '#e67e22'],
        ['Animals', 'fas fa-paw', '#95a5a6'],
        ['Emergency', 'fas fa-shield-alt', '#c0392b'],
        ['Innovation', 'fas fa-lightbulb', '#f1c40f']
    ];

    foreach ($category_data as $cat) {
        // Check if category already exists
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt->execute([$cat[0]]);
        if (!$stmt->fetch()) {
            // Category doesn't exist, create it
            $stmt = $pdo->prepare("INSERT INTO categories (name, icon, color) VALUES (?, ?, ?)");
            $stmt->execute([$cat[0], $cat[1], $cat[2]]);
            echo "<span style='color:green;'>✓ Created category: {$cat[0]}</span><br>\n";
        } else {
            echo "<span style='color:blue;'>ℹ Category already exists: {$cat[0]}</span><br>\n";
        }
    }

    // Get category IDs
    $categories = $pdo->query("SELECT id FROM categories")->fetchAll(PDO::FETCH_COLUMN);

    // Insert fundraisers
    echo "<p>4. Creating fundraiser accounts...</p>\n";
    $fundraiser_ids = [];
    for ($i = 0; $i < 15; $i++) {
        $name = $fundraiser_names[$i];
        $email = strtolower(str_replace([' ', '.', ','], ['', '', ''], $name)) . '@fundraiser.com';
        $password = password_hash('password123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, email_verified, bio) VALUES (?, ?, ?, 'fundraiser', 1, ?)");
        $bio = "Passionate about making a positive impact in the world through innovative projects and community engagement.";
        $stmt->execute([$name, $email, $password, $bio]);
        $fundraiser_ids[] = $pdo->lastInsertId();
    }

    // Insert backers
    echo "<p>5. Creating backer accounts...</p>\n";
    $backer_ids = [];
    for ($i = 0; $i < 20; $i++) {
        $name = $backer_names[$i];
        $email = strtolower(str_replace(' ', '.', $name)) . '@backer.com';
        $password = password_hash('password123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, email_verified, bio) VALUES (?, ?, ?, 'backer', 1, ?)");
        $bio = "Supporting amazing projects and helping bring innovative ideas to life.";
        $stmt->execute([$name, $email, $password, $bio]);
        $backer_ids[] = $pdo->lastInsertId();
    }

    // Insert funds
    echo "<p>6. Creating funds/campaigns...</p>\n";
    $fund_ids = [];
    for ($i = 0; $i < 20; $i++) {
        $title = $fund_titles[$i];
        $description = $fund_descriptions[$i];
        $short_description = substr($description, 0, 150) . '...';
        $goal_amount = rand(10000, 100000);
        $fundraiser_id = $fundraiser_ids[array_rand($fundraiser_ids)];
        $category_id = $categories[array_rand($categories)];
        $icon = $icons[$i];
        
        // Random start and end dates
        $start_date = date('Y-m-d', strtotime('-' . rand(1, 30) . ' days'));
        $end_date = date('Y-m-d', strtotime('+' . rand(10, 90) . ' days'));
        
        // Some campaigns are featured
        $featured = rand(1, 5) == 1 ? 1 : 0;
        
        // Random views
        $views = rand(50, 1000);
        
        $stmt = $pdo->prepare("
            INSERT INTO funds (title, description, short_description, goal_amount, fundraiser_id, category_id, 
                             start_date, end_date, featured, views_count, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'));
        $stmt->execute([
            $title, $description, $short_description, $goal_amount, $fundraiser_id, $category_id,
            $start_date, $end_date, $featured, $views, $created_at
        ]);
        
        $fund_ids[] = $pdo->lastInsertId();
    }

    // Seed likes for each fund
    echo "<p>7. Creating likes...</p>\n";
    foreach ($fund_ids as $fund_id) {
        // Each fund gets random number of unique likes
        $like_count = rand(3, 25);
        $liked_users = [];
        for ($i = 0; $i < $like_count; $i++) {
            $user_id = $backer_ids[array_rand($backer_ids)];
            if (in_array($user_id, $liked_users)) { continue; }
            $liked_users[] = $user_id;
            $stmt = $pdo->prepare("INSERT IGNORE INTO fund_likes (fund_id, user_id, created_at) VALUES (?, ?, ?)");
            $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 25) . ' days'));
            $stmt->execute([$fund_id, $user_id, $created_at]);
        }
        // Sync likes_count on funds
        $stmt = $pdo->prepare("UPDATE funds f SET likes_count = (SELECT COUNT(*) FROM fund_likes fl WHERE fl.fund_id = f.id) WHERE f.id = ?");
        $stmt->execute([$fund_id]);
    }

    // Insert donations
    echo "<p>8. Creating donations...</p>\n";
    foreach ($fund_ids as $fund_id) {
        // Each fund gets random number of donations
        $donation_count = rand(5, 25);
        $total_raised = 0;
        
        for ($j = 0; $j < $donation_count; $j++) {
            $backer_id = $backer_ids[array_rand($backer_ids)];
            $amount = rand(25, 1000);
            $anonymous = rand(1, 10) <= 2 ? 1 : 0; // 20% anonymous
            $payment_status = rand(1, 20) == 1 ? 'failed' : 'completed'; // 5% failed
            
            $comments = [
                'Great project! Happy to support this initiative.',
                'Looking forward to seeing this come to life!',
                'Amazing work, keep it up!',
                'This is exactly what we need in our community.',
                'Proud to be a part of this project.',
                'Wishing you all the best with this campaign.',
                'Can\'t wait to see the results!',
                'This will make a real difference.',
                'Fantastic idea, well executed!',
                'Supporting innovation and positive change.'
            ];
            
            $comment = rand(1, 3) == 1 ? $comments[array_rand($comments)] : null;
            
            $stmt = $pdo->prepare("
                INSERT INTO donations (fund_id, backer_id, amount, payment_status, comment, anonymous, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 25) . ' days'));
            $stmt->execute([$fund_id, $backer_id, $amount, $payment_status, $comment, $anonymous, $created_at]);
            
            if ($payment_status == 'completed') {
                $total_raised += $amount;
            }
        }
        
        // Update fund's current amount
        $stmt = $pdo->prepare("UPDATE funds SET current_amount = ? WHERE id = ?");
        $stmt->execute([$total_raised, $fund_id]);
    }

    // Insert comments
    echo "<p>9. Creating comments...</p>\n";
    foreach ($fund_ids as $fund_id) {
        $comment_count = rand(3, 15);
        
        for ($j = 0; $j < $comment_count; $j++) {
            // Mix of fundraisers and backers commenting
            $all_user_ids = array_merge($fundraiser_ids, $backer_ids);
            $user_id = $all_user_ids[array_rand($all_user_ids)];
            
            $comment_texts = [
                'This is such an inspiring project! I love the vision and the potential impact it could have.',
                'Have you considered partnering with local organizations? It might help amplify your reach.',
                'The progress so far is amazing. Keep up the excellent work!',
                'I\'d love to see more updates on how the funds are being utilized.',
                'This project addresses a real need in our community. Thank you for your dedication.',
                'The team behind this seems very passionate and knowledgeable.',
                'I\'m curious about the timeline for implementation. Any updates?',
                'What measures are in place to ensure sustainability?',
                'This could be a game-changer if executed properly.',
                'I appreciate the transparency in your campaign description.',
                'How can supporters get more involved beyond just donating?',
                'The potential social impact of this project is incredible.',
                'I\'ve shared this with my network. Hope it helps with visibility!',
                'Looking forward to regular updates on the progress.',
                'This aligns perfectly with values I care about.',
                'The technical approach seems well thought out.',
                'I hope this gets the funding it deserves.',
                'Great job on explaining the project clearly.',
                'This is exactly the kind of innovation we need.',
                'Wishing you success in reaching your funding goal!'
            ];
            
            $comment = $comment_texts[array_rand($comment_texts)];
            
            $stmt = $pdo->prepare("\n                INSERT INTO comments (fund_id, user_id, comment, created_at) \n                VALUES (?, ?, ?, ?)\n            ");
            
            $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 20) . ' days'));
            $stmt->execute([$fund_id, $user_id, $comment, $created_at]);
        }
    }

    // Add some fund updates
    echo "<p>10. Creating fund updates...</p>\n";
    foreach ($fund_ids as $fund_id) {
        if (rand(1, 3) == 1) { // 33% of funds have updates
            $update_count = rand(1, 3);
            
            for ($j = 0; $j < $update_count; $j++) {
                $update_titles = [
                    'Project Milestone Reached!',
                    'Thank You to Our Amazing Supporters',
                    'New Partnership Announcement',
                    'Progress Update and Next Steps',
                    'Community Feedback Integration',
                    'Technical Development Update',
                    'Funding Goal Achievement',
                    'Team Expansion Update'
                ];
                
                $update_contents = [
                    'We\'re excited to share that we\'ve reached a major milestone in our project development. Thanks to your continued support, we\'re making excellent progress.',
                    'We want to express our heartfelt gratitude to all our supporters who have made this project possible. Your contributions are making a real difference.',
                    'We\'re thrilled to announce a new partnership that will help us expand our reach and impact. This collaboration brings additional expertise and resources to our project.',
                    'Here\'s a detailed update on our progress so far and what we plan to accomplish in the coming weeks. Your feedback has been invaluable.',
                    'Based on community feedback, we\'ve made several improvements to our approach. We\'re committed to ensuring this project meets your expectations.',
                    'Our technical team has been working hard on the development phase. Here are some insights into the challenges we\'ve overcome and our current status.',
                    'Thanks to your incredible support, we\'ve reached our initial funding goal! This enables us to move forward with confidence.',
                    'We\'re excited to welcome new team members who bring specialized skills to help us deliver on our promises to supporters.'
                ];
                
                $title = $update_titles[array_rand($update_titles)];
                $content = $update_contents[array_rand($update_contents)];
                
                $stmt = $pdo->prepare("
                    INSERT INTO fund_updates (fund_id, title, content, created_at) 
                    VALUES (?, ?, ?, ?)
                ");
                
                $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 15) . ' days'));
                $stmt->execute([$fund_id, $title, $content, $created_at]);
            }
        }
    }

    echo "<div style='background:#d4edda;padding:20px;border-radius:5px;margin:20px 0;'>\n";
    echo "<h3>✅ Dummy data generation completed!</h3>\n";
    echo "<strong>Generated:</strong><br>\n";
    echo "- 15 Fundraiser accounts<br>\n";
    echo "- 20 Backer accounts<br>\n";
    echo "- 20 Fund campaigns<br>\n";
    echo "- Likes, donations, comments, and updates<br>\n";
    echo "</div>\n";
    
    echo "<div style='background:#e7f3ff;padding:15px;border-radius:5px;margin:10px 0;'>\n";
    echo "<strong>Login credentials:</strong><br>\n";
    echo "<strong>Admin:</strong> admin@crowdfund.com / admin123<br>\n";
    echo "</div>\n";

    echo "<div style='margin:20px 0;'>\n";
    echo "<a href='../home/view/index.php' style='background:#28a745;color:white;padding:15px 25px;text-decoration:none;border-radius:5px;margin-right:10px;'>🎉 View Homepage</a>\n";
    echo "<a href='test.php' style='background:#6c757d;color:white;padding:15px 25px;text-decoration:none;border-radius:5px;'>🔧 Test Database</a>\n";
    echo "</div>\n";

} catch (Exception $e) {
    echo "<div style='background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;'>\n";
    echo "<strong>❌ Error:</strong> " . $e->getMessage() . "<br><br>\n";
    echo "<strong>Solution:</strong> Make sure to run create_tables.php first to create the database structure.<br>\n";
    echo "<a href='create_tables.php' style='background:#007bff;color:white;padding:10px 15px;text-decoration:none;border-radius:3px;'>Run Database Setup</a>\n";
    echo "</div>\n";
}
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f8f9fa; }
h2, h3 { color: #333; }
p { margin: 10px 0; }
a { display: inline-block; margin: 5px; }
</style>