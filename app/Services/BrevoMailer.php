<?php

class BrevoMailer {
    private $apiKey;
    private $apiUrl = 'https://api.brevo.com/v3/smtp/email';

    public function __construct() {
        $this->apiKey = defined('BREVO_API_KEY') ? BREVO_API_KEY : '';
    }

    // NEW: Added $senderName as an optional parameter at the end
    public function sendEmail($toEmail, $toName, $subject, $htmlContent, $attachmentContent = null, $attachmentName = null, $senderName = null) {
        if (empty($this->apiKey) || strpos($this->apiKey, 'YOUR_API_KEY_HERE') !== false) {
            return ['success' => false, 'error' => 'Brevo API Key is missing or invalid in config/database.php.'];
        }

        $data = [
            'sender' => [
                'name' => $senderName ?: APP_NAME, // Dynamically uses Company Name if provided
                'email' => 'falconstationary@gmail.com' // <-- KEEP YOUR FIXED EMAIL HERE
            ],
            'to' => [
                [
                    'email' => $toEmail,
                    'name' => $toName
                ]
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent
        ];

        // Attach the document if provided
        if ($attachmentContent && $attachmentName) {
            $data['attachment'] = [
                [
                    'name' => $attachmentName,
                    'content' => base64_encode($attachmentContent)
                ]
            ];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'api-key: ' . $this->apiKey,
            'content-type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'error' => ''];
        } else {
            return ['success' => false, 'error' => $response ?: $curlError];
        }
    }
}