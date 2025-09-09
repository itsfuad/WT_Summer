<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Campaign - CrowdFund</title>
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
    
    // Get fund ID from URL
    $fund_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$fund_id) {
        header('Location: ../../home/view/index.php');
        exit;
    }
    
    // Get fund details
    $fund = $fundManager->getFundById($fund_id);
    
    if (!$fund || $fund['fundraiser_id'] != $user['id']) {
        header('Location: ../../home/view/index.php');
        exit;
    }
    
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
        
        // If status is frozen, preserve it (dropdown is disabled so no POST value)
        if ($fund['status'] === 'frozen') {
            $status = 'frozen';
        } else {
            $status = $_POST['status'] ?? $fund['status'];
        }
        
        // Validation
        if (empty($title) || empty($description) || $goal_amount <= 0 || empty($end_date) || $category_id <= 0) {
            $error = "Please fill in all required fields with valid values.";
        } elseif (strtotime($end_date) <= time() && $status === 'active') {
            $error = "End date must be in the future for active campaigns.";
        } else {
            // Update the fund
            $updated = $fundManager->updateFund($fund_id, [
                'title' => $title,
                'short_description' => $short_description,
                'description' => $description,
                'goal_amount' => $goal_amount,
                'category_id' => $category_id,
                'end_date' => $end_date,
                'status' => $status
            ]);
            
            if ($updated) {
                // Handle cover image upload if provided
                if (!empty($_FILES['cover_image']['name'])) {
                    $uploadManager = new UploadManager();
                    $upload_result = $uploadManager->uploadCoverImage($_FILES['cover_image'], $fund_id);
                    
                    if (!$upload_result['success']) {
                        $error = "Campaign updated but cover image upload failed: " . $upload_result['message'];
                    } else {
                        $success = "Campaign updated successfully with new cover image!";
                    }
                } else {
                    $success = "Campaign updated successfully!";
                }
                
                if (!$error) {
                    // Refresh fund data
                    $fund = $fundManager->getFundById($fund_id);
                }
            } else {
                $error = "Failed to update campaign. Please try again.";
            }
        }
    }
    ?>
    
    <div class="fund-form-container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <a href="../../campaign/view?id=<?php echo $fund['id']; ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Campaign
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
            'mode' => 'edit',
            'fund' => $fund,
            'categories' => $categories
        ]);
        ?>
    </div>
</body>
</html>
