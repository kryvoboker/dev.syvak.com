<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FailureOrderRecoveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number');
            $table->unsignedBigInteger('order_status_id')->nullable();
            $table->string('order_status_name')->nullable();
            $table->string('order_type')->default('regular');
            $table->decimal('total', 15, 4)->default(0);
            $table->timestamps();
        });
    }

    public function test_retry_rejects_a_missing_session_bound_order(): void
    {
        $response = app(\App\Services\Order\FailureOrderRecoveryService::class)
            ->retry('cash_on_delivery', 'en');

        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('payment', $response['errors']);
    }
}
