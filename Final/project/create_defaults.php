<?php
/**
 * Create default profile and cover images
 */

// Create default profile image (100x100)
$profile_img = imagecreate(100, 100);
$bg_color = imagecolorallocate($profile_img, 108, 117, 125); // Bootstrap secondary color
$text_color = imagecolorallocate($profile_img, 255, 255, 255);

// Add text
$font_size = 3;
$text = "Profile";
$text_x = (100 - strlen($text) * imagefontwidth($font_size)) / 2;
$text_y = (100 - imagefontheight($font_size)) / 2;
imagestring($profile_img, $font_size, $text_x, $text_y, $text, $text_color);

// Save profile image
imagepng($profile_img, 'uploads/profiles/default-profile.png');
imagedestroy($profile_img);

// Create default cover image (800x400)
$cover_img = imagecreate(800, 400);
$bg_color = imagecolorallocate($cover_img, 52, 58, 64); // Bootstrap dark color
$text_color = imagecolorallocate($cover_img, 255, 255, 255);

// Add text
$font_size = 5;
$text = "Campaign Cover";
$text_x = (800 - strlen($text) * imagefontwidth($font_size)) / 2;
$text_y = (400 - imagefontheight($font_size)) / 2;
imagestring($cover_img, $font_size, $text_x, $text_y, $text, $text_color);

// Save cover image
imagepng($cover_img, 'uploads/covers/default-cover.png');
imagedestroy($cover_img);

echo "Default images created successfully!\n";
echo "- default-profile.png (100x100)\n";
echo "- default-cover.png (800x400)\n";
?>
