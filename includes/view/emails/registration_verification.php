<?php
// Ensure variables are set, providing defaults if not
$username = $username ?? 'User';
$siteName = $siteName ?? 'Our Website';
$verificationLink = $verificationLink ?? '#'; // Important: ensure this link is properly generated and escaped before passing

// --- Text version ---
$textBody = "Hello " . htmlspecialchars($username) . ",\n\n";
$textBody .= "Welcome to " . htmlspecialchars($siteName) . "!\n";
$textBody .= "Thank you for registering. Please verify your email address to activate your account.\n\n";
$textBody .= "Click the following link or copy and paste it into your browser:\n";
$textBody .= $verificationLink . "\n\n";
$textBody .= "This link will expire in 24 hours.\n\n";
$textBody .= "If you did not create an account, no further action is required.\n\n";
$textBody .= "Regards,\n";
$textBody .= "The " . htmlspecialchars($siteName) . " Team";

// --- HTML version with design ---

// Style variables for convenience
$fontFamily = "Arial, 'Helvetica Neue', Helvetica, sans-serif";
$backgroundColor = "#f4f4f4"; // Light grey background for the email body
$containerBackgroundColor = "#ffffff"; // White background for the content area
$textColor = "#333333"; // Dark grey for text
$linkColor = "#007bff"; // Standard blue for links
$buttonBackgroundColor = "#007bff"; // Blue for buttons
$buttonTextColor = "#ffffff"; // White text for buttons

$htmlBody = "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Verify Your Email Address - " . htmlspecialchars($siteName) . "</title>
    <style type=\"text/css\">
        /* Basic Resets */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: {$backgroundColor}; font-family: {$fontFamily}; }

        /* Email Container */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: {$containerBackgroundColor};
            padding: 20px; /* Default padding */
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        /* Content Styles */
        .content h1 {
            color: {$textColor};
            font-size: 24px;
            margin-bottom: 20px;
        }
        .content p {
            color: {$textColor};
            line-height: 1.6;
            margin: 10px 0;
            font-size: 16px;
        }
        .content a {
            color: {$linkColor};
            text-decoration: underline;
        }
        /* Button Styles */
        .button-link {
            display: inline-block;
            padding: 10px 20px;
            margin: 15px 0;
            background-color: {$buttonBackgroundColor};
            color: {$buttonTextColor} !important; /* Important to override link color */
            text-decoration: none !important; /* Important to override link underline */
            border-radius: 5px;
            font-weight: bold;
        }
        /* Footer Styles */
        .footer {
            font-size: 0.9em;
            color: #777777;
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eeeeee;
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
                        <h1 style=\"color: {$textColor}; font-size: 24px; margin-bottom: 20px; text-align: center;\">Welcome to " . htmlspecialchars($siteName) . "!</h1>
                        <p style=\"margin: 10px 0; color: {$textColor};\">Hello " . htmlspecialchars($username) . ",</p>
                        <p style=\"margin: 10px 0; color: {$textColor};\">Thank you for registering. Please click the button below to verify your email address and activate your account:</p>
                        <p style=\"text-align: center; margin: 25px 0;\">
                            <a href=\"" . htmlspecialchars($verificationLink) . "\" class=\"button-link\" style=\"display: inline-block; padding: 12px 25px; background-color: {$buttonBackgroundColor}; color: {$buttonTextColor} !important; text-decoration: none !important; border-radius: 5px; font-weight: bold; font-size: 16px;\">Verify Email Address</a>
                        </p>
                        <p style=\"margin: 10px 0; color: {$textColor}; font-size: 0.9em; text-align: center;\">If you cannot click the button, please copy and paste this link into your browser:</p>
                        <p style=\"text-align: center; margin: 5px 0 20px 0; font-size: 0.9em;\">
                            <a href=\"" . htmlspecialchars($verificationLink) . "\" style=\"color: {$linkColor}; text-decoration: underline;\">" . htmlspecialchars($verificationLink) . "</a>
                        </p>
                        <p style=\"margin: 10px 0; color: {$textColor};\">This link will expire in 24 hours.</p>
                        <p style=\"margin: 10px 0; color: {$textColor};\">If you did not create an account, no further action is required.</p>
                        <p style=\"margin: 20px 0 10px 0; color: {$textColor};\">Regards,<br>The " . htmlspecialchars($siteName) . " Team</p>
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