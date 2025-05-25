<?php
$username = $username ?? 'User';
$siteName = $siteName ?? 'Our Website';
$changeDateTime = $changeDateTime ?? date('Y-m-d H:i:s'); // Current time by default
// $changeIpAddress = $changeIpAddress ?? null; // Optional

// --- Text version ---
$textBody = "Hello " . htmlspecialchars($username) . ",\n\n";
$textBody .= "This is a notification that the password for your account on " . htmlspecialchars($siteName) . " was recently changed.\n\n";
$textBody .= "Date and Time of Change: " . htmlspecialchars($changeDateTime) . "\n";
// if ($changeIpAddress) {
//     $textBody .= "Changed from IP Address: " . htmlspecialchars($changeIpAddress) . "\n";
// }
$textBody .= "\nIf you made this change, you can safely ignore this email.\n\n";
$textBody .= "If you did NOT make this change, please secure your account immediately. You can try to reset your password using the 'Forgot Password' link on our login page, or contact our support team if you suspect unauthorized access.\n\n";
$textBody .= "Login Page: " . (defined('SITE_URL') ? rtrim(SITE_URL, '/') . '/index.php?page=login' : '#') . "\n";
// $textBody .= "Support Contact: " . (defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'support@example.com') . "\n\n"; // Add your support email
$textBody .= "Regards,\n";
$textBody .= "The " . htmlspecialchars($siteName) . " Team";

// --- HTML version with design (similar to other email templates) ---
$fontFamily = "Arial, 'Helvetica Neue', Helvetica, sans-serif";
$backgroundColor = "#f4f4f4";
$containerBackgroundColor = "#ffffff";
$textColor = "#333333";
$linkColor = "#007bff";
$warningColor = "#dc3545"; // Red for warnings

$htmlBody = "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Password Change Notification - " . htmlspecialchars($siteName) . "</title>
    <style type=\"text/css\">
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: {$backgroundColor}; font-family: {$fontFamily}; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: {$containerBackgroundColor}; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .content h1 { color: {$textColor}; font-size: 24px; margin-bottom: 20px; }
        .content p { color: {$textColor}; line-height: 1.6; margin: 10px 0; font-size: 16px; }
        .content a { color: {$linkColor}; text-decoration: underline; }
        .warning-text { color: {$warningColor}; font-weight: bold; }
        .footer { font-size: 0.9em; color: #777777; text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #eeeeee; }
    </style>
</head>
<body style=\"background-color: {$backgroundColor}; margin: 0 !important; padding: 0 !important;\">
    <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
        <tr>
            <td align=\"center\" style=\"background-color: {$backgroundColor}; padding: 20px 0;\">
                <!--[if (gte mso 9)|(IE)]>
                <table align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" width=\"600\"><tr><td align=\"center\" valign=\"top\" width=\"600\">
                <![endif]-->
                <div class=\"email-container\" style=\"max-width: 600px; margin: 0 auto; background-color: {$containerBackgroundColor}; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);\">
                    <div class=\"content\" style=\"color: {$textColor}; font-family: {$fontFamily}; font-size: 16px; line-height: 1.6;\">
                        <h1 style=\"color: {$textColor}; font-size: 24px; margin-bottom: 20px; text-align: center;\">Password Change Notification</h1>
                        <p>Hello " . htmlspecialchars($username) . ",</p>
                        <p>This email is to confirm that the password for your account on <strong>" . htmlspecialchars($siteName) . "</strong> was recently changed.</p>
                        <p><strong>Date and Time of Change:</strong> " . htmlspecialchars($changeDateTime) . "</p>";
// if ($changeIpAddress) {
//     $htmlBody .= "<p><strong>Changed from IP Address:</strong> " . htmlspecialchars($changeIpAddress) . "</p>";
// }
$htmlBody .= "          <p>If you made this change, you can safely ignore this email.</p>
                        <p class=\"warning-text\">If you did NOT make this change, please secure your account immediately. You can try to reset your password using the 'Forgot Password' link on our login page, or contact our support team if you suspect unauthorized access.</p>
                        <p>You can access our login page here: <a href=\"" . (defined('SITE_URL') ? rtrim(SITE_URL, '/') . '/index.php?page=login' : '#') . "\" style=\"color: {$linkColor}; text-decoration: underline;\">Login Page</a></p>";
// $htmlBody .= "        <p>For support, please contact: <a href=\"mailto:" . (defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'support@example.com') . "\" style=\"color: {$linkColor}; text-decoration: underline;\">" . (defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'support@example.com') . "</a></p>";
$htmlBody .= "          <p style=\"margin: 20px 0 10px 0;\">Regards,<br>The " . htmlspecialchars($siteName) . " Team</p>
                    </div>
                    <div class=\"footer\" style=\"font-size: 0.9em; color: #777777; text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #eeeeee;\">
                        <p style=\"margin: 5px 0;\">&copy; " . date("Y") . " " . htmlspecialchars($siteName) . ". All rights reserved.</p>
                    </div>
                </div>
                <!--[if (gte mso 9)|(IE)]>
                </td></tr></table>
                <![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>";

return ['text' => $textBody, 'html' => $htmlBody];
?>