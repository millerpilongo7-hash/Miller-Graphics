<?php
/**
 * contact.php
 * Handles the portfolio contact form submission and emails it to the site owner.
 *
 * Requirements:
 * - Must run on a server with PHP (and a working mail transport — most shared
 *   hosts like GoDaddy, Hostinger, Namecheap etc. have PHP's mail() configured
 *   out of the box). This will NOT work by just double-clicking the HTML file;
 *   it needs to be uploaded to a real PHP-capable web host.
 * - Keep this file in the SAME folder as portfolio.html.
 */

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// --- Where the message should be delivered ---
$recipientEmail = 'miller.pilongo7@gmail.com';

// --- Honeypot spam trap (invisible field bots tend to fill in) ---
if (!empty($_POST['website'] ?? '')) {
    // Silently pretend success so bots don't learn the trap worked
    echo json_encode(['success' => true]);
    exit;
}

// --- Collect + sanitize input ---
$name    = trim(strip_tags($_POST['name'] ?? ''));
$email   = trim($_POST['email'] ?? '');
$subject = trim(strip_tags($_POST['subject'] ?? ''));
$message = trim(strip_tags($_POST['message'] ?? ''));

// --- Validate ---
$errors = [];
if ($name === '') $errors[] = 'Name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
if ($subject === '') $errors[] = 'Subject is required.';
if (mb_strlen($message) < 10) $errors[] = 'Message should be at least 10 characters.';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// --- Build the email ---
$emailSubject = "Portfolio Contact: {$subject}";

$emailBody = "You received a new message from your portfolio contact form.\n\n"
    . "Name:    {$name}\n"
    . "Email:   {$email}\n"
    . "Subject: {$subject}\n\n"
    . "Message:\n{$message}\n";

// Use a safe From address on your own domain if you have one (improves deliverability).
// Replying to this email will go to the visitor's address via Reply-To.
$fromAddress = 'no-reply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost');

$headers = [];
$headers[] = "From: Portfolio Contact Form <{$fromAddress}>";
$headers[] = "Reply-To: {$name} <{$email}>";
$headers[] = "Content-Type: text/plain; charset=UTF-8";
$headers[] = "X-Mailer: PHP/" . phpversion();

$sent = @mail($recipientEmail, $emailSubject, $emailBody, implode("\r\n", $headers));

if ($sent) {
    echo json_encode(['success' => true, 'message' => "Message sent — I'll get back to you soon!"]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "The server couldn't send the email. If this keeps happening, your host's mail() function may need SMTP configuration."
    ]);
}
