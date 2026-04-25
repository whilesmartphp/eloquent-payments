<?php

namespace Whilesmart\Payments\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Whilesmart\OwnerAccess\Concerns\AuthorizesOwnerController;
use Whilesmart\Payments\Http\Requests\StorePaymentRequest;
use Whilesmart\Payments\Http\Requests\UpdatePaymentRequest;
use Whilesmart\Payments\Http\Resources\PaymentResource;
use Whilesmart\Payments\Models\Payment;

class PaymentController extends Controller
{
    use AuthorizesOwnerController;

    public function index(Request $request): JsonResponse
    {
        $query = $this->scopeAccessibleOwners(Payment::query(), $request->user());

        if ($request->filled('payable_type') && $request->filled('payable_id')) {
            $query->where('payable_type', $request->input('payable_type'))
                ->where('payable_id', $request->input('payable_id'));
        }

        if ($request->filled('owner_type') && $request->filled('owner_id')) {
            $query->where('owner_type', $request->input('owner_type'))
                ->where('owner_id', $request->input('owner_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('direction')) {
            $query->where('direction', $request->input('direction'));
        }

        if ($request->filled('gateway')) {
            $query->where('gateway', $request->input('gateway'));
        }

        $payments = $query->latest()
            ->paginate((int) $request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => PaymentResource::collection($payments)->response()->getData(true),
        ]);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $payment = Payment::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment),
        ], 201);
    }

    public function show(Payment $payment, Request $request): JsonResponse
    {
        $this->authorizeAccessTo($payment, $request->user());

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment),
        ]);
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): JsonResponse
    {
        $payment->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment),
        ]);
    }

    public function destroy(Payment $payment, Request $request): JsonResponse
    {
        $this->authorizeAccessTo($payment, $request->user());
        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted.',
        ]);
    }
}
