<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Portfolio accounts (one per platform connection)
        Schema::create('portfolio_accounts', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('platform', 50); // 'moomoo', 'fsmone', 'luno'
            $table->string('name', 255);
            $table->text('api_key')->nullable();     // encrypted
            $table->text('api_secret')->nullable();   // encrypted
            $table->string('currency', 10)->default('MYR');
            $table->boolean('active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'platform']);
        });

        // Individual holdings (positions)
        Schema::create('portfolio_holdings', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('portfolio_account_id');
            $table->foreign('portfolio_account_id')->references('id')->on('portfolio_accounts')->cascadeOnDelete();
            $table->string('symbol', 50);        // e.g. 'AAPL', 'XBT', fund ISIN
            $table->string('name', 255);          // human-readable name
            $table->string('asset_type', 50);     // 'stock', 'etf', 'crypto', 'fund', 'bond'
            $table->decimal('quantity', 20, 8)->default(0);
            $table->decimal('avg_cost', 20, 8)->default(0);
            $table->decimal('current_price', 20, 8)->default(0);
            $table->decimal('market_value', 20, 2)->default(0);
            $table->decimal('cost_basis', 20, 2)->default(0);
            $table->decimal('unrealized_pnl', 20, 2)->default(0);
            $table->decimal('unrealized_pnl_pct', 10, 4)->default(0);
            $table->string('currency', 10)->default('MYR');
            $table->timestamps();

            $table->index(['portfolio_account_id', 'symbol']);
        });

        // Transaction history
        Schema::create('portfolio_transactions', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('portfolio_account_id');
            $table->foreign('portfolio_account_id')->references('id')->on('portfolio_accounts')->cascadeOnDelete();
            $table->string('symbol', 50);
            $table->string('name', 255)->nullable();
            $table->string('type', 20);           // 'buy', 'sell', 'dividend', 'deposit', 'withdrawal', 'fee'
            $table->decimal('quantity', 20, 8)->default(0);
            $table->decimal('price', 20, 8)->default(0);
            $table->decimal('amount', 20, 2)->default(0);
            $table->decimal('fee', 20, 2)->default(0);
            $table->string('currency', 10)->default('MYR');
            $table->string('external_id', 255)->nullable();
            $table->timestamp('transacted_at');
            $table->timestamps();

            $table->index(['portfolio_account_id', 'transacted_at']);
            $table->index('external_id');
        });

        // Daily portfolio snapshots for historical chart
        Schema::create('portfolio_snapshots', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('portfolio_account_id');
            $table->foreign('portfolio_account_id')->references('id')->on('portfolio_accounts')->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->decimal('total_value', 20, 2)->default(0);
            $table->decimal('total_cost', 20, 2)->default(0);
            $table->decimal('total_pnl', 20, 2)->default(0);
            $table->decimal('day_change', 20, 2)->default(0);
            $table->decimal('day_change_pct', 10, 4)->default(0);
            $table->timestamps();

            $table->unique(['portfolio_account_id', 'snapshot_date']);
        });

        // Sync log
        Schema::create('portfolio_sync_logs', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('portfolio_account_id');
            $table->foreign('portfolio_account_id')->references('id')->on('portfolio_accounts')->cascadeOnDelete();
            $table->string('status', 20);        // 'success', 'error', 'partial'
            $table->text('message')->nullable();
            $table->unsignedInteger('records_synced')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_sync_logs');
        Schema::dropIfExists('portfolio_snapshots');
        Schema::dropIfExists('portfolio_transactions');
        Schema::dropIfExists('portfolio_holdings');
        Schema::dropIfExists('portfolio_accounts');
    }
};
