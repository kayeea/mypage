<?php
// contact.php
// Accepts POST (form-data or JSON), validates input, saves submission to contacts.json, and attempts to send an email.
// Returns JSON responses for AJAX clients.
// IMPORTANT: tweak $ownerEmail and mail configuration to match your hosting environment.

header('Content-Type: application/json; charset=utf-8');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Read input (supports JSON payloads and form POSTs)
$input = null;
$contentType = isset($_SERVER['CONTENT_TYPE']) ? strtolower(trim($_SERVER['CONTENT_TYPE'])) : '';

if (strpos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) $input = [];
} else {
    // fallback to $_POST (FormData or application/x-www-form-urlencoded)
    $input = $_POST;
}

// Helper: get value or empty string
function val($arr, $key) {
    return isset($arr[$key]) ? trim($arr[$key]) : '';
}

// NOTE: If your HTML checkboxes don't have name attributes, add them:
// <input type="checkbox" id="privacy" name="privacy" required>
// <input type="checkbox" id="terms" name="terms" required>
$name = val($input, 'name');
$email = val($input, 'email');
$subject = val($input, 'subject');
$message = val($input, 'message');
$privacy = isset($input['privacy']) ? $input['privacy'] : null; // may be "on" or "true" depending on client
$terms = isset($input['terms']) ? $input['terms'] : null;

// Basic validation
$errors = [];

if ($name === '') $errors[] = 'Name is required';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required';
if ($subject === '') $errors[] = 'Subject is required';
if ($message === '' || strlen($message) < 5) $errors[] = 'Message is required (at least 5 characters)';
if ($privacy === null) $errors[] = 'You must agree to the Privacy Policy';
if ($terms === null) $errors[] = 'You must agree to the Terms & Conditions';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'errors' => $errors]);
    exit;
}

// Sanitize for storage
$entry = [
    'name' => htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
    'email' => $email,
    'subject' => htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
    'message' => htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
    'privacy' => $privacy ? true : false,
    'terms' => $terms ? true : false,
    'createdAt' => gmdate('c')
];

// Save to JSON file (append safely)
$dataFile = __DIR__ . '/contacts.json';
$maxRetries = 3;
$written = false;
for ($i = 0; $i < $maxRetries; $i++) {
    $fp = @fopen($dataFile, 'c+');
    if (!$fp) break;
    if (flock($fp, LOCK_EX)) {
        // read current content
        $contents = stream_get_contents($fp);
        $arr = [];
        if ($contents) {
            $arr = json_decode($contents, true);
            if (!is_array($arr)) $arr = [];
        }
        $arr[] = $entry;
        // rewind and truncate
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        $written = true;
        break;
    } else {
        fclose($fp);
        usleep(100000); // wait 100ms then retry
    }
}

if (!$written) {
    // non-fatal: continue but inform client
    error_log('Could not write to contacts.json');
}

// Send email to site owner (simple mail()). Adjust $ownerEmail and headers as needed.
$ownerEmail = 'kaye.eloisa@hotmail.com'; // <-- CHANGE this to your real email before deploying
$subjectLine = '[Contact Form] ' . $entry['subject'];
$emailBody = "New contact form submission:\n\n"
    . "Name: {$entry['name']}\n"
    . "Email: {$entry['email']}\n"
    . "Subject: {$entry['subject']}\n"
    . "Message:\n{$entry['message']}\n\n"
    . "Sent at: {$entry['createdAt']}\n";

$headers = "From: {$entry['name']} <{$entry['email']}>\r\n";
$headers .= "Reply-To: {$entry['email']}\r\n";
$headers .= "Content-Type: text/plain; charset=utf-8\r\n";

$mailSent = false;
try {
    // PHP's mail() might be disabled on some hosts; check your host's docs.
    $mailSent = @mail($ownerEmail, $subjectLine, $emailBody, $headers);
} catch (Exception $e) {
    error_log('Mail error: ' . $e->getMessage());
    $mailSent = false;
}

// Response
http_response_code(200);
if ($mailSent) {
    echo json_encode(['ok' => true, 'message' => 'Message sent — thank you!']);
} else {
    // still OK because saved to file; warn user that email failed (you can still process submissions from contacts.json)
    echo json_encode(['ok' => true, 'message' => 'Message saved. Email could not be sent from this server; please check mail settings.']);
}

