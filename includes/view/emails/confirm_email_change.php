<?php
// Ensure variables are set, providing defaults if not
$username = $username ?? 'User';
$siteName = $siteName ?? 'Our Website';
$newEmailAddress = $newEmailAddress ?? 'your new email address';
$confirmationLink = $confirmationLink ?? '#';

// --- Text version ---
$textBody = "Hello " . htmlspecialchars($username) . ",\n\n";
$textBody .= "You recently requested to change the email address associated with your account on " . htmlspecialchars($siteName) . " to " . htmlspecialchars($newEmailAddress) . ".\n\n";
$textBody .= "To confirm this change, please click the following link or copy and paste it into your browser:\n";
$textBody .= $confirmationLink . "\n\n";
$textBody .= "This link will expire in 1 hour.\n\n";
$textBody .= "If you did not request this change, please ignore this email. Your current email address will remain unchanged.\n\n";
$textBody .= "Regards,\n";
$textBody .= "The " . htmlspecialchars($siteName) . " Team";

// --- HTML version with design (similar to registration_verification.php) ---

$fontFamily = "Arial, 'Helvetica Neue', Helvetica, sans-serif";
$backgroundColor = "#f4f4f4";
$containerBackgroundColor = "#ffffff";
$textColor = "#333333";
$linkColor = "#007bff";
$buttonBackgroundColor = "#007bff";
$buttonTextColor = "#ffffff";

$htmlBody = "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Confirm Your New Email Address - " . htmlspecialchars($siteName) . "</title>
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
        .button-link { display: inline-block; padding: 10px 20px; margin: 15px 0; background-color: {$buttonBackgroundColor}; color: {$buttonTextColor} !important; text-decoration: none !important; border-radius: 5px; font-weight: bold; }
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
                        <h1 style=\"color: {$textColor}; font-size: 24px; margin-bottom: 20px; text-align: center;\">Confirm Your New Email Address</h1>
                        <p>Hello " . htmlspecialchars($username) . ",</p>
                        <p>You recently requested to change the email address associated with your account on <strong>" . htmlspecialchars($siteName) . "</strong> to <strong>" . htmlspecialchars($newEmailAddress) . "</strong>.</p>
                        <p>To confirm this change and start using your new email address for this account, please click the button below:</p>
                        <p style=\"text-align: center; margin: 25px 0;\">
                            <a href=\"" . htmlspecialchars($confirmationLink) . "\" class=\"button-link\" style=\"display: inline-block; padding: 12px 25px; background-color: {$buttonBackgroundColor}; color: {$buttonTextColor} !important; text-decoration: none !important; border-radius: 5px; font-weight: bold; font-size: 16px;\">Confirm New Email Address</a>
                        </p>
                        <p style=\"text-align: center; margin: 10px 0; font-size: 0.9em;\">If you cannot click the button, please copy and paste this link into your browser:</p>
                        <p style=\"text-align: center; margin: 5px 0 20px 0; font-size: 0.9em;\">
                            <a href=\"" . htmlspecialchars($confirmationLink) . "\" style=\"color: {$linkColor}; text-decoration: underline;\">" . htmlspecialchars($confirmationLink) . "</a>
                        </p>
                        <p>This confirmation link will expire in 1 hour.</p>
                        <p>If you did not request this change, please ignore this email. No changes will be made to your account.</p>
                        <p style=\"margin: 20px 0 10px 0;\">Regards,<br>The " . htmlspecialchars($siteName) . " Team</p>
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