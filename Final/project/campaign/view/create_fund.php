<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Campaign - CrowdFund</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../shared/css/upload.css">
    <link rel="stylesheet" href="../css/fund_form.css">
</head>
<body>
    <?php
    require_once '../../shared/includes/session.php';
    require_once '../../shared/includes/functions.php';
    require_once '../../shared/includes/fund_form.php';
    require_once '../../shared/includes/upload_manager.php';
    
    requireLogin();
    requireRole('fundraiser');
    $user = getCurrentUser();
    
    $fundManager = new FundManager();
    $categories = $fundManager->getCategories();
    
    $success = '';
    $error = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title'] ?? '');
        $short_description = trim($_POST['short_description'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $goal_amount = floatval($_POST['goal_amount'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $end_date = $_POST['end_date'] ?? '';
        
        // Validation
        if (empty($title) || empty($description) || $goal_amount <= 0 || empty($end_date) || $category_id <= 0) {
            $error = "Please fill in all required fields with valid values.";
        } else if (strtotime($end_date) <= time()) {
            $error = "End date must be in the future.";
        } else {
            // Create the fund
            $fund_id = $fundManager->createFund([
                'title' => $title,
                'short_description' => $short_description,
                'description' => $description,
                'goal_amount' => $goal_amount,
                'category_id' => $category_id,
                'fundraiser_id' => $user['id'],
                'end_date' => $end_date
            ]);
            
            if ($fund_id) {
                // Handle cover image upload if provided
                if (!empty($_FILES['cover_image']['name'])) {
                    $uploadManager = new UploadManager();
                    $upload_result = $uploadManager->uploadCoverImage($_FILES['cover_image'], $fund_id);
                    
                    if (!$upload_result['success']) {
                        $error = "Campaign created but cover image upload failed: " . $upload_result['message'];
                    } else {
                        $success = "Campaign created successfully with cover image!";
                    }
                } else {
                    $success = "Campaign created successfully!";
                }
                
                if (!$error) {
                    header('Location: ../../' . $user['role'] . '/view/index.php');
                }
            } else {
                $error = "Failed to create campaign. Please try again.";
            }
        }
    }
    ?>
    
    <div class="fund-form-container">
        <div class="header">
            <div class="header-left">
                <a href="../../<?php echo $user['role']; ?>/view/index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div class="header-actions">
                <div class="sub-title">
                    <i class="fas fa-pencil-alt"></i> Edit Campaign
                </div>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php 
        renderFundForm([
            'mode' => 'create',
            'categories' => $categories
        ]);
        ?>
    </div>
</body>
</html>
