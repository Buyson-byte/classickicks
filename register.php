<?php
session_start();
include_once("connections/connection.php");
include_once("middleware/myfunctions.php");

$conn = connection();

$message = "";
$message_class = "";

// 🚫 Prevent logged-in users from accessing register page
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: home.php");
    }
    exit;
}

if (isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($username) || empty($password)) {
        $message = "All fields are required.";
        $message_class = "message-error";
    } else {
        // CHECK FOR DUPLICATE EMAIL OR USERNAME
        $check_sql = "SELECT * FROM users WHERE email = ? OR username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $email, $username);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $existing = $result->fetch_assoc();
            if ($existing['email'] === $email) {
                $message = "Email is already registered.";
            } else {
                $message = "Username is already taken.";
            }
            $message_class = "message-error";
        } else {
            // INSERT IF NO DUPLICATE
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (email, username, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                $message = "Prepare failed: " . $conn->error;
                $message_class = "message-error";
            } else {
                $stmt->bind_param("sss", $email, $username, $hashed_pass);

                if ($stmt->execute()) {
                    $message = "Registration successful. <a href='login.php'>Login here</a>";
                    $message_class = "message-success";
                } else {
                    $message = "Error: " . $stmt->error;
                    $message_class = "message-error";
                }

                $stmt->close();
            }
        }

        $check_stmt->close();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
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

        .register-container {
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

        input[type="email"],
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
            border-color: #28a745;
            outline: none;
        }

        button {
            width: 103%;
            padding: 12px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #218838;
        }

        .message-success {
            color: green;
            margin-top: 20px;
            text-align: center;
            font-weight: bold;
        }

        .message-error {
            color: red;
            margin-top: 20px;
            text-align: center;
            font-weight: bold;
        }

        p {
            text-align: center;
            margin-top: 20px;
        }

        a {
            color: #28a745;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="register-container">
    <h2>Register New Account</h2>

    <form action="" method="POST">
    <span style="text-decoration: underline;">E</span>mail:
        <input type="email" name="email" placeholder="Email" required accesskey="E"
            maxlength="50" pattern="[A-Za-z0-9]+@gmail\.com"
            title="Must be a Gmail address, max 50  characters, only letters and numbers before @gmail.com">

    <span style="text-decoration: underline;">U</span>sername:
    <input type="text" name="username" placeholder="Username" required accesskey="U"
           maxlength="12" pattern="[A-Za-z0-9_]+"
           title="Max 12 characters, only letters, numbers, and underscore">

    <span style="text-decoration: underline;">P</span>assword:
    <div style="position: relative; width: 89%;">
        <input type="password" id="password" name="password" placeholder="Password" required accesskey="P"
            minlength="8" maxlength="16"
            title="Password must be 8–16 characters"
            style="width: 100%; padding: 12px 40px 12px 15px; border: 1px solid #ccc; border-radius: 5px;">

        <span id="togglePassword" 
            style="position: absolute; right: -30px; top: 50%; transform: translateY(-50%); cursor: pointer;">
            <img src="https://cdn-icons-png.flaticon.com/512/159/159604.png" 
                alt="Show Password" 
                width="20" height="20" id="eyeIcon">
        </span>
    </div>

           
    <button type="submit" name="submit" accesskey="R">
        <span style="text-decoration: underline;">R</span>egister
    </button>

    <?php if (!empty($message)): ?>
        <div class="<?php echo $message_class; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <p style="text-align:center; margin-top:15px;">
        Already have an account? 
        <a href="login.php" accesskey="S"><span style="text-decoration: underline;">S</span>ign in</a>
    </p>
    </form>


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