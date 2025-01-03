<?php

namespace App;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class EmailService
{
    private $mailer;
    private $config;
    private $templatePath;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->templatePath = __DIR__ . '/templates/';

        $this->initializeMailer();
    }

    private function initializeMailer()
    {
        $this->mailer = new PHPMailer(true);

        // Server settings
        $this->mailer->isSMTP();
        $this->mailer->Host = $this->config['smtp']['host'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $this->config['smtp']['username'];
        $this->mailer->Password = $this->config['smtp']['password'];
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = $this->config['smtp']['port'];

        // Default settings
        $this->mailer->isHTML(true);
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->setFrom(
            $this->config['from']['email'],
            $this->config['from']['name']
        );

        // Anti-spam settings
        $this->mailer->XMailer = ' '; // Hide X-Mailer header
        $this->mailer->addCustomHeader('Precedence', 'bulk');
        $this->mailer->addCustomHeader('Auto-Submitted', 'auto-generated');
    }

    public function sendPaymentNotification(array $paymentData): bool
    {
        try {
            $template = $this->getEmailTemplate($paymentData['status']);
            $emailContent = $this->prepareEmailContent($template, $paymentData);

            $this->mailer->clearAddresses();
            $this->mailer->addAddress($paymentData['email'], $paymentData['name']);
            $this->mailer->Subject = $this->getSubject($paymentData);
            $this->mailer->Body = $emailContent['html'];
            $this->mailer->AltBody = $emailContent['text'];

            // Add payment receipt as PDF for successful payments
            if ($paymentData['status'] === 'success') {
                $this->addPaymentReceipt($paymentData);
            }

            $sent = $this->mailer->send();
            $this->logEmailActivity('success', $paymentData);
            return $sent;

        } catch (PHPMailerException $e) {
            $this->logEmailActivity('error', $paymentData, $e->getMessage());
            throw new Exception("Failed to send payment notification: " . $e->getMessage());
        }
    }

    private function getEmailTemplate(string $status): string
    {
        $templateFile = match ($status) {
            'success' => 'payment_success.html',
            'failed' => 'payment_failed.html',
            'pending' => 'payment_pending.html',
            default => throw new Exception("Invalid payment status: $status")
        };

        $templatePath = $this->templatePath . $templateFile;
        if (!file_exists($templatePath)) {
            throw new Exception("Email template not found: $templateFile");
        }

        return file_get_contents($templatePath);
    }

    private function prepareEmailContent(string $template, array $data): array
    {
        // Replace placeholders in template
        $replacements = [
            '{{name}}' => htmlspecialchars($data['name']),
            '{{amount}}' => number_format($data['amount'], 2),
            '{{currency}}' => htmlspecialchars($data['currency']),
            '{{payment_id}}' => htmlspecialchars($data['payment_id']),
            '{{order_id}}' => htmlspecialchars($data['order_id']),
            '{{date}}' => date('F j, Y'),
            '{{payment_method}}' => htmlspecialchars($data['payment_method']),
        ];

        $html = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );

        // Generate plain text version
        $text = strip_tags(str_replace(['<br>', '</p>'], "\n", $html));

        return [
            'html' => $html,
            'text' => $text
        ];
    }

    private function getSubject(array $data): string
    {
        $prefix = $this->config['subject_prefix'] ?? '';

        return match ($data['status']) {
            'success' => "$prefix Payment Confirmation - Order #{$data['order_id']}",
            'failed' => "$prefix Payment Failed - Order #{$data['order_id']}",
            'pending' => "$prefix Payment Pending - Order #{$data['order_id']}",
            default => "$prefix Payment Update - Order #{$data['order_id']}"
        };
    }

    private function addPaymentReceipt(array $data): void
    {
        // Generate PDF receipt
        $pdfPath = $this->generatePDFReceipt($data);

        if ($pdfPath && file_exists($pdfPath)) {
            $this->mailer->addAttachment(
                $pdfPath,
                "payment_receipt_{$data['order_id']}.pdf"
            );
        }
    }

    private function generatePDFReceipt(array $data): ?string
    {
        // Implementation for PDF generation
        // You can use libraries like TCPDF or Dompdf here
        return null;
    }

    private function logEmailActivity(string $status, array $data, ?string $error = null): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => $status,
            'recipient' => $data['email'],
            'payment_id' => $data['payment_id'],
            'order_id' => $data['order_id'],
            'error' => $error
        ];

        info("Email notification: " . json_encode($logData));
    }
}
