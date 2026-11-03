<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Sends SMS via Twilio when configured. When Twilio credentials are not set
 * (e.g. local/demo), the message is written to the log instead of being sent,
 * so the rest of the app behaves identically with or without a Twilio account.
 */
class SmsService
{
    public function isConfigured(): bool
    {
        $c = config('services.twilio');

        return ! empty($c['sid']) && ! empty($c['token']) && ! empty($c['from']);
    }

    /**
     * @return array{sent: bool, stub: bool, error: ?string}
     */
    public function send(string $to, string $message): array
    {
        $number = $this->toE164($to);

        if (! $number) {
            Log::warning("[SMS] skipped — could not format number: {$to}");

            return ['sent' => false, 'stub' => false, 'error' => 'invalid number'];
        }

        // No credentials → log the message instead of sending.
        if (! $this->isConfigured() || ! class_exists(\Twilio\Rest\Client::class)) {
            Log::info("[SMS stub] to {$number}: {$message}");

            return ['sent' => true, 'stub' => true, 'error' => null];
        }

        try {
            $c = config('services.twilio');
            $client = new \Twilio\Rest\Client($c['sid'], $c['token']);
            $client->messages->create($number, [
                'from' => $c['from'],
                'body' => $message,
            ]);

            Log::info("[SMS sent] to {$number}");

            return ['sent' => true, 'stub' => false, 'error' => null];
        } catch (\Throwable $e) {
            Log::error('[SMS] Twilio send failed: ' . $e->getMessage());

            return ['sent' => false, 'stub' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Normalise a phone number to E.164 (e.g. +66812345678).
     */
    public function toE164(string $raw): ?string
    {
        $raw = trim($raw);
        $cc = (string) config('services.twilio.default_country_code', '+66');

        // Already international.
        if (str_starts_with($raw, '+')) {
            $digits = preg_replace('/[^0-9]/', '', $raw);

            return $digits ? '+' . $digits : null;
        }

        $digits = preg_replace('/[^0-9]/', '', $raw);
        if (! $digits) {
            return null;
        }

        // Local number starting with a trunk "0" → replace with country code.
        if (str_starts_with($digits, '0')) {
            return $cc . substr($digits, 1);
        }

        // Otherwise assume it already includes the country code digits.
        return $cc . $digits;
    }
}
