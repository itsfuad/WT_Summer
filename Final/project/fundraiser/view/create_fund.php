<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Campaign - CrowdFund</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../css/create_fund.css">
</head>
<body>
    <?php
    require_once '../../includes/session.php';
    require_once '../../includes/functions.php';
    
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
        $featured = isset($_POST['featured']) ? 1 : 0;
        
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
                'end_date' => $end_date,
                'featured' => $featured
            ]);
            
            if ($fund_id) {
                $success = "Campaign created successfully!";
                // Redirect after 2 seconds
                header("refresh:2;url=index.php");
            } else {
                $error = "Failed to create campaign. Please try again.";
            }
        }
    }
    ?>
    
    <div class="create-fund-container">
        <div class="header">
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
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

        <form method="POST" class="create-fund-form">
            <div class="form-section">
                <h2><i class="fas fa-info-circle"></i> Basic Information</h2>
                
                <div class="form-group">
                    <label for="title">Campaign Title *</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                           placeholder="Give your campaign a compelling title" required>
                </div>
                
                <div class="form-group">
                    <label for="short_description">Short Description</label>
                    <input type="text" id="short_description" name="short_description" 
                           value="<?php echo htmlspecialchars($_POST['short_description'] ?? ''); ?>" 
                           placeholder="A brief summary (optional)" maxlength="100">
                    <small>This will appear on campaign cards (max 100 characters)</small>
                </div>
                
                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                    <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-section">
                <h2><i class="fas fa-align-left"></i> Campaign Description</h2>
                
                <div class="form-group">
                    <label for="description">Full Description *</label>
                    <textarea id="description" name="description" rows="8" 
                              placeholder="Tell your story... Why are you raising funds? What will the money be used for? What impact will it have?" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    <small>Be detailed and compelling. This is where you convince people to support your cause.</small>
                </div>
            </div>

            <div class="form-section">
                <h2><i class="fas fa-chart-line"></i> Funding Details</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="goal_amount">Funding Goal (USD) *</label>
                        <input type="number" id="goal_amount" name="goal_amount" 
                               value="<?php echo htmlspecialchars($_POST['goal_amount'] ?? ''); ?>" 
                               min="1" step="1" placeholder="10000" required>
                        <small>Set a realistic and achievable goal</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">Campaign End Date *</label>
                        <input type="date" id="end_date" name="end_date" 
                               value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>" 
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                        <small>When should this campaign end?</small>
                    </div>
                </div>
            </div>

            <div class="form-section terms-checkbox">
                <h2><i class="fas fa-star"></i> Campaign Options</h2>
                
                <div class="form-group">
                    <div class="checkbox-container">
                        <input type="checkbox" name="featured" value="1" id="featured"
                               <?php echo (isset($_POST['featured']) && $_POST['featured']) ? 'checked' : ''; ?>>
                        <div class="checkmark"></div>
                        <label for="featured" class="checkbox-label">
                            Request to feature this campaign
                            <small style="display: block; margin-top: 4px; color: var(--gray-500);">
                                Featured campaigns get more visibility (subject to admin approval)
                            </small>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-rocket"></i> Launch Campaign
                </button>
            </div>
        </form>
    </div>
</body>
</html>
