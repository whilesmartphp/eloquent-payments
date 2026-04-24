<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('payments.table', 'payments'), function (Blueprint $table) {
            $table->id();

            // What's being paid for. Polymorphic so we never care whether it's
            // an invoice, an expense, a subscription charge, or anything else.
            $table->morphs('payable');

            // Who initiated / owns this payment (workspace, user, organisation).
            // Optional but useful for multi-tenant queries without joining the payable.
            $table->nullableMorphs('owner');

            // Which account the funds moved in/out of (bank, mobile money wallet,
            // cash register, card processor). Polymorphic so any Account-like
            // model can be attached -- see whilesmart/eloquent-accounts.
            $table->nullableMorphs('account');

            // Money
            $table->bigInteger('amount_cents');
            $table->string('currency', 3);

            // Lifecycle
            $table->string('status')->default('pending');
            $table->string('direction')->default('inbound');

            // Gateway details
            $table->string('gateway')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->string('method')->nullable();

            // Audit timeline
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('succeeded_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('failure_reason')->nullable();

            // Refund chain: a refund Payment points back to the original.
            $table->foreignId('parent_payment_id')->nullable()->constrained('payments')->nullOnDelete();

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['payable_type', 'payable_id', 'status']);
            $table->index(['owner_type', 'owner_id', 'status']);
            $table->unique(['gateway', 'gateway_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('payments.table', 'payments'));
    }
};
