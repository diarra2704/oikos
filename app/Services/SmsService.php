<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function __construct(
        protected bool $enabled,
        protected ?string $sid,
        protected ?string $token,
        protected ?string $from,
    ) {}

    public static function fromConfig(): self
    {
        $config = config('sms', []);
        $twilio = $config['twilio'] ?? [];
        return new self(
            enabled: (bool) ($config['enabled'] ?? false),
            sid: $twilio['sid'] ?? null,
            token: $twilio['token'] ?? null,
            from: $twilio['from'] ?? null,
        );
    }

    public function isConfigured(): bool
    {
        return $this->enabled && $this->sid && $this->token && $this->from;
    }

    /**
     * Envoie un SMS via Twilio.
     *
     * @return array{success: bool, sid?: string, error?: string}
     */
    public function send(string $to, string $message): array
    {
        if (!$this->isConfigured()) {
            Log::warning('SMS non configuré, envoi simulé vers ' . $to);
            return ['success' => true];
        }

        $to = $this->normalizePhone($to);
        if (!$to) {
            return ['success' => false, 'error' => 'Numéro invalide'];
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json";
        $response = Http::asForm()
            ->withBasicAuth($this->sid, $this->token)
            ->post($url, [
                'From' => $this->from,
                'To' => $to,
                'Body' => $message,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return ['success' => true, 'sid' => $data['sid'] ?? null];
        }

        $error = $response->json('message') ?? $response->body();
        Log::error('Erreur envoi SMS Twilio', ['to' => $to, 'error' => $error]);

        return ['success' => false, 'error' => $error];
    }

    protected function normalizePhone(string $phone): ?string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) < 9) {
            return null;
        }
        if (!str_starts_with($phone, '237') && strlen($phone) === 9) {
            $phone = '237' . $phone;
        }
        return '+' . $phone;
    }
}
