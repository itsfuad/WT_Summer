<?php
require_once __DIR__ . '/env.php';

EnvReader::load();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test ENV</title>
</head>
<body>
    <?php
        foreach (EnvReader::all() as $key => $value) {
            echo "<p><strong>$key:</strong> $value</p>\n";
        }
    ?>
</body>
</html>