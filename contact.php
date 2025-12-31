<?php
// contact.php

header("Content-Type: application/json");

// Allow only POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

// Helper function to prevent header injection
function clean_input($str) {
    $str = trim($str);
    $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    return str_replace(["\r", "\n"], '', $str);
}

// Sanitize input
$name    = clean_input($_POST["name"] ?? "");
$email   = filter_var($_POST["email"] ?? "", FILTER_SANITIZE_EMAIL);
$subject = clean_input($_POST["subject"] ?? "");
$message = htmlspecialchars(trim($_POST["message"] ?? ""), ENT_QUOTES, 'UTF-8');

// Validate required fields
if (!$name || !$email || !$subject || !$message) {
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Invalid email format"]);
    exit;
}

// Email settings
$to = "kaye.eloisa@hotmail.com"; // Change this to your email

$headers  = "From: $name <$email>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$body = "Name: $name\n";
$body .= "Email: $email\n\n";
$body .= "Message:\n$message\n";

// Send email
if (mail($to, $subject, $body, $headers)) {
    echo json_encode(["status" => "success", "message" => "Message sent successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to send message"]);
}
?>
