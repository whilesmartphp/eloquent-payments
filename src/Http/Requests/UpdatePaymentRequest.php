<?php

namespace Whilesmart\Payments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_type' => ['nullable', 'string'],
            'account_id' => ['nullable'],
            'status' => ['nullable', 'in:pending,authorized,succeeded,failed,refunded,partially_refunded,cancelled'],
            'gateway' => ['nullable', 'string', 'max:60'],
            'gateway_reference' => ['nullable', 'string', 'max:200'],
            'method' => ['nullable', 'string', 'max:60'],
            'authorized_at' => ['nullable', 'date'],
            'succeeded_at' => ['nullable', 'date'],
            'failed_at' => ['nullable', 'date'],
            'refunded_at' => ['nullable', 'date'],
            'failure_reason' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
