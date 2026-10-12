<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMidtransSettingsRequest;
use App\Services\MidtransConfiguration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class MidtransSettingsController extends Controller
{
    public function __construct(private MidtransConfiguration $configuration) {}

    public function edit(): View
    {
        return view('admin.midtrans-settings', [
            'enabled' => $this->configuration->enabled(),
            'environment' => $this->configuration->environment(),
            'merchantId' => $this->configuration->merchantId(),
            'serverKey' => $this->configuration->maskedServerKey(),
            'clientKey' => $this->configuration->maskedClientKey(),
        ]);
    }

    public function update(UpdateMidtransSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->boolean('enabled')
            && blank($validated['server_key'] ?? null)
            && $this->configuration->serverKey() === null) {
            throw ValidationException::withMessages(['server_key' => 'Server Key wajib diisi sebelum Midtrans diaktifkan.']);
        }

        $this->configuration->save([
            'enabled' => $request->boolean('enabled'),
            'environment' => $validated['environment'],
            'merchant_id' => $validated['merchant_id'],
            'server_key' => $validated['server_key'] ?? null,
            'client_key' => $validated['client_key'] ?? null,
        ]);

        return back()->with('status', 'Pengaturan Midtrans berhasil disimpan.');
    }
}
