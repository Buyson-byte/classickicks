<?php
session_start();
include_once("connections/connection.php");
include_once("middleware/myfunctions.php");

$conn = connection();

$message = "";

// Prevent logged-in users from seeing login page again
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admindash.php");
    } else {
        header("Location: home.php");
    }
    exit;
}

$message = "";

if(isset($_POST['submit'])){
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if(empty($username) || empty($password)) {
        $message = "Please enter both username and password.";
    } else {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            if(password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                 $_SESSION['role'] = $row['role']; 
                 if ($row['role'] === 'admin') {
                    header("Location: admindash.php");
                } else {
                    redirect("home.php", "Logged in successfully!");
                }
                exit;
            } else {
                $message = "Invalid password.";
            }
        } else {
            $message = "Username not found.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Roboto', sans-serif;
        margin: 0;
        background: #f0f2f5;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100vh;
    }

    .login-container {
        background: #fff;
        padding: 40px 30px;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 400px;
    }

    h2 {
        text-align: center;
        margin-bottom: 25px;
        color: #333;
    }

    input[type="text"],
    input[type="password"] {
        width: 95%;
        padding: 12px 15px;
        margin: 10px 0 20px 0;
        border: 1px solid #ccc;
        border-radius: 5px;
        transition: border-color 0.3s;
    }

    input:focus {
        border-color: #007bff;
        outline: none;
    }

    button {
        width: 103%;
        padding: 12px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 5px;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    button:hover {
        background-color: #0056b3;
    }

    .message {
        color: red;
        margin-bottom: 20px;
        text-align: center;
    }

    p {
        text-align: center;
        margin-top: 20px;
    }

    a {
        color: #007bff;
        text-decoration: none;
    }

    a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>

<div class="login-container">
    <!-- Logo with redirect -->
    <div style="text-align: center; margin-bottom: 20px;">
        <a href="home.php">
            <img src="images/logowithbg.png" alt="Logo" style="height: 80px; width: auto;">
        </a>
    </div>

    <h2>Login</h2>

    <?php if(isset($message) && $message != ""): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>


    <form action="" method="post">
        <span style="text-decoration: underline;">U</span>sername:
        <input type="text" name="username" placeholder="Username" required accesskey="U">

    <span style="text-decoration: underline;">P</span>assword:
    <div style="position: relative; width: 100%;">
        <input type="password" id="password" name="password" placeholder="Password" required accesskey="P"
            style="width: 89%; padding: 12px 40px 12px 15px; border: 1px solid #ccc; border-radius: 5px;">
        
        <span id="togglePassword" 
            style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer;">
            <img src="https://cdn-icons-png.flaticon.com/512/159/159604.png" 
                alt="Show Password" 
                width="20" height="20" id="eyeIcon">
        </span>
    </div>

        

        <button type="submit" name="submit" accesskey="L"><span style="text-decoration: underline;">L</span>ogin</button>
    </form>

    <p style="text-align:center; margin-top:15px;">
        Don't have an account?
        <a href="register.php" accesskey="R"><span style="text-decoration: underline;">R</span>egister here</a>
    </p>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const togglePassword = document.getElementById("togglePassword");
    const passwordInput = document.getElementById("password");
    const eyeIcon = document.getElementById("eyeIcon");

    togglePassword.addEventListener("click", function () {
        const type = passwordInput.type === "password" ? "text" : "password";
        passwordInput.type = type;

        // Change icon depending on state
        if (type === "password") {
            eyeIcon.src = "https://cdn-icons-png.flaticon.com/512/159/159604.png"; 
        } else {
            eyeIcon.src = "https://cdn-icons-png.flaticon.com/512/709/709612.png"; 
        }
    });
});
</script>


</body>
</html>