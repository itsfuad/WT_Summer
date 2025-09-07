<?php
require_once 'session.php';

// Logout the user
logoutUser();

// Redirect to home page
header('Location: ../../home/view/index.php');
exit();
?>
