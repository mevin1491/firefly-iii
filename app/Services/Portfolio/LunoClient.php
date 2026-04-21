<?php

declare(strict_types=1);

namespace FireflyIII\Services\Portfolio;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class LunoClient
{
    private Client $client;
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('portfolio.luno.base_url');
        $this->client  = new Client([
            'base_uri' => $this->baseUrl,
            'auth'     => [
                config('portfolio.luno.api_key_id'),
                config('portfolio.luno.api_key_secret'),
            ],
            'timeout'  => 30,
        ]);
    }

    public function getBalances(): array
    {
        try {
            $response = $this->client->get('balance');
            $data     = json_decode($response->getBody()->getContents(), true);

            return $this->mapBalances($data['balance'] ?? []);
        } catch (GuzzleException $e) {
            Log::error(sprintf('Luno API error fetching balances: %s', $e->getMessage()));

            return [];
        }
    }

    public function getTicker(string $pair): array
    {
        try {
            $response = $this->client->get('ticker', ['query' => ['pair' => $pair]]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error(sprintf('Luno API error fetching ticker %s: %s', $pair, $e->getMessage()));

            return [];
        }
    }

    public function getAllTickers(): array
    {
        try {
            $response = $this->client->get('tickers');
            $data     = json_decode($response->getBody()->getContents(), true);

            return $data['tickers'] ?? [];
        } catch (GuzzleException $e) {
            Log::error(sprintf('Luno API error fetching tickers: %s', $e->getMessage()));

            return [];
        }
    }

    public function getTransactions(string $accountId, int $minRow = 1, int $maxRow = 100): array
    {
        try {
            $response = $this->client->get("accounts/{$accountId}/transactions", [
                'query' => ['min_row' => $minRow, 'max_row' => $maxRow],
            ]);
            $data     = json_decode($response->getBody()->getContents(), true);

            return $data['transactions'] ?? [];
        } catch (GuzzleException $e) {
            Log::error(sprintf('Luno API error fetching transactions: %s', $e->getMessage()));

            return [];
        }
    }

    private function mapBalances(array $balances): array
    {
        $result = [];
        foreach ($balances as $balance) {
            $asset  = $balance['asset'] ?? '';
            $amount = $balance['balance'] ?? '0';

            if (bccomp($amount, '0', 12) <= 0) {
                continue;
            }

            $result[] = [
                'symbol'         => $asset,
                'name'           => $asset,
                'asset_class'    => 'crypto',
                'quantity'       => $amount,
                'account_id'     => $balance['account_id'] ?? '',
                'currency_code'  => $asset,
                'reserved'       => $balance['reserved'] ?? '0',
                'unconfirmed'    => $balance['unconfirmed'] ?? '0',
            ];
        }

        return $result;
    }
}
