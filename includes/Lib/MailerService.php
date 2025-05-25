<?php
namespace App\Lib;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class MailerService {
    private PHPMailer $mailer;
    private string $templatePath = '';

    public function __construct() {
        $this->mailer = new PHPMailer(true);

        if (defined('MAIL_TEMPLATE_PATH')) {
            $this->templatePath = \MAIL_TEMPLATE_PATH;
        } else {
            error_log("MailerService: MAIL_TEMPLATE_PATH is not defined.");

        }

        if (!defined('MAIL_ENABLED') || \MAIL_ENABLED === false) {
            return;
        }

        try {
            $this->mailer->isSMTP();
            $this->mailer->Host       = \MAIL_HOST;
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = \MAIL_USERNAME;
            $this->mailer->Password   = \MAIL_PASSWORD;

            $encryption = '';
            if (defined('MAIL_ENCRYPTION')) {
                $encryption = strtolower(\MAIL_ENCRYPTION);
            }

            if ($encryption === 'ssl') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            $this->mailer->Port       = \MAIL_PORT;

            $this->mailer->setFrom(\MAIL_FROM_ADDRESS, \MAIL_FROM_NAME);

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
            error_log("MailerService PHPMailerException during send: {$this->mailer->ErrorInfo}");
            return false;
        } finally {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
        }
    }


    public function renderTemplate(string $templateName, array $data): ?array
    {
        if (empty($this->templatePath)) {
            error_log("MailerService: templatePath is not set.");
            return null;
        }

        $templateFile = rtrim($this->templatePath, '/') . '/' . $templateName . '.php';

        if (!file_exists($templateFile)) {
            error_log("MailerService: Email template file not found: " . $templateFile);
            return null;
        }

        extract($data);

        ob_start();
        $email_content = include $templateFile;
        ob_end_clean();

        if (is_array($email_content) && isset($email_content['text']) && isset($email_content['html'])) {
            return $email_content;
        } else {
            error_log("MailerService: Template '$templateFile' did not return the expected array structure with 'text' and 'html' keys. Returned: " . print_r($email_content, true));
            return null;
        }
    }
}