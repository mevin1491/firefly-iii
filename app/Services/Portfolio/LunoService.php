<?php

declare(strict_types=1);

namespace FireflyIII\Services\Portfolio;

use Carbon\Carbon;
use FireflyIII\Models\PortfolioAccount;
use FireflyIII\Models\PortfolioHolding;
use FireflyIII\Models\PortfolioSyncLog;
use FireflyIII\Models\PortfolioTransaction;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class LunoService
{
    private const BASE_URL = 'https://api.luno.com/api/1/';

    private Client $client;

    public function __construct(private PortfolioAccount $account)
    {
        $this->client = new Client([
            'base_uri' => self::BASE_URL,
            'auth'     => [$account->decrypted_api_key, $account->decrypted_api_secret],
            'timeout'  => 30,
        ]);
    }

    /**
     * Sync all balances and create/update holdings.
     */
    public function syncBalances(): PortfolioSyncLog
    {
        $log = new PortfolioSyncLog([
            'portfolio_account_id' => $this->account->id,
            'status'               => 'success',
            'records_synced'       => 0,
        ]);

        try {
            $response = $this->client->get('balance');
            $data     = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['balance'])) {
                $log->status  = 'error';
                $log->message = 'Unexpected response format: missing balance key';
                $log->save();

                return $log;
            }

            $synced = 0;

            foreach ($data['balance'] as $wallet) {
                $asset   = $wallet['asset'];
                $balance = (float) $wallet['balance'];

                if ($balance <= 0.0) {
                    continue;
                }

                $price = $this->getTickerPrice($asset, $this->account->currency);

                $marketValue = $balance * $price;

                PortfolioHolding::updateOrCreate(
                    [
                        'portfolio_account_id' => $this->account->id,
                        'symbol'               => $asset,
                    ],
                    [
                        'name'          => $this->getAssetName($asset),
                        'asset_type'    => $this->isFiat($asset) ? 'cash' : 'crypto',
                        'quantity'      => $balance,
                        'current_price' => $price,
                        'market_value'  => $marketValue,
                        'currency'      => $this->account->currency,
                    ]
                );

                ++$synced;
            }

            // Remove holdings with zero balance
            PortfolioHolding::where('portfolio_account_id', $this->account->id)
                ->where('quantity', '<=', 0)
                ->delete();

            $log->records_synced = $synced;
            $log->message        = sprintf('Synced %d wallet balances', $synced);

            $this->account->last_synced_at = Carbon::now();
            $this->account->save();
        } catch (GuzzleException $e) {
            Log::error('Luno API error: ' . $e->getMessage());
            $log->status  = 'error';
            $log->message = 'API error: ' . $e->getMessage();
        } catch (\JsonException $e) {
            Log::error('Luno JSON parse error: ' . $e->getMessage());
            $log->status  = 'error';
            $log->message = 'JSON parse error: ' . $e->getMessage();
        }

        $log->save();

        return $log;
    }

    /**
     * Sync recent transactions for all accounts.
     */
    public function syncTransactions(): PortfolioSyncLog
    {
        $log = new PortfolioSyncLog([
            'portfolio_account_id' => $this->account->id,
            'status'               => 'success',
            'records_synced'       => 0,
        ]);

        try {
            $response = $this->client->get('balance');
            $data     = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            $synced = 0;

            foreach ($data['balance'] ?? [] as $wallet) {
                $accountId = $wallet['account_id'];
                $asset     = $wallet['asset'];

                $txResponse = $this->client->get("accounts/{$accountId}/transactions", [
                    'query' => ['min_row' => 1, 'max_row' => 200],
                ]);
                $txData     = json_decode((string) $txResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);

                foreach ($txData['transactions'] ?? [] as $tx) {
                    $externalId = $accountId . '_' . $tx['row_index'];

                    $exists = PortfolioTransaction::where('external_id', $externalId)
                        ->where('portfolio_account_id', $this->account->id)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $delta = (float) ($tx['balance_delta'] ?? $tx['available_delta'] ?? 0);
                    $type  = $delta >= 0 ? 'deposit' : 'withdrawal';

                    // Try to detect buy/sell from description
                    $desc = strtolower($tx['description'] ?? '');
                    if (str_contains($desc, 'bought') || str_contains($desc, 'buy')) {
                        $type = 'buy';
                    } elseif (str_contains($desc, 'sold') || str_contains($desc, 'sell')) {
                        $type = 'sell';
                    } elseif (str_contains($desc, 'fee')) {
                        $type = 'fee';
                    }

                    PortfolioTransaction::create([
                        'portfolio_account_id' => $this->account->id,
                        'symbol'               => $asset,
                        'name'                 => $tx['description'] ?? $asset,
                        'type'                 => $type,
                        'quantity'             => abs($delta),
                        'price'                => 0,
                        'amount'               => abs($delta),
                        'fee'                  => 0,
                        'currency'             => $tx['currency'] ?? $asset,
                        'external_id'          => $externalId,
                        'transacted_at'        => Carbon::createFromTimestampMs((int) $tx['timestamp']),
                    ]);

                    ++$synced;
                }
            }

            $log->records_synced = $synced;
            $log->message        = sprintf('Synced %d transactions', $synced);
        } catch (GuzzleException $e) {
            Log::error('Luno transaction sync error: ' . $e->getMessage());
            $log->status  = 'error';
            $log->message = 'API error: ' . $e->getMessage();
        } catch (\JsonException $e) {
            Log::error('Luno JSON parse error: ' . $e->getMessage());
            $log->status  = 'error';
            $log->message = 'JSON parse error: ' . $e->getMessage();
        }

        $log->save();

        return $log;
    }

    /**
     * Get current ticker price for an asset pair.
     */
    private function getTickerPrice(string $asset, string $fiat): float
    {
        if ($this->isFiat($asset)) {
            return 1.0;
        }

        $pair = $asset . $fiat;

        try {
            $response = $this->client->get('ticker', [
                'query' => ['pair' => $pair],
            ]);
            $data     = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            return (float) ($data['last_trade'] ?? 0);
        } catch (\Exception $e) {
            Log::warning("Luno: Could not fetch ticker for {$pair}: " . $e->getMessage());

            return 0.0;
        }
    }

    private function isFiat(string $asset): bool
    {
        return in_array(strtoupper($asset), ['MYR', 'ZAR', 'NGN', 'UGX', 'IDR', 'EUR', 'GBP', 'USD'], true);
    }

    private function getAssetName(string $asset): string
    {
        $names = [
            'XBT'  => 'Bitcoin',
            'ETH'  => 'Ethereum',
            'XRP'  => 'Ripple',
            'LTC'  => 'Litecoin',
            'BCH'  => 'Bitcoin Cash',
            'SOL'  => 'Solana',
            'USDC' => 'USD Coin',
            'USDT' => 'Tether',
            'UNI'  => 'Uniswap',
            'LINK' => 'Chainlink',
            'MYR'  => 'Malaysian Ringgit',
            'ZAR'  => 'South African Rand',
            'NGN'  => 'Nigerian Naira',
            'IDR'  => 'Indonesian Rupiah',
        ];

        return $names[$asset] ?? $asset;
    }

    /**
     * Test the API connection.
     */
    public static function testConnection(string $apiKey, string $apiSecret): array
    {
        try {
            $client   = new Client([
                'base_uri' => self::BASE_URL,
                'auth'     => [$apiKey, $apiSecret],
                'timeout'  => 10,
            ]);
            $response = $client->get('balance');
            $data     = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            return [
                'success'  => true,
                'wallets'  => count($data['balance'] ?? []),
                'message'  => sprintf('Connected successfully. Found %d wallets.', count($data['balance'] ?? [])),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'wallets' => 0,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }
}
