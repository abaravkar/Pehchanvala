<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->boolean('is_admin')->default(false));
        Schema::create('properties', function (Blueprint $t) {
            $t->id(); $t->string('slug')->unique(); $t->string('title'); $t->string('area');
            $t->string('type', 12); $t->string('kind'); $t->unsignedBigInteger('rent')->nullable();
            $t->unsignedBigInteger('sale')->nullable(); $t->unsignedSmallInteger('beds')->default(0);
            $t->unsignedSmallInteger('baths')->default(0); $t->unsignedInteger('size')->default(0);
            $t->string('furnished'); $t->date('available')->nullable(); $t->text('description');
            $t->json('amenities'); $t->json('images'); $t->string('map', 500);
            $t->string('sharing')->nullable(); $t->unsignedBigInteger('deposit')->nullable();
            $t->string('meals')->nullable(); $t->boolean('featured')->default(false);
            $t->boolean('published')->default(false); $t->boolean('illustrative')->default(false);
            $t->timestamps(); $t->softDeletes(); $t->index(['published','type','area']);
        });
        Schema::create('posts', function (Blueprint $t) {
            $t->id(); $t->string('slug')->unique(); $t->string('title'); $t->string('category');
            $t->string('image', 1000); $t->text('summary'); $t->longText('body');
            $t->boolean('published')->default(false); $t->boolean('illustrative')->default(false); $t->timestamps();
        });
        Schema::create('enquiries', function (Blueprint $t) {
            $t->id(); $t->uuid('reference')->unique(); $t->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name'); $t->string('phone',30); $t->string('email'); $t->string('interest');
            $t->string('locality')->nullable(); $t->string('property_title')->nullable();
            $t->unsignedBigInteger('expected_price')->nullable(); $t->text('message')->nullable();
            $t->string('status')->default('new'); $t->text('notes')->nullable(); $t->timestamp('consented_at');
            $t->timestamp('agency_emailed_at')->nullable(); $t->timestamp('customer_emailed_at')->nullable(); $t->timestamps();
        });
        Schema::create('settings', function (Blueprint $t) {$t->string('key')->primary(); $t->longText('value')->nullable();});
        Schema::create('payment_requests', function (Blueprint $t) {
            $t->id(); $t->string('reference',40)->unique(); $t->string('purpose'); $t->unsignedBigInteger('amount');
            $t->string('customer_email')->nullable(); $t->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamp('expires_at'); $t->timestamp('paid_at')->nullable(); $t->boolean('enabled')->default(true); $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id(); $t->foreignId('payment_request_id')->constrained(); $t->string('order_id')->unique();
            $t->string('payment_id')->nullable()->unique(); $t->string('session_hash',64); $t->string('name');
            $t->string('email'); $t->string('phone',30); $t->unsignedBigInteger('amount');
            $t->string('currency',3)->default('INR'); $t->string('status')->default('created');
            $t->timestamp('captured_at')->nullable(); $t->timestamp('agency_emailed_at')->nullable();
            $t->timestamp('customer_emailed_at')->nullable(); $t->timestamps();
        });
        Schema::create('webhook_events', function (Blueprint $t) {$t->string('id')->primary();$t->timestamp('processed_at');});
    }
    public function down(): void
    {
        foreach (['webhook_events','payments','payment_requests','settings','enquiries','posts','properties'] as $table) Schema::dropIfExists($table);
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_admin'));
    }
};
