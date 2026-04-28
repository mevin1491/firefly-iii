<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('portfolio_accounts', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('user_group_id');
            $table->string('name', 255);
            $table->string('platform', 50);
            $table->boolean('active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('user_group_id')->references('id')->on('user_groups')->onDelete('cascade');
            $table->index(['user_id', 'platform']);
        });

        Schema::create('portfolio_holdings', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('portfolio_account_id');
            $table->string('symbol', 50);
            $table->string('name', 255);
            $table->string('asset_class', 50);
            $table->decimal('quantity', 32, 12)->default(0);
            $table->decimal('average_cost', 32, 12)->default(0);
            $table->string('cost_currency_code', 10);
            $table->decimal('current_price', 32, 12)->nullable();
            $table->string('price_currency_code', 10);
            $table->decimal('current_value', 32, 12)->nullable();
            $table->decimal('unrealized_pnl', 32, 12)->nullable();
            $table->timestamp('last_price_update')->nullable();
            $table->timestamps();

            $table->foreign('portfolio_account_id')->references('id')->on('portfolio_accounts')->onDelete('cascade');
            $table->unique(['portfolio_account_id', 'symbol']);
        });

        Schema::create('portfolio_transactions', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('portfolio_account_id');
            $table->string('symbol', 50);
            $table->string('transaction_type', 20);
            $table->decimal('quantity', 32, 12)->default(0);
            $table->decimal('price_per_unit', 32, 12)->default(0);
            $table->decimal('total_amount', 32, 12)->default(0);
            $table->string('currency_code', 10);
            $table->decimal('fees', 32, 12)->default(0);
            $table->timestamp('transacted_at');
            $table->string('external_id', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('portfolio_account_id')->references('id')->on('portfolio_accounts')->onDelete('cascade');
            $table->unique(['portfolio_account_id', 'external_id']);
        });

        Schema::create('portfolio_prices', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('symbol', 50);
            $table->string('platform', 50);
            $table->decimal('price', 32, 12);
            $table->string('currency_code', 10);
            $table->date('priced_at');
            $table->timestamps();

            $table->unique(['symbol', 'platform', 'priced_at']);
        });

        Schema::create('portfolio_snapshots', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('portfolio_account_id')->nullable();
            $table->decimal('total_value', 32, 12)->default(0);
            $table->decimal('total_cost', 32, 12)->default(0);
            $table->string('currency_code', 10);
            $table->date('snapshot_date');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('portfolio_account_id')->references('id')->on('portfolio_accounts')->onDelete('cascade');
            $table->unique(['user_id', 'portfolio_account_id', 'snapshot_date', 'currency_code'], 'portfolio_snapshots_unique');
        });

        Schema::create('portfolio_import_logs', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('portfolio_account_id');
            $table->string('filename', 255);
            $table->integer('rows_imported')->default(0);
            $table->integer('rows_skipped')->default(0);
            $table->string('status', 20)->default('pending');
            $table->text('errors')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('portfolio_account_id')->references('id')->on('portfolio_accounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_import_logs');
        Schema::dropIfExists('portfolio_snapshots');
        Schema::dropIfExists('portfolio_prices');
        Schema::dropIfExists('portfolio_transactions');
        Schema::dropIfExists('portfolio_holdings');
        Schema::dropIfExists('portfolio_accounts');
    }
};
