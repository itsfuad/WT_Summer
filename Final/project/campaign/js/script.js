let reportCommentId = null;

// Auto-resize textarea
function autoResize(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
}

// Like functionality
function toggleLike(fundId) {
    
    const likeBtn = document.getElementById('like-btn');
    const likeText = document.getElementById('like-text');
    const likesCount = document.getElementById('likes-count');
    
    likeBtn.disabled = true;
    
    fetch('../ajax/toggle_like.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            fund_id: fundId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.liked) {
                likeBtn.classList.add('liked');
                likeText.textContent = 'Liked';
            } else {
                likeBtn.classList.remove('liked');
                likeText.textContent = 'Like';
            }
            
            likesCount.textContent = '(' + data.likes_count + ')';
            showNotification(data.message, 'success');
        } else {
            showNotification(data.error || 'Failed to update like', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    })
    .finally(() => {
        likeBtn.disabled = false;
    });
}

// Donate Modal handlers
function openDonateModal() {
    const m = document.getElementById('donate-modal');
    m.style.display = 'flex';
}
function closeDonateModal() {
    const m = document.getElementById('donate-modal');
    m.style.display = 'none';
}
function submitDonation(e) {
    e.preventDefault();
    const amount = parseFloat(document.getElementById('donation-amount').value || '0');
    const comment = document.getElementById('donation-comment').value.trim();
    const anonymous = document.getElementById('donation-anonymous').checked ? 1 : 0;
    if (!(amount > 0)) { showNotification('Enter a valid amount', 'error'); return; }
    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.disabled = true; submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing';
    fetch('../ajax/donate.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ fund_id: fundId, amount, comment, anonymous })
    }).then(r => r.json()).then(data => {
        if (data.success) {
            showNotification('Thank you for your donation!', 'success');
            
            // Update progress stats with proper IDs
            updateProgressStats(data);

            // Update Recent Backers list without refresh
            try {
                ensureBackersSection();
                prependBackerItem(data.donation);
            } catch (_) {}
            closeDonateModal();
        } else {
            showNotification(data.error || 'Donation failed', 'error');
        }
    }).catch(() => showNotification('Network error', 'error'))
        .finally(() => { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Donation'; });
}

function formatCurrency(amount) { try { return '$' + Number(amount).toLocaleString(); } catch { return '$' + amount; } }

function updateProgressStats(data) {
    try {
        // Update current amount
        const currentAmountEl = document.getElementById('current-amount');
        if (currentAmountEl && data.current_amount !== undefined) {
            currentAmountEl.textContent = formatCurrency(data.current_amount);
        }
        
        // Update backer count
        const backerCountEl = document.getElementById('backer-count');
        if (backerCountEl && data.backer_count !== undefined) {
            backerCountEl.textContent = data.backer_count;
        }
        
        // Update progress bar percentage
        const progressFillEl = document.getElementById('progress-fill');
        if (progressFillEl && data.current_amount !== undefined && typeof goalAmount !== 'undefined') {
            const percentage = Math.min((data.current_amount / goalAmount) * 100, 100);
            progressFillEl.style.width = percentage + '%';
        }
        
        // Days left doesn't change with donations, so no update needed
        
    } catch (error) {
        console.error('Error updating progress stats:', error);
    }
}

function ensureBackersSection() {
    let section = document.querySelector('.backers-section');
    if (!section) {
        const container = document.querySelector('.campaign-content');
        const div = document.createElement('div');
        div.className = 'backers-section';
        div.innerHTML = '<h3>Recent backers</h3><div class="backers-list" id="backers-list"></div>';
        container.appendChild(div);
    } else {
        let listById = document.getElementById('backers-list');
        if (!listById) {
            const existing = section.querySelector('.backers-list');
            if (existing) {
                existing.id = 'backers-list';
            } else {
                const list = document.createElement('div');
                list.id = 'backers-list';
                list.className = 'backers-list';
                section.appendChild(list);
            }
        }
    }
}

function prependBackerItem(d) {
    const list = document.getElementById('backers-list');
    if (!list) return;
    const item = document.createElement('div');
    item.className = 'backer-item';
    
    // Get avatar HTML based on whether donation is anonymous and has profile image
    const avatarHtml = (!d.anonymous && d.profile_image_url && d.profile_image_url !== '') ? 
        `<img src="${d.profile_image_url}" alt="${escapeHtml(d.backer_name)}" class="user-profile-img">` :
        `<img src="../../uploads/profiles/default-profile.png" alt="${d.anonymous ? 'Anonymous' : escapeHtml(d.backer_name)}" class="user-profile-img ${d.anonymous ? 'anonymous' : 'default'}">`;
    
    item.innerHTML = `
        <div class="backer-info">
            <div class="backer-avatar">${avatarHtml}</div>
            <div class="backer-details">
                <div class="backer-name">${d.anonymous ? 'Anonymous' : `<a class="username" href="../../public_profile/view?id=${d.backer_id}">${escapeHtml(d.backer_name)}</a>`}</div>
                <div class="backer-time js-timeago" data-time="${new Date().toISOString().slice(0,19).replace('T',' ')}">just now</div>
            </div>
        </div>
        <div class="backer-amount">${formatCurrency(d.amount)}</div>
    `;
    list.insertAdjacentElement('afterbegin', item);
}

function escapeHtml(s){
    const div = document.createElement('div');
    div.textContent = s || '';
    return div.innerHTML;
}

// Report Modal handlers
function openReportModal() {
    reportCommentId = null;
    const m = document.getElementById('report-modal');
    m.style.display = 'flex';
}
function openCommentReport(commentId) {
    reportCommentId = commentId;
    const m = document.getElementById('report-modal');
    m.style.display = 'flex';
}
function closeReportModal() {
    const m = document.getElementById('report-modal');
    m.style.display = 'none';
}
function submitReport(e) {
    e.preventDefault();
    const reason = document.getElementById('report-reason').value;
    const description = document.getElementById('report-description').value.trim();
    if (!reason) { showNotification('Select a reason', 'error'); return; }
    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.disabled = true; submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting';
    fetch('../ajax/report.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ fund_id: reportCommentId ? null : fundId, comment_id: reportCommentId ? reportCommentId : null, reason, description })
    }).then(r => r.json()).then(data => {
        if (data.success) { showNotification('Report submitted', 'success'); closeReportModal(); }
        else { showNotification(data.error || 'Failed to submit report', 'error'); }
    }).catch(() => showNotification('Network error', 'error'))
        .finally(() => { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-flag"></i> Submit Report'; });
}

// Comment functionality
function submitComment(event) {
    event.preventDefault();
    
    const commentText = document.getElementById('comment-text').value.trim();
    if (!commentText) {
        showNotification('Please enter a comment', 'error');
        return;
    }
    
    const submitBtn = event.target.querySelector('.submit-comment-btn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    fetch('../ajax/add_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            fund_id: fundId,
            comment: commentText
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Response data:', data);  // Debug log
        if (data.success) {
            addCommentToList(data.comment);
            document.getElementById('comment-text').value = '';
            document.getElementById('comment-text').style.height = 'auto';
            showNotification('Comment added successfully!', 'success');
        } else {
            showNotification(data.error || 'Failed to add comment', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
    });
}

function addCommentToList(comment) {
    console.log('Adding comment:', comment);  // Debug log
    let commentsList = document.getElementById('comments-list');
    
    // Create comments section if it doesn't exist
    if (!commentsList) {
        const commentsSection = document.createElement('div');
        commentsSection.className = 'comments-section';
        commentsSection.innerHTML = `
            <div class="comments-header">
                <h3>Comments (1)</h3>
            </div>
            <div class="comments-list" id="comments-list"></div>
        `;
        
        const commentFormSection = document.querySelector('.comment-form-section');
        commentFormSection.parentNode.insertBefore(commentsSection, commentFormSection.nextSibling);
        commentsList = document.getElementById('comments-list');
    }
    
    // Get user avatar HTML
    const userAvatarHtml = comment.profile_image_url && comment.profile_image_url !== '' ? 
        `<img src="${comment.profile_image_url}" alt="${comment.user_name}" class="user-profile-img">` :
        `<img src="../../uploads/profiles/default-profile.png" alt="${comment.user_name}" class="user-profile-img default">`;
    
    const commentHtml = `
        <div class="comment-item" data-comment-id="${comment.id}">
            <div class="comment-content">
                <div class="comment-header">
                    <div class="comment-user">
                        <div class="user-avatar">
                            ${userAvatarHtml}
                        </div>
                        <div class="user-info">
                            <span class="username">
                                <a class="username" href="../../public_profile/view?id=${comment.user_id}">${comment.user_name}</a>
                                ${comment.user_role === 'fundraiser' ? '<span class="role-badge fundraiser">Creator</span>' : ''}
                                ${comment.user_role === 'backer' ? '<span class="role-badge backer">Backer</span>' : ''}
                            </span>
                            <span class="comment-time js-timeago" data-time="${comment.created_at}">just now</span>
                        </div>
                    </div>
                    ${currentUserId == comment.user_id ? `
                    <div class="comment-actions">
                        <button class="comment-action-btn" onclick="editComment(${comment.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="comment-action-btn delete" onclick="deleteComment(${comment.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    ` : ''}
                </div>
                <div class="comment-text" id="comment-content-${comment.id}">
                    ${comment.comment.replace(/\n/g, '<br>')}
                </div>
            </div>
        </div>
    `;
    
    commentsList.insertAdjacentHTML('afterbegin', commentHtml);
    
    // Update comment count if available
    const currentCount = commentsList.children.length;
    updateCommentCount(currentCount);
}

function updateCommentCount(count) {
    const header = document.querySelector('.comments-header h3');
    if (header) {
        header.textContent = `Comments (${count})`;
    }
}

function deleteComment(commentId) {
    if (!confirm('Are you sure you want to delete this comment?')) {
        return;
    }
    
    fetch('../ajax/delete_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            fund_id: fundId,
            comment_id: commentId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const commentElement = document.querySelector(`[data-comment-id="${commentId}"]`);
            if (commentElement) {
                commentElement.remove();
            }
            showNotification('Comment deleted successfully!', 'success');
        } else {
            showNotification(data.error || 'Failed to delete comment', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    });
}

function editComment(commentId) {
    const commentElement = document.querySelector(`[data-comment-id="${commentId}"]`);
    const contentElement = document.getElementById(`comment-content-${commentId}`);
    
    if (!commentElement || !contentElement) return;
    
    const currentText = contentElement.textContent.trim();
    
    const editForm = `
        <div class="edit-comment-form">
            <textarea class="edit-comment-text" maxlength="1000">${currentText}</textarea>
            <div class="edit-actions">
                <button type="button" class="btn btn-outline btn-sm" onclick="cancelEdit(${commentId})">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveEdit(${commentId})">Save</button>
            </div>
        </div>
    `;
    
    contentElement.innerHTML = editForm;
    contentElement.querySelector('.edit-comment-text').focus();
}

function cancelEdit(commentId) {
    location.reload();
}

function saveEdit(commentId) {
    const contentElement = document.getElementById(`comment-content-${commentId}`);
    const textarea = contentElement.querySelector('.edit-comment-text');
    const newText = textarea.value.trim();
    
    if (!newText) {
        showNotification('Comment cannot be empty', 'error');
        return;
    }
    
    fetch('../ajax/edit_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            fund_id: fundId,
            comment_id: commentId,
            comment: newText
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            contentElement.innerHTML = data.comment.replace(/\n/g, '<br>');
            showNotification('Comment updated successfully!', 'success');
        } else {
            showNotification(data.error || 'Failed to update comment', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    });
}

function shareCampaign() {
    if (navigator.share) {
        navigator.share({
            title: fundTitle,
            text: 'Check out this amazing campaign!',
            url: window.location.href
        });
    } else {
        navigator.clipboard.writeText(window.location.href).then(() => {
            showNotification('Campaign link copied to clipboard!', 'success');
        });
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.add('show'), 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => document.body.removeChild(notification), 300);
    }, 3000);
}

// Live time-ago updates for comments and backers
let timeUpdateInterval;

function timeAgoFrom(dateStr) {
    if (!dateStr) return 'just now';
    
    // Handle both MySQL datetime format and ISO
    let d;
    if (dateStr.includes('T')) {
        d = new Date(dateStr);
    } else {
        // MySQL format: YYYY-MM-DD HH:MM:SS
        d = new Date(dateStr.replace(' ', 'T'));
    }
    
    const now = new Date();
    const diff = Math.floor((now - d) / 1000);
    
    if (isNaN(diff) || diff < 0) return 'just now';
    if (diff < 60) return 'just now';
    
    const mins = Math.floor(diff / 60);
    if (mins < 60) return mins + ' minute' + (mins > 1 ? 's' : '') + ' ago';
    
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return hrs + ' hour' + (hrs > 1 ? 's' : '') + ' ago';
    
    const days = Math.floor(hrs / 24);
    if (days < 30) return days + ' day' + (days > 1 ? 's' : '') + ' ago';
    
    const months = Math.floor(days / 30);
    if (months < 12) return months + ' month' + (months > 1 ? 's' : '') + ' ago';
    
    const years = Math.floor(months / 12);
    return years + ' year' + (years > 1 ? 's' : '') + ' ago';
}

function refreshTimeagos() {
    const elements = document.querySelectorAll('.js-timeago[data-time]');
    elements.forEach(el => {
        const timeStr = el.getAttribute('data-time');
        if (timeStr) {
            const newText = timeAgoFrom(timeStr);
            if (el.textContent !== newText) {
                el.textContent = newText;
            }
        }
    });
}

function startTimeUpdates() {
    // Clear any existing interval
    if (timeUpdateInterval) {
        clearInterval(timeUpdateInterval);
    }
    
    // Initial run
    refreshTimeagos();
    
    // Update every 30 seconds for more responsiveness
    timeUpdateInterval = setInterval(refreshTimeagos, 30 * 1000);
}

function stopTimeUpdates() {
    if (timeUpdateInterval) {
        clearInterval(timeUpdateInterval);
        timeUpdateInterval = null;
    }
}

// Start time updates when page loads
document.addEventListener('DOMContentLoaded', startTimeUpdates);

// Clean up interval on page unload
window.addEventListener('beforeunload', stopTimeUpdates);
window.addEventListener('pagehide', stopTimeUpdates);

// Also start immediately if DOM is already loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startTimeUpdates);
} else {
    startTimeUpdates();
}

// Admin functions
function toggleFeature(fundId) {
    fetch('../ajax/toggle_feature.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `fund_id=${fundId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const btn = document.getElementById('feature-btn');
            if (data.featured) {
                btn.className = 'btn btn-outline';
                btn.innerHTML = '<i class="fas fa-star"></i> Unfeature';
            } else {
                btn.className = 'btn btn-primary';
                btn.innerHTML = '<i class="fas fa-star"></i> Mark as Featured';
            }
            showNotification(data.message, 'success');
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    });
}

function toggleFreeze(fundId) {
    const btn = document.getElementById('freeze-btn');
    const action = btn.innerHTML.includes('Freeze') ? 'freeze' : 'unfreeze';
    
    if (!confirm(`Are you sure you want to ${action} this campaign?`)) return;
    
    fetch('../ajax/toggle_freeze.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `fund_id=${fundId}&action=${action}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.status === 'frozen') {
                btn.innerHTML = '<i class="fas fa-play"></i> Unfreeze';
            } else {
                btn.innerHTML = '<i class="fas fa-pause"></i> Freeze';
            }
            showNotification(data.message, 'success');
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    });
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startTimeUpdates);
} else {
    startTimeUpdates();
}