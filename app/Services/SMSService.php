<?php

// 1. SMS Service Class (app/Services/SMSService.php)
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SMSService
{
    private $username;
    private $password;
    private $from;
    private $url;

    public function __construct()
    {
        $this->username = config('sms.username', 'fyp');
        $this->password = config('sms.password', '1956Pio%palex');
        $this->from = config('sms.from', 'REMINDER');
        $this->url = config('sms.url', 'https://messaging-service.co.tz/api/sms/v1/text/single');
    }

    public function sendSMS($phone, $message)
    {
        try {
            $auth = base64_encode($this->username . ':' . $this->password);

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $auth,
                'Content-Type' => 'application/json'
            ])->post($this->url, [
                'from' => $this->from,
                'to' => $phone,
                'text' => $message
            ]);

            if ($response->successful()) {
                Log::info('SMS sent successfully', ['phone' => $phone]);
                return true;
            } else {
                Log::error('SMS failed', ['phone' => $phone, 'response' => $response->body()]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('SMS exception', ['phone' => $phone, 'error' => $e->getMessage()]);
            return false;
        }
    }
}