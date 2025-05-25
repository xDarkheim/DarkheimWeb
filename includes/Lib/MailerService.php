<?php
namespace App\Lib;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class MailerService {
    private PHPMailer $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true);

        if (!defined('MAIL_ENABLED') || MAIL_ENABLED === false) {
            return;
        }

        if (!defined('MAIL_ENABLED') || MAIL_ENABLED === false) {
            return;
        }

        try {
            $this->mailer->isSMTP();
            $this->mailer->Host       = MAIL_HOST;
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = MAIL_USERNAME;
            $this->mailer->Password   = MAIL_PASSWORD;
            $this->mailer->SMTPSecure = MAIL_ENCRYPTION === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port       = MAIL_PORT;

            $this->mailer->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);

        } catch (PHPMailerException $e) {
            error_log("MailerService PHPMailerException during construction: {$this->mailer->ErrorInfo}");
        }
    }

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $plainBody = ''): bool {
        if (!defined('MAIL_ENABLED') || MAIL_ENABLED === false) {
            error_log("Mail sending skipped: MAIL_ENABLED is false.");
            return true; 
        }

        try {
            $this->mailer->addAddress($toEmail, $toName);

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $htmlBody;
            if (!empty($plainBody)) {
                $this->mailer->AltBody = $plainBody;
            } else {
                $this->mailer->AltBody = strip_tags(str_replace("<br>", "\n", $htmlBody));
            }

            $this->mailer->send();
            return true;
        } catch (PHPMailerException $e) {
            error_log("Message could not be sent. Mailer Error: {$this->mailer->ErrorInfo}");
            return false;
        } finally {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments(); 
        }
    }

    public function renderTemplate(string $templateName, array $data = []): string {
        $templatePath = ROOT_PATH . DS . 'includes' . DS . 'view' . DS . 'emails' . DS . $templateName . '.php';
        if (file_exists($templatePath)) {
            extract($data);
            ob_start();
            include $templatePath;
            return ob_get_clean();
        }
        error_log("Email template not found: " . $templatePath);
        return "";
    }
}