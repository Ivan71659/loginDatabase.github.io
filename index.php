<?php
// Handle user actions (register, login)
session_start();

$db = new SQLite3 ('users.db');

$db->exec("
CREATE TABLE IF NOT EXISTS user (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE,
    password TEXT,
    email TEXT UNIQUE,
    role TEXT
)");

// Check if admin exists, if not, insert it
$stmt = $db->prepare("SELECT * FROM user WHERE username = ?");
$stmt->bindValue(1, 'admin');
$result = $stmt->execute();

if (!$result->fetchArray ()) {
    $db->exec ("INSERT INTO user (username, password, email, role) VALUES ('admin','admin123', 'admin@gmail.com', 'admin')");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action']; 

    if($action == "register"){
        $username = $_POST["username"];
        $password = $_POST["password"];
        $email = $_POST["email"];

        // check if email or username exists
        $stmt = $db->prepare("SELECT * FROM user WHERE username = ? OR email = ?");
        $stmt->bindValue(1, $username);
        $stmt->bindValue(2, $email);
        $result = $stmt->execute();

        if ($result->fetchArray()) {
            echo "<p style='color: red;'>Username or email already exists!</p>";
        } else {
            $db->exec ("INSERT INTO user (username, password, email, role) VALUES ('$username', '$password','$email', 'userrole')");
            $stmt->bindValue(1, $username);
            $stmt->bindValue(2, $email);
            $stmt->execute();
        }
    }  

    else if($action == "login"){
        $username = $_POST["username"];
        $password = $_POST["password"];

        $stmt = $db->prepare("SELECT * FROM user WHERE username = ?");
        $stmt->bindValue(1, $username);
        $result = $stmt->execute();
        $user = $result->fetchArray();

        if($user && $user['password'] == $password){
            // stores user info in session
            $_SESSION['id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['password'] = $user['password'];

            // sends user to correct page
            if ($user['role'] == 'admin') {
                header('Location: index.php?admin=true');
                exit();
            }
            else {
                header('Location: index.php?userrole=true');
                exit();
            }
        } else {
            echo "<p style='color: red;'>Invalid password!</p>";
        }
    }  

    else if($action == "passchange"){
        $newpassword = $_POST["newpassword"];

        $stmt = $db->prepare("UPDATE user SET password = ? WHERE username = ?");
        $stmt->bindValue(1, $newpassword);
        $stmt->bindValue(2, $_SESSION['username']);
        $stmt->execute();
    }
}

if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'){
    $result = $db->query ("SELECT * FROM user");

    echo "<h1>Admin Dashboard</h1>";

    echo "<h2>All Users</h2>";
    echo "<table border = '1'>";

    echo "<tr>
    <th>ID</th>
    <th>Username</th>
    <th>Password</th>
    <th>Email</th>
    </tr>";

    while ($row = $result->fetchArray (SQLITE3_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row ['id'] . "</td>";
        echo "<td>" . $row ['username'] . "</td>";
        echo "<td>" . $row ['password'] . "</td>";
        echo "<td>" . $row ['email'] . "</td>";
        echo "</tr>";
    }

    echo "</table>";

    echo "<form action='logout.php' method='POST'>
        <input type='submit' value='Logout'>
    </form>";
}
    
else if(isset($_SESSION['role']) && $_SESSION['role'] == 'user'){
    echo "<h1>Viewer Dashboard</h1>";

    echo '<form method = "post" action = "index.php">
        <input type = "hidden" name = "action" value = "passchange">

        <label>New Password: </label>
        <input type = "text" required name = "newpassword">
        <br>

        <button type = "submit">Change Password</button>
    </form>';
    echo "<form action='logout.php' method='POST'>
        <input type='submit' value='Logout'>
    </form>";
}

$db->close ();
?>

<html>
<head>
    <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
    </style>
</head>
<body> 
    <?php if (!isset($_SESSION['username'])): ?>
    
    <h1>User Authentication System</h1>
    
    <h2>Register</h2>
    <form method = "post" action = "index.php">
        <input type = "hidden" name = "action" value = "register">

        <label>Username: </label>
        <input type = "text" required name = "username">
        <br>
        <label>Password:</label>
        <input type = "password" required name = "password">
        <br>
        <label>Email:</label>
        <input type = "email" required name = "email">
        <br>

        <button type = "submit">Register Account</button>
    </form>
    
    <h2>Login</h2>
    <form method = "post" action = "index.php">
        <input type = "hidden" name = "action" value = "login">
        
        <label>Username:</label>
        <input type = "text" required name = "username">
        <br>
        <label>Password:</label>
        <input type = "password" required name = "password">
        <br>
        
        <button type = "submit">Login</button>
    </form>  

    <?php endif; ?>
</body>
</html>
