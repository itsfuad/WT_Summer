<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$db_password = '';
$database = 'lab2';

try {
// First, connect without selecting a database to create it
    echo "<p>1. Connecting to MySQL server...</p>\n";
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    echo "<p>2. Creating database...</p>\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database");
    $pdo->exec("USE $database");
    echo "<span style='color:green;'>✓ Database created/connected</span><br>\n";

    // create table if not exists
    $createTableSQL = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        age INT NOT NULL,
        gender ENUM('male', 'female', 'other') NOT NULL
    )";

    $pdo->exec($createTableSQL);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Basic CRUD Operation</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new user
    if (isset($_POST['add'])) {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $age = $_POST['age'] ?? '';
        $gender = $_POST['gender'] ?? '';
        if ($name && $email && $age && $gender) {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, age, gender) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $age, $gender]);
            echo "<span style='color:green;'>User added!</span><br>";
        }
    }
    // Update user
    if (isset($_POST['update'])) {
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $age = $_POST['age'] ?? '';
        $gender = $_POST['gender'] ?? '';
        if ($id && $name && $email && $age && $gender) {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, age=?, gender=? WHERE id=?");
            $stmt->execute([$name, $email, $age, $gender, $id]);
            echo "<span style='color:green;'>User updated!</span><br>";
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$id]);
    echo "<span style='color:red;'>User deleted!</span><br>";
}

// Handle edit form
$editUser = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$id]);
    $editUser = $stmt->fetch();
}
?>

<h2><?php echo $editUser ? "Edit User" : "Add New User"; ?></h2>
<form method="post">
    <?php if ($editUser): ?>
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($editUser['id']); ?>">
    <?php endif; ?>
    <input type="text" name="name" placeholder="Name" required value="<?php echo $editUser ? htmlspecialchars($editUser['name']) : ''; ?>">
    <input type="email" name="email" placeholder="Email" required value="<?php echo $editUser ? htmlspecialchars($editUser['email']) : ''; ?>">
    <input type="number" name="age" placeholder="Age" required value="<?php echo $editUser ? htmlspecialchars($editUser['age']) : ''; ?>">
    <select name="gender" required>
        <option value="">Select Gender</option>
        <option value="male" <?php echo ($editUser && $editUser['gender']=='male') ? 'selected' : ''; ?>>Male</option>
        <option value="female" <?php echo ($editUser && $editUser['gender']=='female') ? 'selected' : ''; ?>>Female</option>
        <option value="other" <?php echo ($editUser && $editUser['gender']=='other') ? 'selected' : ''; ?>>Other</option>
    </select>
    <button type="submit" name="<?php echo $editUser ? 'update' : 'add'; ?>">
        <?php echo $editUser ? 'Update' : 'Add'; ?>
    </button>
    <?php if ($editUser): ?>
        <a href="index.php">Cancel</a>
    <?php endif; ?>
</form>

<h2>User List</h2>
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>ID</th><th>Name</th><th>Email</th><th>Age</th><th>Gender</th><th>Actions</th>
    </tr>
    <?php
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
    foreach ($stmt as $row):
    ?>
    <tr>
        <td><?php echo htmlspecialchars($row['id']); ?></td>
        <td><?php echo htmlspecialchars($row['name']); ?></td>
        <td><?php echo htmlspecialchars($row['email']); ?></td>
        <td><?php echo htmlspecialchars($row['age']); ?></td>
        <td><?php echo htmlspecialchars($row['gender']); ?></td>
        <td>
            <a href="?edit=<?php echo $row['id']; ?>">Edit</a>
            <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this user?');">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>