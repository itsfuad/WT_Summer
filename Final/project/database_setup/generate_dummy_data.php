<?php
require_once '../config/database.php';

echo "<h2>🎲 Generating Realistic Sample Data for CrowdFund Platform</h2>\n";
echo "<p>Creating admin, fundraisers, backers, campaigns, likes, donations, comments...</p>\n";

try {
    echo "<p>1. Checking database structure...</p>\n";
    $required_tables = ['users', 'categories', 'funds', 'donations', 'comments', 'fund_likes'];
    foreach ($required_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if (!$stmt->fetch()) {
            throw new Exception("Table '$table' does not exist. Please run create_db.php first.");
        }
    }
    echo "<span style='color:green;'>✓ All required tables exist</span><br>\n";

    // ---------- Bangladeshi names ----------
    $fundraiser_names = [
        'Rafiq Ahmed', 'Tanjim Karim', 'Shamima Akter', 'Rumana Begum',
        'Jahid Hasan', 'Farhana Hossain', 'Imran Khan', 'Nusrat Jahan',
        'Sabbir Rahman', 'Parvez Hossain', 'Mehnaz Chowdhury', 'Sadia Islam',
        'Tahsin Ahmed', 'Nabila Akhter', 'Raihanul Islam', 'Mahfuzur Rahman',
        'Shaila Sultana', 'Fahim Hasan', 'Rifat Chowdhury', 'Sohana Akter',
        'Tanvir Hossain', 'Afsana Parvin', 'Imtiaz Ahmed', 'Nusrat Sultana',
        'Rashedul Islam', 'Shirin Akter', 'Saiful Islam', 'Mousumi Rahman',
        'Arifur Rahman', 'Naznin Akter', 'Rakib Hossain', 'Moushumi Sultana',
        'Shakil Ahmed', 'Taslima Begum', 'Jannat Ara', 'Hasib Chowdhury',
        'Tahmid Rahman', 'Rumana Akhter', 'Noman Hossain', 'Farzana Rahman',
        'Rashed Khan', 'Salma Akter', 'Tanveer Ahmed', 'Mahamudul Hasan',
        'Nadia Chowdhury', 'Rabiul Islam', 'Sadia Khan', 'Firoz Ahmed', 'Parveen Akhter'
    ];

    $backer_names = [
        'Rakib Hossain', 'Moushumi Sultana', 'Arifur Rahman', 'Naznin Akter',
        'Shakil Ahmed', 'Taslima Begum', 'Fahim Hasan', 'Jannat Ara',
        'Rashedul Islam', 'Sabrina Khan', 'Hasib Chowdhury', 'Shirin Akter',
        'Tanvir Hossain', 'Afsana Parvin', 'Imtiaz Ahmed', 'Nusrat Sultana',
        'Rifat Hossain', 'Mahbuba Akter', 'Saiful Islam', 'Mousumi Rahman',
        'Rakib Ahmed', 'Tanjim Karim', 'Shamima Akter', 'Rumana Begum',
        'Jahid Hasan', 'Farhana Hossain', 'Imran Khan', 'Nusrat Jahan',
        'Sabbir Rahman', 'Parvez Hossain', 'Mehnaz Chowdhury', 'Sadia Islam',
        'Tahsin Ahmed', 'Nabila Akhter', 'Raihanul Islam', 'Mahfuzur Rahman',
        'Shaila Sultana', 'Fahim Hasan', 'Rifat Chowdhury', 'Sohana Akter',
        'Tanvir Hossain', 'Afsana Parvin', 'Imtiaz Ahmed', 'Nusrat Sultana',
        'Rashedul Islam', 'Shirin Akter', 'Saiful Islam', 'Mousumi Rahman',
        'Arifur Rahman', 'Naznin Akter', 'Rakib Hossain', 'Moushumi Sultana',
        'Shakil Ahmed', 'Taslima Begum', 'Jannat Ara', 'Hasib Chowdhury'
    ];

    $used_emails = [];
    function generateUniqueEmail($name, &$used_emails) {
        $name_clean = strtolower(str_replace(' ', '.', $name));
        $domains = ['gmail.com', 'outlook.com'];
        $domain = $domains[array_rand($domains)];
        $email = $name_clean . '@' . $domain;
        $counter = 1;
        while (in_array($email, $used_emails)) {
            $email = $name_clean . $counter . '@' . $domain;
            $counter++;
        }
        $used_emails[] = $email;
        return $email;
    }

    // ---------- Fund Categories ----------
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

    // ---------- Clear Existing Data ----------
    echo "<p>2. Clearing existing data...</p>\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $tables_to_clear = ['categories','comments','donations','funds','fund_likes','password_reset_tokens','reports','users'];
    foreach ($tables_to_clear as $t) {
        $pdo->exec("DELETE FROM $t WHERE id > 0");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "<span style='color:orange;'>✓ Cleared existing data</span><br>\n";

    // ---------- Create Admin ----------
    echo "<p>3. Creating admin account...</p>\n";
    $adminPassword = 'admin123';
    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role,status,email_verified) VALUES (?,?,?,?,?,?)");
    $stmt->execute(['Admin','admin@crowdfund.com',$hashedPassword,'admin','active',1]);
    echo "<span style='color:green;'>✓ Admin created: admin@crowdfund.com / $adminPassword</span><br>\n";

    // ---------- Create Categories ----------
    echo "<p>4. Creating categories...</p>\n";
    foreach($category_data as $cat) {
        $stmt = $pdo->prepare("INSERT INTO categories (name,icon,color) VALUES (?,?,?)");
        $stmt->execute([$cat[0],$cat[1],$cat[2]]);
    }
    $categories = $pdo->query("SELECT id FROM categories")->fetchAll(PDO::FETCH_COLUMN);

    // ---------- Create Fundraisers ----------
    echo "<p>5. Creating fundraiser accounts...</p>\n";
    $fundraiser_ids = [];
    foreach ($fundraiser_names as $name) {
        $email = generateUniqueEmail($name, $used_emails);
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $bio = "Passionate about making a positive impact through innovative projects.";
        $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role,email_verified,bio) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$name,$email,$password,'fundraiser',1,$bio]);
        $fundraiser_ids[] = $pdo->lastInsertId();
    }

    // ---------- Create Backers ----------
    echo "<p>6. Creating backer accounts...</p>\n";
    $backer_ids = [];
    foreach ($backer_names as $name) {
        $email = generateUniqueEmail($name, $used_emails);
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $bio = "Supporting amazing projects and helping innovative ideas come to life.";
        $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role,email_verified,bio) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$name,$email,$password,'backer',1,$bio]);
        $backer_ids[] = $pdo->lastInsertId();
    }

    // ---------- Fund Campaigns ----------
    echo "<p>7. Creating fund campaigns...</p>\n";
    $fund_ids = [];
    $fund_titles = [
        'Clean Water for Rural Villages','Solar Energy for Schools','Digital Learning Platform',
        'Community Health Clinic','Tree Plantation Drive','Women Empowerment Workshops',
        'Local Library Modernization','Youth Sports Program','Emergency Relief for Flood Victims',
        'Affordable Housing Initiative'
    ];
    $fund_descriptions = [
        'Providing access to clean drinking water to underprivileged communities across rural Bangladesh.',
        'Installing solar panels in schools to ensure uninterrupted learning and promote renewable energy awareness.',
        'Developing an online learning platform tailored for Bangladeshi students with local content and resources.',
        'Setting up community health clinics to offer affordable and accessible medical services.',
        'Organizing tree plantation drives to promote environmental sustainability in urban and rural areas.',
        'Hosting workshops for women to enhance skills, promote entrepreneurship, and empower local communities.',
        'Modernizing local libraries to provide digital resources and learning tools to students and residents.',
        'Launching youth sports programs to encourage physical fitness, teamwork, and leadership skills.',
        'Providing emergency relief to flood-affected families with food, clothing, and temporary shelter.',
        'Creating affordable housing solutions using sustainable building materials for low-income families.'
    ];
    $icons = ['fas fa-tint','fas fa-solar-panel','fas fa-laptop-code','fas fa-heart','fas fa-seedling','fas fa-female','fas fa-book','fas fa-running','fas fa-shield-alt','fas fa-home'];

    for($i=0;$i<50;$i++){
        $title = $fund_titles[array_rand($fund_titles)];
        $description = $fund_descriptions[array_rand($fund_descriptions)];
        $short_desc = substr($description,0,150).'...';
        $goal_amount = rand(20000,100000);
        $fundraiser_id = $fundraiser_ids[array_rand($fundraiser_ids)];
        $category_id = $categories[array_rand($categories)];
        $icon = $icons[array_rand($icons)];
        $start_date = date('Y-m-d', strtotime('-'.rand(0,365).' days'));
        $end_date = date('Y-m-d', strtotime($start_date.' + '.rand(30,180).' days'));
        $featured = rand(1,10)==1?1:0;
        $views = rand(50,5000);
        $created_at = date('Y-m-d H:i:s', strtotime('-'.rand(0,365).' days'));
        $stmt = $pdo->prepare("INSERT INTO funds (title,description,short_description,goal_amount,fundraiser_id,category_id,start_date,end_date,featured,views_count,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$title,$description,$short_desc,$goal_amount,$fundraiser_id,$category_id,$start_date,$end_date,$featured,$views,$created_at]);
        $fund_ids[] = $pdo->lastInsertId();
    }

    // ---------- Likes ----------
    echo "<p>8. Creating likes...</p>\n";
    foreach($fund_ids as $fund_id){
        $like_count = rand(5,50);
        $liked_users = [];
        for($i=0;$i<$like_count;$i++){
            $user_id = $backer_ids[array_rand($backer_ids)];
            if(in_array($user_id,$liked_users)) continue;
            $liked_users[] = $user_id;
            $stmt = $pdo->prepare("INSERT INTO fund_likes (fund_id,user_id,created_at) VALUES (?,?,?)");
            $created_at = date('Y-m-d H:i:s', strtotime('-'.rand(0,365).' days'));
            $stmt->execute([$fund_id,$user_id,$created_at]);
        }
        $stmt = $pdo->prepare("UPDATE funds f SET likes_count=(SELECT COUNT(*) FROM fund_likes fl WHERE fl.fund_id=f.id) WHERE f.id=?");
        $stmt->execute([$fund_id]);
    }

    // ---------- Donations ----------
    echo "<p>9. Creating donations...</p>\n";
    foreach($fund_ids as $fund_id){
        $donation_count = rand(10,50);
        $total_raised = 0;
        for($j=0;$j<$donation_count;$j++){
            $backer_id = $backer_ids[array_rand($backer_ids)];
            $amount = rand(50,2000);
            $anonymous = rand(1,10)<=2?1:0;
            $payment_status = rand(1,20)==1?'failed':'completed';
            $comments = ['Great initiative!','Excited to see progress','Wishing success','Amazing work!','Keep it up!'];
            $comment = rand(1,3)==1?$comments[array_rand($comments)]:null;
            $created_at = date('Y-m-d H:i:s', strtotime('-'.rand(0,365).' days'));
            $stmt = $pdo->prepare("INSERT INTO donations (fund_id,backer_id,amount,payment_status,comment,anonymous,created_at) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$fund_id,$backer_id,$amount,$payment_status,$comment,$anonymous,$created_at]);
            if($payment_status=='completed') $total_raised+=$amount;
        }
        $stmt = $pdo->prepare("UPDATE funds SET current_amount=? WHERE id=?");
        $stmt->execute([$total_raised,$fund_id]);
    }

    // ---------- Comments ----------
    echo "<p>10. Creating comments...</p>\n";
    $comment_texts = [
        'This is an inspiring project!','Looking forward to the updates','Amazing work, keep it up!',
        'This helps our community','Great initiative','Wishing all the best','Excited to support this'
    ];
    foreach($fund_ids as $fund_id){
        $comment_count = rand(5,20);
        $all_users = array_merge($fundraiser_ids,$backer_ids);
        for($j=0;$j<$comment_count;$j++){
            $user_id = $all_users[array_rand($all_users)];
            $comment = $comment_texts[array_rand($comment_texts)];
            $created_at = date('Y-m-d H:i:s', strtotime('-'.rand(0,365).' days'));
            $stmt = $pdo->prepare("INSERT INTO comments (fund_id,user_id,comment,created_at) VALUES (?,?,?,?)");
            $stmt->execute([$fund_id,$user_id,$comment,$created_at]);
            $stmt = $pdo->prepare("UPDATE funds f SET comments_count=(SELECT COUNT(*) FROM comments c WHERE c.fund_id=f.id) WHERE f.id=?");
            $stmt->execute([$fund_id]);
        }
    }

    echo "<div style='background:#d4edda;padding:20px;border-radius:5px;margin:20px 0;'>";
    echo "<h3>✅ Dummy data generation completed!</h3>";
    echo "<strong>Generated:</strong><br>";
    echo "- ".count($fundraiser_ids)." Fundraiser accounts<br>";
    echo "- ".count($backer_ids)." Backer accounts<br>";
    echo "- ".count($fund_ids)." Fund campaigns<br>";
    echo "- Likes, donations, comments<br>";
    echo "</div>";

} catch(Exception $e){
    echo "<div style='background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;'>";
    echo "<strong>❌ Error:</strong> ".$e->getMessage();
    echo "</div>";
}
?>
