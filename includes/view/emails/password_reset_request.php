<?php
$username = $username ?? 'User';
$siteName = $siteName ?? 'Your Website';
$reset_link = $reset_link ?? '#'; // Important: make sure this link is properly escaped before passing it to the template

// --- Text version (remains unchanged) ---
$textBody = "Hello, " . htmlspecialchars($username) . ",\n\n";
$textBody .= "You (or someone else) requested a password reset for your account on " . htmlspecialchars($siteName) . ".\n";
$textBody .= "If this was not you, please ignore this email.\n\n";
$textBody .= "To reset your password, please click the following link:\n";
$textBody .= $reset_link . "\n\n"; // For the text version, htmlspecialchars is not needed for the link itself
$textBody .= "This link will be valid for 1 hour.\n\n";
$textBody .= "Regards,\n";
$textBody .= "The " . htmlspecialchars($siteName) . " Team";

// --- HTML version with design ---

// Style variables for convenience
$fontFamily = "Arial, 'Helvetica Neue', Helvetica, sans-serif";
$backgroundColor = "#f4f4f4";
$containerBackgroundColor = "#ffffff";
$textColor = "#333333";
$linkColor = "#007bff"; // Blue color for links
$buttonBackgroundColor = "#007bff";
$buttonTextColor = "#ffffff";

$htmlBody = "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Password Reset Request</title>
    <style type=\"text/css\">
        /* General styles for the email body */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: {$backgroundColor}; font-family: {$fontFamily}; }

        /* Styles for the email container */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: {$containerBackgroundColor};
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .content p {
            color: {$textColor};
            line-height: 1.6;
            margin: 10px 0;
        }
        .content a {
            color: {$linkColor};
            text-decoration: underline;
        }
        .button-link {
            display: inline-block;
            padding: 10px 20px;
            margin: 15px 0;
            background-color: {$buttonBackgroundColor};
            color: {$buttonTextColor} !important; /* !important to override link color */
            text-decoration: none !important; /* !important to override underline */
            border-radius: 5px;
            font-weight: bold;
        }
        .footer {
            font-size: 0.9em;
            color: #777777;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body style=\"background-color: {$backgroundColor}; margin: 0 !important; padding: 0 !important;\">
    <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
        <tr>
            <td align=\"center\" style=\"background-color: {$backgroundColor}; padding: 20px 0;\">
                <!--[if (gte mso 9)|(IE)]>
                <table align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" width=\"600\">
                <tr>
                <td align=\"center\" valign=\"top\" width=\"600\">
                <![endif]-->
                <div class=\"email-container\" style=\"max-width: 600px; margin: 0 auto; background-color: {$containerBackgroundColor}; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);\">
                    <div class=\"content\" style=\"color: {$textColor}; font-family: {$fontFamily}; font-size: 16px; line-height: 1.6;\">
                        <p style=\"margin: 10px 0; color: {$textColor};\">Hello, " . htmlspecialchars($username) . ",</p>
                        <p style=\"margin: 10px 0; color: {$textColor};\">You (or someone else) requested a password reset for your account on <strong style=\"color: {$textColor};\">" . htmlspecialchars($siteName) . "</strong>.</p>
                        <p style=\"margin: 10px 0; color: {$textColor};\">If this was not you, please ignore this email.</p>
                        <p style=\"margin: 10px 0; color: {$textColor};\">To reset your password, please click the button below or use the following link:</p>
                        <p style=\"text-align: center; margin: 25px 0;\">
                            <a href=\"" . htmlspecialchars($reset_link) . "\" class=\"button-link\" style=\"display: inline-block; padding: 12px 25px; background-color: {$buttonBackgroundColor}; color: {$buttonTextColor} !important; text-decoration: none !important; border-radius: 5px; font-weight: bold; font-size: 16px;\">Reset Your Password</a>
                        </p>
                        <p style=\"text-align: center; margin: 10px 0; font-size: 0.9em;\">
                            <a href=\"" . htmlspecialchars($reset_link) . "\" style=\"color: {$linkColor}; text-decoration: underline;\">" . htmlspecialchars($reset_link) . "</a>
                        </p>
                        <p style=\"margin: 10px 0; color: {$textColor};\">This link will be valid for 1 hour.</p>
                        <p style=\"margin: 10px 0; color: {$textColor};\">Regards,<br>The " . htmlspecialchars($siteName) . " Team</p>
                    </div>
                    <div class=\"footer\" style=\"font-size: 0.9em; color: #777777; text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #eeeeee;\">
                        <p style=\"margin: 5px 0;\">&copy; " . date("Y") . " " . htmlspecialchars($siteName) . ". All rights reserved.</p>
                    </div>
                </div>
                <!--[if (gte mso 9)|(IE)]>
                </td>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>";

return ['text' => $textBody, 'html' => $htmlBody];
?>