<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;

class MidtransConfiguration
{
    public const SETTING_PREFIX = 'midtrans_';

    private ?Collection $settings = null;

    public function enabled(): bool
    {
        return $this->value('midtrans_enabled') === '1';
    }

    public function environment(): string
    {
        return $this->value('midtrans_environment') === 'production' ? 'production' : 'sandbox';
    }

    public function merchantId(): ?string
    {
        return $this->nullableValue('midtrans_merchant_id');
    }

    public function serverKey(): ?string
    {
        return $this->secret('midtrans_server_key');
    }

    public function clientKey(): ?string
    {
        return $this->secret('midtrans_client_key');
    }

    public function configured(): bool
    {
        return $this->enabled() && $this->merchantId() !== null && $this->serverKey() !== null;
    }

    public function snapTransactionUrl(): string
    {
        return $this->environment() === 'production'
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    /** @param  array{enabled: bool, environment: string, merchant_id: string, server_key?: string|null, client_key?: string|null}  $values */
    public function save(array $values): void
    {
        $this->put('midtrans_enabled', $values['enabled'] ? '1' : '0');
        $this->put('midtrans_environment', $values['environment']);
        $this->put('midtrans_merchant_id', trim($values['merchant_id']));

        foreach (['server_key' => 'midtrans_server_key', 'client_key' => 'midtrans_client_key'] as $field => $key) {
            if (filled($values[$field] ?? null)) {
                $this->put($key, Crypt::encryptString(trim((string) $values[$field])));
            }
        }

        $this->settings = null;
    }

    public function maskedServerKey(): ?string
    {
        return $this->mask($this->serverKey());
    }

    public function maskedClientKey(): ?string
    {
        return $this->mask($this->clientKey());
    }

    private function secret(string $key): ?string
    {
        $encrypted = $this->nullableValue($key);

        return $encrypted === null ? null : Crypt::decryptString($encrypted);
    }

    private function mask(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return str_repeat('•', max(8, mb_strlen($value) - 4)).mb_substr($value, -4);
    }

    private function value(string $key): ?string
    {
        return $this->all()->get($key);
    }

    private function nullableValue(string $key): ?string
    {
        $value = trim((string) $this->value($key));

        return $value === '' ? null : $value;
    }

    private function all(): Collection
    {
        return $this->settings ??= SiteSetting::query()
            ->where('setting_key', 'like', self::SETTING_PREFIX.'%')
            ->pluck('setting_value', 'setting_key');
    }

    private function put(string $key, string $value): void
    {
        SiteSetting::query()->updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value],
        );
    }
}
