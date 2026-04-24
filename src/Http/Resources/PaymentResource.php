<?php

namespace Whilesmart\Payments\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'payable_type' => $this->payable_type,
            'payable_id' => $this->payable_id,
            'owner_type' => $this->owner_type,
            'owner_id' => $this->owner_id,
            'account_type' => $this->account_type,
            'account_id' => $this->account_id,
            'account' => $this->whenLoaded('account'),
            'amount_cents' => $this->amount_cents,
            'currency' => $this->currency,
            'status' => $this->status,
            'direction' => $this->direction,
            'gateway' => $this->gateway,
            'gateway_reference' => $this->gateway_reference,
            'method' => $this->method,
            'authorized_at' => $this->authorized_at,
            'succeeded_at' => $this->succeeded_at,
            'failed_at' => $this->failed_at,
            'refunded_at' => $this->refunded_at,
            'failure_reason' => $this->failure_reason,
            'parent_payment_id' => $this->parent_payment_id,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
