<?php

if ($_SERVER[“REQUEST_METHOD”] == “POST”) {

    $name = trim($_POST[“name”]);

    $email = trim($_POST[“email”]);

    $message = trim($_POST[“message”]);

    $errors = [];

    if (empty($name)) {

        $errors[] = “Please enter your name.”;

    }

    if (empty($email)) {

        $errors[] = “Please enter your email address.”;

     elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = “The email address is not valid.”;

    if (empty($message)) {

        $errors[] = “Please enter your message.”;

    if (empty($errors)) {

        $to = “your-email@example.com”;

        $subject = “New Contact Form Submission”;

        1st. $body = “Name: $name\n”;

        2nd. $body .= “Email: $email\n”;

        3rd. $body .= “Message:\n$message\n”;

        $headers = “From: $email\r\n”;

        $headers .= “Reply-To: $email\r\n”;

        if (mail($to, $subject, $body, $headers)) {

            echo “<p>Your message was sent successfully!</p>”;

         else {

            echo “<p>There was a problem sending your message. Please try again later.</p>”;

     else {

        foreach ($errors as $error) {

            echo “<p>$error</p>”;

}

?>