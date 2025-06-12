<?php
// app/Services/SmsService.php

namespace App\Services;

class SmsService
{
    public function send($phone, $message): bool
    {
        $username = 'fyp';
        $password = '1956Pio%palex';
        $from = 'REMINDER';
        $url = 'https://messaging-service.co.tz/api/sms/v1/text/single';
        $auth = base64_encode("$username:$password");

        $data = [
            "from" => $from,
            "to" => $phone,
            "text" => $message
        ];

        $headers = [
            "Authorization: Basic $auth",
            "Content-Type: application/json"
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        // Debug output for testing if cURL is reached
        if (function_exists('logger')) {
            logger()->info('Sending SMS to: ' . $phone . ' with message: ' . $message);
            logger()->info('SMS cURL debug', ['http_code' => $http_code, 'response' => $response]);
        }
        curl_close($ch);

        if ($curl_error) {
            // Optionally log the cURL error if logger is available
            if (function_exists('logger')) {
                logger()->error('SMS cURL error: ' . $curl_error);
            }
            return false;
        }

        if (!in_array($http_code, [200, 201])) {
            // Optionally log the HTTP error and response
            if (function_exists('logger')) {
                logger()->error('SMS HTTP error: ' . $http_code . ' Response: ' . $response);
            }
            return false;
        }

        return true;
    }
}
