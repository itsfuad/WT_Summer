<?php
/**
 * Reusable Fund Form Component
 * Used for both creating and editing campaigns
 */

function renderFundForm($config) {
    $isEdit = $config['mode'] === 'edit';
    $fund = $config['fund'] ?? null;
    $categories = $config['categories'] ?? [];
    $formClass = $isEdit ? 'edit-fund-form' : 'create-fund-form';
    $submitText = $isEdit ? 'Save Changes' : 'Launch Campaign';
    $submitIcon = $isEdit ? 'fas fa-save' : 'fas fa-rocket';
    
    // Get form values - either from existing fund or POST data
    $formData = [];
    if ($isEdit && $fund) {
        $formData = [
            'title' => $fund['title'],
            'short_description' => $fund['short_description'] ?? '',
            'description' => $fund['description'],
            'goal_amount' => $fund['goal_amount'],
            'category_id' => $fund['category_id'],
            'end_date' => $fund['end_date'],
            'status' => $fund['status'] ?? 'active'
        ];
    } else {
        $formData = [
            'title' => $_POST['title'] ?? '',
            'short_description' => $_POST['short_description'] ?? '',
            'description' => $_POST['description'] ?? '',
            'goal_amount' => $_POST['goal_amount'] ?? '',
            'category_id' => $_POST['category_id'] ?? '',
            'end_date' => $_POST['end_date'] ?? '',
            'status' => $_POST['status'] ?? 'active'
        ];
    }
    ?>
    
    <form method="POST" class="<?php echo $formClass; ?>">
        <!-- Basic Information -->
        <div class="form-section">
            <h2><i class="fas fa-info-circle"></i> Basic Information</h2>
            
            <div class="form-group">
                <label for="title">Campaign Title *</label>
                <input type="text" id="title" name="title" 
                       value="<?php echo htmlspecialchars($formData['title']); ?>" 
                       placeholder="Give your campaign a compelling title" required>
            </div>
            
            <div class="form-group">
                <label for="short_description">Short Description</label>
                <input type="text" id="short_description" name="short_description" 
                       value="<?php echo htmlspecialchars($formData['short_description']); ?>" 
                       placeholder="A brief summary (optional)" maxlength="100">
                <small><?php echo $isEdit ? 'Brief summary for campaign cards (max 100 characters)' : 'This will appear on campaign cards (max 100 characters)'; ?></small>
            </div>
            
            <div class="form-group">
                <label for="category_id">Category *</label>
                <select id="category_id" name="category_id" required>
                    <?php if (!$isEdit): ?>
                        <option value="">Select a category</option>
                    <?php endif; ?>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"
                                <?php echo ($formData['category_id'] == $category['id']) ? 'selected' : ''; ?>>
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
                <textarea id="description" name="description" rows="8" 
                          placeholder="<?php echo $isEdit ? '' : 'Tell your story... Why are you raising funds? What will the money be used for? What impact will it have?'; ?>" 
                          required><?php echo htmlspecialchars($formData['description']); ?></textarea>
                <small><?php echo $isEdit ? 'Tell your story and explain how the funds will be used' : 'Be detailed and compelling. This is where you convince people to support your cause.'; ?></small>
            </div>
        </div>

        <!-- Funding Details -->
        <div class="form-section">
            <h2><i class="fas fa-chart-line"></i> Funding Details</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="goal_amount">Funding Goal (USD) *</label>
                    <input type="number" id="goal_amount" name="goal_amount" 
                           value="<?php echo $formData['goal_amount']; ?>" 
                           min="1" step="1" placeholder="10000" required>
                    <small>
                        <?php if ($isEdit && $fund): ?>
                            Current raised: <?php echo formatCurrency($fund['current_amount']); ?>
                        <?php else: ?>
                            Set a realistic and achievable goal
                        <?php endif; ?>
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="end_date">Campaign End Date *</label>
                    <input type="date" id="end_date" name="end_date" 
                           value="<?php echo $formData['end_date']; ?>" 
                           <?php if (!$isEdit): ?>min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"<?php endif; ?> required>
                    <small>When should this campaign end?</small>
                </div>
            </div>
        </div>

        <?php if ($isEdit): ?>
            <!-- Campaign Status (Edit mode only) -->
            <div class="form-section">
                <h2><i class="fas fa-toggle-on"></i> Campaign Status</h2>
                
                <div class="form-group">
                    <label for="status">Campaign Status *</label>
                    <select id="status" name="status" required 
                            <?php echo ($fund['status'] === 'frozen') ? 'disabled style="background:#f8f9fa; cursor:not-allowed;"' : ''; ?>>
                        <option value="active" <?php echo ($formData['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="paused" <?php echo ($formData['status'] === 'paused') ? 'selected' : ''; ?>>Paused</option>
                        <option value="completed" <?php echo ($formData['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo ($formData['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="frozen" <?php echo ($formData['status'] === 'frozen') ? 'selected' : ''; ?>>Frozen (Admin)</option>
                    </select>
                    <?php if ($fund['status'] === 'frozen'): ?>
                        <small style="color:#dc3545;">
                            <i class="fas fa-lock"></i> Status locked by administrator - cannot be changed.
                        </small>
                    <?php else: ?>
                        <small>Current status affects visibility and donation acceptance</small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Campaign Statistics (Edit mode only) -->
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
        <?php endif; ?>

        <!-- Form Actions -->
        <div class="form-actions">
            <?php if ($isEdit): ?>
                <a href="../../campaign/view.php?id=<?php echo $fund['id']; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            <?php else: ?>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">
                    <i class="fas fa-times"></i> Cancel
                </button>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">
                <i class="<?php echo $submitIcon; ?>"></i> <?php echo $submitText; ?>
            </button>
        </div>
    </form>
    <?php
}
?>
