<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile - CrowdFund</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../shared/css/profile_manager.css">
</head>
<body>
    <?php
    require_once '../../shared/includes/profile_manager.php';
    
    renderProfileForm([
        'user' => $fullUser,
        'errors' => $errors,
        'success' => $success,
        'showNameField' => true,
        'backUrl' => 'index.php',
        'formAction' => ''
    ]);
    ?>
</body>
</html>
