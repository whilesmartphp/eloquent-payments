<?php

namespace Whilesmart\Payments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Whilesmart\OwnerAccess\Concerns\AuthorizesOwnerRequest;

class StorePaymentRequest extends FormRequest
{
    use AuthorizesOwnerRequest;

    public function authorize(): bool
    {
        return $this->authorizeOwnerInRequest();
    }

    public function rules(): array
    {
        return [
            'payable_type' => ['required', 'string'],
            'payable_id' => ['required'],
            'owner_type' => ['nullable', 'string'],
            'owner_id' => ['nullable'],
            'account_type' => ['nullable', 'string', 'required_with:account_id'],
            'account_id' => ['nullable', 'required_with:account_type'],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['nullable', 'in:pending,authorized,succeeded,failed,refunded,partially_refunded,cancelled'],
            'direction' => ['nullable', 'in:inbound,outbound'],
            'gateway' => ['nullable', 'string', 'max:60'],
            'gateway_reference' => ['nullable', 'string', 'max:200'],
            'method' => ['nullable', 'string', 'max:60'],
            'authorized_at' => ['nullable', 'date'],
            'succeeded_at' => ['nullable', 'date'],
            'failed_at' => ['nullable', 'date'],
            'failure_reason' => ['nullable', 'string'],
            'parent_payment_id' => ['nullable', 'integer', 'exists:payments,id'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
