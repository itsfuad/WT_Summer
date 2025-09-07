<?php
/**
 * Simple test to verify upload system setup
 */
require_once 'shared/includes/upload_manager.php';
require_once 'shared/includes/functions.php';

// Check if upload directories exist
$uploadDir = __DIR__ . '/uploads';
$profilesDir = $uploadDir . '/profiles';
$coversDir = $uploadDir . '/covers';

echo "<h1>Upload System Test</h1>";

echo "<h2>Directory Structure:</h2>";
echo "<ul>";
echo "<li>Main uploads dir: " . ($uploadDir) . " - " . (is_dir($uploadDir) ? "✓ EXISTS" : "❌ MISSING") . "</li>";
echo "<li>Profiles dir: " . ($profilesDir) . " - " . (is_dir($profilesDir) ? "✓ EXISTS" : "❌ MISSING") . "</li>";
echo "<li>Covers dir: " . ($coversDir) . " - " . (is_dir($coversDir) ? "✓ EXISTS" : "❌ MISSING") . "</li>";
echo "</ul>";

echo "<h2>Default Images:</h2>";
$defaultProfile = $uploadDir . '/default-profile.png';
$defaultCover = $uploadDir . '/default-cover.png';

echo "<ul>";
echo "<li>Default profile: " . ($defaultProfile) . " - " . (file_exists($defaultProfile) ? "✓ EXISTS" : "❌ MISSING") . "</li>";
echo "<li>Default cover: " . ($defaultCover) . " - " . (file_exists($defaultCover) ? "✓ EXISTS" : "❌ MISSING") . "</li>";
echo "</ul>";

echo "<h2>UploadManager Class:</h2>";
try {
    $uploadManager = new UploadManager();
    echo "✓ UploadManager class loaded successfully<br>";
    
    // Test image methods
    echo "Sample user profile image: " . $userManager->getProfileImage(1) . "<br>";
    echo "Sample fund cover image: " . $fundManager->getFundCoverImage(1) . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error loading UploadManager: " . $e->getMessage() . "<br>";
}

echo "<h2>UserManager and FundManager:</h2>";
try {
    $userManager = new UserManager();
    $fundManager = new FundManager();
    echo "✓ UserManager and FundManager loaded successfully<br>";
    
    // Test image methods
    echo "Sample user profile image: " . $userManager->getProfileImage(1) . "<br>";
    echo "Sample fund cover image: " . $fundManager->getFundCoverImage(1) . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error loading managers: " . $e->getMessage() . "<br>";
}
?>
