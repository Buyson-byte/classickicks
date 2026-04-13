<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data safely
    $name    = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email   = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

    // Validate required fields
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        header("Location: contact.php?status=error&msg=Please fill in all fields.");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: contact.php?status=error&msg=Invalid email address.");
        exit;
    }

    // Email setup
    $to = "your-email@example.com"; // 🔧 replace with your email
    $full_subject = "New Contact Form Message: " . $subject;
    $body = "You received a new message from your website:\n\n"
          . "Name: $name\n"
          . "Email: $email\n"
          . "Subject: $subject\n\n"
          . "Message:\n$message\n";

    $headers = "From: $email\r\nReply-To: $email";

    // Try sending the email
    if (mail($to, $full_subject, $body, $headers)) {
        header("Location: contact.php?status=success&msg=Thank you for your message. We'll get back to you soon!");
    } else {
        header("Location: contact.php?status=error&msg=Sorry, something went wrong. Please try again later.");
    }
    exit;
} else {
    // If accessed directly
    header("Location: contact.php");
    exit;
}
