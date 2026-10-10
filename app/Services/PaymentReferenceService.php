<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use RuntimeException;

class PaymentReferenceService
{
    public function encrypt(string $reference): string
    {
        return Crypt::encryptString($reference);
    }

    public function decrypt(?string $ciphertext): ?string
    {
        if ($ciphertext === null || $ciphertext === '') {
            return null;
        }

        return Crypt::decryptString($ciphertext);
    }

    public function fingerprint(string $method, string $provider, string $reference): string
    {
        $key = $this->key();

        return self::fingerprintFor($method, $provider, $reference, $key);
    }

    public static function fingerprintFor(string $method, string $provider, string $reference, string $key): string
    {
        $reference = trim($reference);
        if (strtolower($method) === 'card') {
            $reference = preg_replace('/\s+\(\*{4}\s+\d{4}\)$/', '', $reference) ?? $reference;
            $reference = mb_strtolower($reference);
        }

        $normalized = strtolower(trim($method))."\0".trim($provider)."\0".$reference;

        return hash_hmac('sha256', $normalized, $key);
    }

    public function reserve(string $method, string $provider, string $fingerprint, string $sourceType, int $sourceId): void
    {
        \Illuminate\Support\Facades\DB::table('payment_reference_registry')->insert([
            'payment_method' => $method,
            'payment_provider' => $provider,
            'reference_fingerprint' => $fingerprint,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'created_at' => now(),
        ]);
    }

    public function alreadyUsed(string $method, string $provider, string $fingerprint): bool
    {
        return \Illuminate\Support\Facades\DB::table('payment_reference_registry')
            ->where('payment_method', $method)
            ->where('payment_provider', $provider)
            ->where('reference_fingerprint', $fingerprint)
            ->exists();
    }

    public function mask(string $method, ?string $reference, ?string $cardLast4 = null): string
    {
        if (strtolower($method) === 'card') {
            return '•••• •••• •••• '.($cardLast4 ?: '••••');
        }

        if ($reference === null || $reference === '') {
            return '—';
        }

        $suffix = mb_substr($reference, -4);
        $maskedLength = max(4, mb_strlen($reference) - mb_strlen($suffix));

        return str_repeat('•', $maskedLength).$suffix;
    }

    public function maskedApprovalCode(?string $reference): string
    {
        if ($reference === null || $reference === '') {
            return '••••';
        }

        $suffix = mb_substr($reference, -4);
        $maskedLength = max(4, mb_strlen($reference) - mb_strlen($suffix));

        return str_repeat('•', $maskedLength).$suffix;
    }

    public function redactText(string $text, ?string $reference, string $maskedValue): string
    {
        if ($reference === null || $reference === '') {
            return $text;
        }

        return str_ireplace($reference, $maskedValue, $text);
    }

    public function revealCardApproval(?string $reference): ?string
    {
        if ($reference === null) {
            return null;
        }

        return preg_replace('/\s+\(\*{4}\s+\d{4}\)$/', '', $reference) ?? $reference;
    }

    private function key(): string
    {
        $configuredKey = (string) config('app.key');
        $key = str_starts_with($configuredKey, 'base64:')
            ? base64_decode(substr($configuredKey, 7), true)
            : $configuredKey;

        if ($key === false || $key === '') {
            throw new RuntimeException('APP_KEY must be configured to protect payment references.');
        }

        return $key;
    }
}
