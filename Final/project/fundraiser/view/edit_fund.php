<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Campaign - CrowdFund</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../css/edit_fund.css">
</head>
<body>
    <?php
    require_once '../../includes/session.php';
    require_once '../../includes/functions.php';
    
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
        $status = $_POST['status'] ?? $fund['status'];
        $featured = isset($_POST['featured']) ? 1 : 0;
        
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
                'status' => $status,
                'featured' => $featured
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
    
    <div class="edit-fund-container">
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

        <form method="POST" class="edit-fund-form">
            <!-- Basic Information -->
            <div class="form-section">
                <h2><i class="fas fa-info-circle"></i> Basic Information</h2>
                
                <div class="form-group">
                    <label for="title">Campaign Title *</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($fund['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="short_description">Short Description</label>
                    <input type="text" id="short_description" name="short_description" 
                           value="<?php echo htmlspecialchars($fund['short_description'] ?? ''); ?>" 
                           maxlength="100">
                    <small>Brief summary for campaign cards (max 100 characters)</small>
                </div>
                
                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select id="category_id" name="category_id" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                    <?php echo ($fund['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Campaign Description -->
            <div class="form-section">
                <h2><i class="fas fa-align-left"></i> Campaign Description</h2>
                
                <div class="form-group">
                    <label for="description">Full Description *</label>
                    <textarea id="description" name="description" rows="8" required><?php echo htmlspecialchars($fund['description']); ?></textarea>
                    <small>Tell your story and explain how the funds will be used</small>
                </div>
            </div>

            <!-- Funding Details -->
            <div class="form-section">
                <h2><i class="fas fa-chart-line"></i> Funding Details</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="goal_amount">Funding Goal (USD) *</label>
                        <input type="number" id="goal_amount" name="goal_amount" 
                               value="<?php echo $fund['goal_amount']; ?>" min="1" step="1" required>
                        <small>Current raised: <?php echo formatCurrency($fund['current_amount']); ?></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">Campaign End Date *</label>
                        <input type="date" id="end_date" name="end_date" 
                               value="<?php echo $fund['end_date']; ?>" required>
                        <small>When should this campaign end?</small>
                    </div>
                </div>
            </div>

            <!-- Campaign Status -->
            <div class="form-section">
                <h2><i class="fas fa-toggle-on"></i> Campaign Status</h2>
                
                <div class="form-group">
                    <label for="status">Campaign Status *</label>
                    <select id="status" name="status" required>
                        <option value="active" <?php echo ($fund['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="paused" <?php echo ($fund['status'] === 'paused') ? 'selected' : ''; ?>>Paused</option>
                        <option value="completed" <?php echo ($fund['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo ($fund['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <small>Current status affects visibility and donation acceptance</small>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-container">
                        <input type="checkbox" name="featured" value="1" id="featured"
                               <?php echo $fund['featured'] ? 'checked' : ''; ?>>
                        <div class="checkmark"></div>
                        <div class="checkbox-label">
                            Request to feature this campaign
                            <small style="display: block; margin-top: 4px; color: var(--gray-500);">
                                Featured campaigns get more visibility (subject to admin approval)
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campaign Statistics (Read-only) -->
            <div class="form-section">
                <h2><i class="fas fa-chart-pie"></i> Campaign Statistics</h2>
                
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-label">Total Raised</div>
                        <div class="stat-value"><?php echo formatCurrency($fund['current_amount']); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Total Backers</div>
                        <div class="stat-value"><?php echo $fund['backer_count']; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Views</div>
                        <div class="stat-value"><?php echo $fund['views_count']; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Days Left</div>
                        <div class="stat-value"><?php echo getDaysLeft($fund['end_date']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <a href="../../campaign/view.php?id=<?php echo $fund['id']; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</body>
</html>
