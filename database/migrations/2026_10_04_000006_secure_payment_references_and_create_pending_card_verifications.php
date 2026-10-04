<?php

use App\Services\PaymentReferenceService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $key = $this->encryptionKey();

        Schema::table('payment', function (Blueprint $table): void {
            $table->longText('reference_ciphertext')->nullable();
            $table->char('reference_fingerprint', 64)->nullable()->index('payment_reference_fingerprint_index');
            $table->string('card_last4', 4)->nullable();
        });

        Schema::table('pending_ewallet_verifications', function (Blueprint $table): void {
            $table->dropUnique('pending_ewallet_provider_reference_unique');
            $table->longText('reference_ciphertext')->nullable();
            $table->char('reference_fingerprint', 64)->nullable();
        });

        Schema::create('pending_card_verifications', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->integer('employee_id')->index();
            $table->uuid('checkout_idempotency_key')->nullable()->unique('pending_card_checkout_idempotency_unique');
            $table->string('payment_provider', 50);
            $table->longText('reference_ciphertext');
            $table->char('reference_fingerprint', 64)->index('pending_card_reference_fingerprint_index');
            $table->string('card_last4', 4)->nullable();
            $table->decimal('submitted_amount', 10, 2);
            $table->json('checkout_payload');
            $table->string('status', 20)->default('pending')->index();
            $table->dateTime('expires_at')->index();
            $table->integer('verified_by_employee_id')->nullable()->index();
            $table->dateTime('verified_at')->nullable();
            $table->integer('rejected_by_employee_id')->nullable()->index();
            $table->dateTime('rejected_at')->nullable();
            $table->integer('sale_transaction_id')->nullable()->index();
            $table->string('resolution_note', 255)->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('employee_id')->on('employee')->restrictOnDelete();
            $table->foreign('verified_by_employee_id')->references('employee_id')->on('employee')->nullOnDelete();
            $table->foreign('rejected_by_employee_id')->references('employee_id')->on('employee')->nullOnDelete();
            $table->foreign('sale_transaction_id')->references('transaction_id')->on('sale_transaction')->nullOnDelete();
        });

        Schema::create('payment_reference_registry', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('payment_method', 20);
            $table->string('payment_provider', 50);
            $table->char('reference_fingerprint', 64);
            $table->string('source_type', 60);
            $table->unsignedBigInteger('source_id');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(
                ['payment_method', 'payment_provider', 'reference_fingerprint'],
                'payment_reference_registry_unique',
            );
            $table->index(['source_type', 'source_id'], 'payment_reference_registry_source_index');
        });

        $this->encryptExistingPaymentReferences($key);
        $this->encryptExistingEwalletReferences($key);

        Schema::table('pending_ewallet_verifications', function (Blueprint $table): void {
            $table->unique(
                ['payment_provider', 'reference_fingerprint'],
                'pending_ewallet_provider_fingerprint_unique',
            );
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('pending_card_verifications')
            && DB::table('pending_card_verifications')->where('status', 'pending')->exists()) {
            throw new RuntimeException('Resolve pending Card verifications before rolling back the payment security migration.');
        }

        $this->restorePaymentReferences();
        $this->restoreEwalletReferences();

        Schema::dropIfExists('payment_reference_registry');
        Schema::dropIfExists('pending_card_verifications');

        Schema::table('pending_ewallet_verifications', function (Blueprint $table): void {
            $table->dropUnique('pending_ewallet_provider_fingerprint_unique');
            $table->dropColumn(['reference_ciphertext', 'reference_fingerprint']);
            $table->unique(['payment_provider', 'reference_number'], 'pending_ewallet_provider_reference_unique');
        });

        Schema::table('payment', function (Blueprint $table): void {
            $table->dropIndex('payment_reference_fingerprint_index');
            $table->dropColumn(['reference_ciphertext', 'reference_fingerprint', 'card_last4']);
        });
    }

    private function encryptExistingPaymentReferences(string $key): void
    {
        DB::table('payment')
            ->whereNotNull('reference_number')
            ->where('reference_number', '<>', '')
            ->orderBy('payment_id')
            ->chunkById(100, function ($payments) use ($key): void {
                foreach ($payments as $payment) {
                    $reference = trim((string) $payment->reference_number);
                    if ($reference === '') {
                        continue;
                    }

                    $last4 = null;
                    if ($payment->payment_method === 'card'
                        && preg_match('/\s+\(\*{4}\s+(\d{4})\)$/', $reference, $matches)) {
                        $last4 = $matches[1];
                    }

                    if ($payment->payment_method === 'card') {
                        $approvalValue = preg_replace('/\s+\(\*{4}\s+\d{4}\)$/', '', $reference) ?? $reference;
                        $digitsOnly = preg_replace('/[\s-]+/', '', $approvalValue) ?? $approvalValue;
                        $isPan = preg_match('/^\d{13,19}$/', $digitsOnly) === 1;
                        $isCvvLike = preg_match('/^\d{3,4}$/', $digitsOnly) === 1;
                        if ($isPan || $isCvvLike) {
                            if ($isPan) {
                                $last4 = substr($digitsOnly, -4);
                            }

                            // Never migrate a full PAN or a likely CVV into ciphertext.
                            DB::table('payment')->where('payment_id', $payment->payment_id)->update([
                                'reference_ciphertext' => null,
                                'reference_fingerprint' => null,
                                'card_last4' => $last4,
                                'reference_number' => null,
                            ]);

                            continue;
                        }
                    }

                    $fingerprint = PaymentReferenceService::fingerprintFor(
                        (string) $payment->payment_method,
                        (string) ($payment->payment_provider ?? ''),
                        $reference,
                        $key,
                    );

                    DB::table('payment')->where('payment_id', $payment->payment_id)->update([
                        'reference_ciphertext' => Crypt::encryptString($reference),
                        'reference_fingerprint' => $fingerprint,
                        'card_last4' => $last4,
                        'reference_number' => null,
                    ]);

                    $this->registerFingerprint(
                        (string) $payment->payment_method,
                        (string) ($payment->payment_provider ?? ''),
                        $fingerprint,
                        'payment',
                        (int) $payment->payment_id,
                    );
                }
            }, 'payment_id');
    }

    private function encryptExistingEwalletReferences(string $key): void
    {
        DB::table('pending_ewallet_verifications')
            ->orderBy('id')
            ->chunkById(100, function ($requests) use ($key): void {
                foreach ($requests as $request) {
                    $reference = trim((string) $request->reference_number);
                    if ($reference === '') {
                        throw new RuntimeException('An existing pending E-Wallet row has no reference number; migration stopped without discarding the row.');
                    }

                    $fingerprint = PaymentReferenceService::fingerprintFor(
                        'e-wallet',
                        (string) $request->payment_provider,
                        $reference,
                        $key,
                    );
                    $payload = json_decode((string) $request->checkout_payload, true) ?: [];
                    unset($payload['reference_number']);

                    DB::table('pending_ewallet_verifications')->where('id', $request->id)->update([
                        'reference_ciphertext' => Crypt::encryptString($reference),
                        'reference_fingerprint' => $fingerprint,
                        'reference_number' => '',
                        'checkout_payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                    ]);

                    $this->registerFingerprint(
                        'e-wallet',
                        (string) $request->payment_provider,
                        $fingerprint,
                        'pending_ewallet_verifications',
                        (int) $request->id,
                    );
                }
            });
    }

    private function registerFingerprint(
        string $method,
        string $provider,
        string $fingerprint,
        string $sourceType,
        int $sourceId,
    ): void {
        DB::table('payment_reference_registry')->insertOrIgnore([
            'payment_method' => $method,
            'payment_provider' => $provider,
            'reference_fingerprint' => $fingerprint,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'created_at' => now(),
        ]);
    }

    private function restorePaymentReferences(): void
    {
        DB::table('payment')
            ->whereNotNull('reference_ciphertext')
            ->orderBy('payment_id')
            ->chunkById(100, function ($payments): void {
                foreach ($payments as $payment) {
                    $reference = Crypt::decryptString($payment->reference_ciphertext);
                    if ($payment->payment_method === 'card'
                        && $payment->card_last4
                        && ! preg_match('/\s+\(\*{4}\s+\d{4}\)$/', $reference)) {
                        $reference .= ' (**** '.$payment->card_last4.')';
                    }

                    DB::table('payment')->where('payment_id', $payment->payment_id)->update([
                        'reference_number' => $reference,
                    ]);
                }
            }, 'payment_id');
    }

    private function restoreEwalletReferences(): void
    {
        DB::table('pending_ewallet_verifications')
            ->whereNotNull('reference_ciphertext')
            ->orderBy('id')
            ->chunkById(100, function ($requests): void {
                foreach ($requests as $request) {
                    $reference = Crypt::decryptString($request->reference_ciphertext);
                    $payload = json_decode((string) $request->checkout_payload, true) ?: [];
                    $payload['reference_number'] = $reference;

                    DB::table('pending_ewallet_verifications')->where('id', $request->id)->update([
                        'reference_number' => $reference,
                        'checkout_payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                    ]);
                }
            });
    }

    private function encryptionKey(): string
    {
        $configuredKey = (string) config('app.key');
        $key = str_starts_with($configuredKey, 'base64:')
            ? base64_decode(substr($configuredKey, 7), true)
            : $configuredKey;

        if ($key === false || $key === '') {
            throw new RuntimeException('APP_KEY must be configured before securing payment references.');
        }

        return $key;
    }
};
