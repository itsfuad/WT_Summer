function removeProfileImage() {
    // Set the hidden field to indicate removal
    document.getElementById('remove_profile_image').value = '1';
    
    // Immediately update the UI to show placeholder state
    updateProfileImageUI(true);
    
    // Show removal feedback toast
    showToast('Image removed', 'success');
}

function updateProfileImageUI(isRemoved) {
    const container = document.querySelector('.profile-upload-container');
    const preview = document.getElementById('profile-preview');
    const overlay = container.querySelector('.upload-overlay');
    let placeholder = container.querySelector('.upload-placeholder');
    const removeButton = container.parentElement.querySelector('.upload-actions');
    
    if (isRemoved) {
        // Hide the current image and overlay
        if (preview) {
            preview.style.display = 'none';
        }
        if (overlay) {
            overlay.style.display = 'none';
        }
        
        // Show placeholder - create if it doesn't exist
        if (!placeholder) {
            placeholder = document.createElement('div');
            placeholder.className = 'upload-placeholder';
            placeholder.innerHTML = `
                <i class="fas fa-user"></i>
                <span>Click to Upload</span>
            `;
            container.appendChild(placeholder);
        }
        
        // Ensure placeholder is visible
        placeholder.style.display = 'flex';
        
        // Hide the remove button
        if (removeButton) {
            removeButton.style.display = 'none';
        }
        
        // Clear the file input
        const fileInput = document.getElementById('profile_image');
        if (fileInput) {
            fileInput.value = '';
        }
    }
}

function showToast(message, type = 'success') {
    // Remove any existing toasts
    const existingToast = document.querySelector('.profile-toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Create toast
    const toast = document.createElement('div');
    toast.className = 'profile-toast';
    
    const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-info-circle';
    const bgColor = type === 'success' ? '#10b981' : '#3b82f6';
    
    toast.innerHTML = `
        <i class="${icon}"></i>
        ${message}
    `;
    
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${bgColor};
        color: white;
        padding: 12px 16px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 500;
        animation: slideIn 0.3s ease-out;
        max-width: 300px;
    `;
    
    // Add animation styles if not already present
    if (!document.querySelector('#toast-styles')) {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(toast);
    
    // Remove the toast after 3 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-in forwards';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

function previewImage(input, type) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const container = document.querySelector('.profile-upload-container');
            if (!container) {
                console.error('Profile upload container not found');
                return;
            }
            
            const previewId = type + '-preview';
            let preview = document.getElementById(previewId);
            
            // Hide placeholder when image is loaded
            const placeholder = container.querySelector('.upload-placeholder');
            if (placeholder) {
                placeholder.style.display = 'none';
            }
            
            // Create preview image if it doesn't exist
            if (!preview) {
                preview = document.createElement('img');
                if (!preview) {
                    alert('Failed to create img element!');
                    return;
                }
                preview.id = previewId;
                preview.className = 'profile-preview';
                preview.alt = 'Profile picture';
                preview.style.width = '100%';
                preview.style.height = '100%';
                preview.style.objectFit = 'cover';
                container.appendChild(preview);
            }
            
            // Set the image - double check preview exists
            if (!preview) {
                alert('Preview is null before setting src!');
                return;
            }
            preview.src = e.target.result;
            preview.style.display = 'block';
            
            // Create/show overlay
            let overlay = container.querySelector('.upload-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'upload-overlay';
                overlay.innerHTML = `
                    <i class="fas fa-camera"></i>
                    <span>Change Photo</span>
                `;
                container.appendChild(overlay);
            }
            overlay.style.display = 'flex';
            
            // Create/show remove button
            let actionsContainer = container.parentElement.querySelector('.upload-actions');
            if (!actionsContainer) {
                // Create the actions container if it doesn't exist
                actionsContainer = document.createElement('div');
                actionsContainer.className = 'upload-actions';
                
                const uploadInfo = container.parentElement.querySelector('.upload-info');
                if (uploadInfo) {
                    container.parentElement.insertBefore(actionsContainer, uploadInfo);
                } else {
                    container.parentElement.appendChild(actionsContainer);
                }
            }
            
            // Add remove button if it doesn't exist
            if (!actionsContainer.querySelector('button')) {
                actionsContainer.innerHTML = `
                    <button type="button" class="btn btn-outline btn-sm" onclick="removeProfileImage()">
                        <i class="fas fa-trash"></i> Remove Photo
                    </button>
                `;
            }
            actionsContainer.style.display = 'flex';
            
            // Reset the removal flag since user is uploading a new image
            const removeFlag = document.getElementById('remove_profile_image');
            if (removeFlag) {
                removeFlag.value = '0';
            }
            
            // Show success toast
            showToast('Image selected', 'success');
        };
        
        reader.readAsDataURL(file);
    }
}

function toggleAllPasswords() {
    const passwordFields = document.querySelectorAll('.password-field');
    const toggleIcon = document.getElementById('password-toggle');
    
    const isHidden = passwordFields[0].type === 'password';
    
    passwordFields.forEach(field => {
        field.type = isHidden ? 'text' : 'password';
    });
    
    if (isHidden) {
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}