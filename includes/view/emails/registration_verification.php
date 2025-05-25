<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email Address</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .button { display: inline-block; padding: 10px 20px; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 3px; }
        .footer { margin-top: 20px; font-size: 0.9em; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to <?php echo htmlspecialchars($siteName); ?>!</h1>
        <p>Hello <?php echo htmlspecialchars($username); ?>,</p>
        <p>Thank you for registering. Please click the button below to verify your email address and activate your account:</p>
        <p><a href="<?php echo htmlspecialchars($verificationLink); ?>" class="button">Verify Email Address</a></p>
        <p>If you cannot click the button, please copy and paste the following link into your browser:</p>
        <p><?php echo htmlspecialchars($verificationLink); ?></p>
        <p>This link will expire in 24 hours.</p>
        <p>If you did not create an account, no further action is required.</p>
        <div class="footer">
            <p>Regards,<br>The <?php echo htmlspecialchars($siteName); ?> Team</p>
        </div>
    </div>
</body>
</html>