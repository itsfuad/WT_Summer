<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Campaign - CrowdFund</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../css/fund_form.css">
</head>
<body>
    <?php
    require_once '../../shared/includes/session.php';
    require_once '../../shared/includes/functions.php';
    require_once '../includes/fund_form.php';
    
    requireLogin();
    requireRole('fundraiser');
    $user = getCurrentUser();
    
    $fundManager = new FundManager();
    
    // Get fund ID from URL
    $fund_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$fund_id) {
        header('Location: index.php');
        exit;
    }
    
    // Get fund details
    $fund = $fundManager->getFundById($fund_id);
    
    if (!$fund || $fund['fundraiser_id'] != $user['id']) {
        header('Location: index.php');
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
                $success = "Campaign updated successfully!";
                // Refresh fund data
                $fund = $fundManager->getFundById($fund_id);
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
                <a href="../../campaign/view.php?id=<?php echo $fund['id']; ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Campaign
                </a>
                <h1>Edit Campaign</h1>
            </div>
            <div class="header-actions">
                <a href="../../campaign/view.php?id=<?php echo $fund['id']; ?>" class="btn btn-secondary">
                    <i class="fas fa-eye"></i> View Campaign
                </a>
                <a href="analytics.php?id=<?php echo $fund['id']; ?>" class="btn btn-outline">
                    <i class="fas fa-chart-bar"></i> Analytics
                </a>
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
