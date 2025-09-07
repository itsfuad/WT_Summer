<?php
/**
 * Simple File Upload Manager
 * Handles basic file upload and storage without image processing
 */

class UploadManager {
    private $uploadsDir;
    private $webRoot;
    private $allowedTypes = ['image/jpeg', 'image/png'];
    private $maxFileSize = 10 * 1024 * 1024; // 10MB

    public function __construct() {
        // Use relative path from upload manager location (shared/includes/)
        $this->webRoot = '../..';
        $this->uploadsDir = $this->webRoot . '/uploads';
    }
    
    /**
     * Upload profile image
     */
    public function uploadProfileImage($file, $userId) {
        return $this->uploadFile($file, 'profile', $userId);
    }
    
    /**
     * Upload cover image
     */
    public function uploadCoverImage($file, $fundId) {
        return $this->uploadFile($file, 'cover', $fundId);
    }
    
    /**
     * Main upload handler
     */
    private function uploadFile($file, $type, $entityId) {
        // Validate file
        $validation = $this->validateFile($file);
        if (!$validation['success']) {
            return $validation;
        }
        
        // Create directory if it doesn't exist
        $typeDir = $this->uploadsDir . '/' . $type . 's';
        if (!is_dir($typeDir)) {
            if (!mkdir($typeDir, 0755, true)) {
                return ['success' => false, 'message' => 'Failed to create upload directory'];
            }
        }
        
        // Generate unique filename
        $extension = $this->getFileExtension($file['name']);
        $filename = $this->generateUniqueFilename($entityId, $type, $extension);
        $targetPath = $typeDir . '/' . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Update database
            $this->updateDatabase($type, $entityId, $filename);
            
            return [
                'success' => true,
                'message' => 'File uploaded successfully',
                'filename' => $filename,
                'path' => $targetPath
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to move uploaded file'];
        }
    }
    
    /**
     * Validate uploaded file
     */
    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload error: ' . $this->getUploadErrorMessage($file['error'])];
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return ['success' => false, 'message' => 'File too large. Maximum size is 10MB'];
        }
        
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type. Only JPG and PNG are allowed'];
        }
        
        return ['success' => true];
    }
    
    /**
     * Generate unique filename
     */
    private function generateUniqueFilename($entityId, $type, $extension) {
        $timestamp = time();
        $hash = substr(hash('sha256', $entityId . $type . $timestamp . uniqid()), 0, 16);
        return $type . '_' . $entityId . '_' . $hash . '.' . $extension;
    }
    
    /**
     * Get file extension
     */
    private function getFileExtension($filename) {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
    
    /**
     * Update database with new file path
     */
    private function updateDatabase($type, $entityId, $filename) {
        if ($type === 'profile') {
            $userManager = new UserManager();
            $userManager->updateProfileImage($entityId, $filename);
        } else if ($type === 'cover') {
            $fundManager = new FundManager();
            $fundManager->updateFundCoverImage($entityId, $filename);
        }
    }
    
    /**
     * Get image URL based on type and filename
     */
    public function getImageUrl($type, $filename = null) {
        if ($filename) {
            $path = $this->uploadsDir . '/' . $type . 's/' . $filename;
            if (file_exists($path)) {
                return $this->webRoot . '/uploads/' . $type . 's/' . $filename;
            }
        }
        return $this->webRoot . '/images/default-' . $type . '.png';
    }
    
    /**
     * Render upload form
     */
    public function renderUploadForm($type, $currentImage = null) {
        $inputName = $type . '_image';
        $previewId = $type . '_preview';
        ?>
        <div class="upload-section">
            <div class="upload-area">
                <input type="file" id="<?= $inputName ?>" name="<?= $inputName ?>" accept="image/jpeg,image/png" onchange="previewImage(this, '<?= $previewId ?>')">
                <label for="<?= $inputName ?>" class="upload-label">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Choose Image</span>
                </label>
            </div>
            <div class="<?= $type ?>-preview" id="<?= $previewId ?>">
                <?php if ($currentImage): ?>
                    <img src="<?= htmlspecialchars($currentImage) ?>" alt="Current <?= $type ?>">
                <?php endif; ?>
            </div>
        </div>
        <script>
        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById(previewId).innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        </script>
        <?php
    }
    
    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($error) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'File too large',
            UPLOAD_ERR_FORM_SIZE => 'File too large',
            UPLOAD_ERR_PARTIAL => 'File upload incomplete',
            UPLOAD_ERR_NO_FILE => 'No file selected',
            UPLOAD_ERR_NO_TMP_DIR => 'No temporary directory',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write file',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        return $messages[$error] ?? 'Unknown upload error';
    }
}
