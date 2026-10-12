<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMidtransSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->is_active === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'environment' => ['required', 'in:sandbox,production'],
            'merchant_id' => ['required', 'string', 'max:80'],
            'server_key' => ['nullable', 'string', 'max:255'],
            'client_key' => ['nullable', 'string', 'max:255'],
            'enabled' => ['nullable', 'boolean'],
        ];
    }
}
