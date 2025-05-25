<?php
$username = $username ?? 'User';
$siteName = $siteName ?? 'Your Website';
$reset_link = $reset_link ?? '#';

$textBody = "Hello, " . htmlspecialchars($username) . ",\n\n";
$textBody .= "You (or someone else) requested a password reset for your account on " . htmlspecialchars($siteName) . ".\n";
$textBody .= "If this was not you, please ignore this email.\n\n";
$textBody .= "To reset your password, please click the following link:\n";
$textBody .= $reset_link . "\n\n";
$textBody .= "This link will be valid for 1 hour.\n\n";
$textBody .= "Regards,\n";
$textBody .= "The " . htmlspecialchars($siteName) . " Team";

$htmlBody = "<p>Hello, " . htmlspecialchars($username) . ",</p>";
$htmlBody .= "<p>You (or someone else) requested a password reset for your account on <strong>" . htmlspecialchars($siteName) . "</strong>.</p>";
$htmlBody .= "<p>If this was not you, please ignore this email.</p>";
$htmlBody .= "<p>To reset your password, please click the following link:<br>";
$htmlBody .= "<a href=\"" . htmlspecialchars($reset_link) . "\">" . htmlspecialchars($reset_link) . "</a></p>";
$htmlBody .= "<p>This link will be valid for 1 hour.</p>";
$htmlBody .= "<p>Regards,<br>";
$htmlBody .= "The " . htmlspecialchars($siteName) . " Team</p>";

return ['text' => $textBody, 'html' => $htmlBody];
?>