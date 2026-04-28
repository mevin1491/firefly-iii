<?php

declare(strict_types=1);

namespace FireflyIII\Services\Portfolio;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class MoomooClient
{
    private Client $client;
    private string $host;
    private int $port;

    public function __construct()
    {
        $this->host   = config('portfolio.moomoo.host');
        $this->port   = config('portfolio.moomoo.port');
        $this->client = new Client([
            'base_uri' => sprintf('http://%s:%d/', $this->host, $this->port),
            'timeout'  => 30,
        ]);
    }

    public function isAvailable(): bool
    {
        try {
            $this->client->get('');

            return true;
        } catch (GuzzleException) {
            return false;
        }
    }

    public function getPositions(): array
    {
        try {
            $response = $this->client->post('api/position', [
                'json' => [
                    'trd_env'    => config('portfolio.moomoo.trade_env'),
                    'trd_market' => 1,
                ],
            ]);
            $data     = json_decode($response->getBody()->getContents(), true);

            return $this->mapPositions($data['data']['position_list'] ?? []);
        } catch (GuzzleException $e) {
            Log::error(sprintf('Moomoo OpenD error fetching positions: %s', $e->getMessage()));

            return [];
        }
    }

    public function getMarketPrice(string $symbol): ?array
    {
        try {
            $response = $this->client->post('api/market/snapshot', [
                'json' => [
                    'security_list' => [['code' => $symbol, 'market' => 1]],
                ],
            ]);
            $data     = json_decode($response->getBody()->getContents(), true);
            $snapshot = $data['data']['snapshot_list'][0] ?? null;

            if (null === $snapshot) {
                return null;
            }

            return [
                'price'         => (string) ($snapshot['last_price'] ?? '0'),
                'currency_code' => $this->detectCurrency($symbol),
            ];
        } catch (GuzzleException $e) {
            Log::error(sprintf('Moomoo OpenD error fetching price for %s: %s', $symbol, $e->getMessage()));

            return null;
        }
    }

    public function getAccountBalances(): array
    {
        try {
            $response = $this->client->post('api/account', [
                'json' => [
                    'trd_env'    => config('portfolio.moomoo.trade_env'),
                    'trd_market' => 1,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true)['data'] ?? [];
        } catch (GuzzleException $e) {
            Log::error(sprintf('Moomoo OpenD error fetching account: %s', $e->getMessage()));

            return [];
        }
    }

    private function mapPositions(array $positions): array
    {
        $result = [];
        foreach ($positions as $pos) {
            $result[] = [
                'symbol'              => $pos['code'] ?? '',
                'name'                => $pos['stock_name'] ?? '',
                'asset_class'         => 'stock',
                'quantity'            => (string) ($pos['qty'] ?? '0'),
                'average_cost'        => (string) ($pos['cost_price'] ?? '0'),
                'cost_currency_code'  => $this->detectCurrency($pos['code'] ?? ''),
                'current_price'       => (string) ($pos['nominal_price'] ?? '0'),
                'price_currency_code' => $this->detectCurrency($pos['code'] ?? ''),
                'current_value'       => (string) ($pos['market_val'] ?? '0'),
                'unrealized_pnl'      => (string) ($pos['unrealized_pl'] ?? '0'),
            ];
        }

        return $result;
    }

    private function detectCurrency(string $code): string
    {
        if (str_starts_with($code, 'US.') || str_contains($code, '.US')) {
            return 'USD';
        }
        if (str_starts_with($code, 'HK.') || str_contains($code, '.HK')) {
            return 'HKD';
        }
        if (str_starts_with($code, 'SG.') || str_contains($code, '.SG')) {
            return 'SGD';
        }

        return 'USD';
    }
}
