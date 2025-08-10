<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

class MarketController
{
    private Request $request;
    private Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function prices(): void
    {
        $q = $this->request->query();
        $ids = $q['ids'] ?? 'bitcoin,ethereum';
        $vs = $q['vs'] ?? 'usd';
        $url = 'https://api.coingecko.com/api/v3/simple/price?ids=' . urlencode($ids) . '&vs_currencies=' . urlencode($vs);
        $data = @file_get_contents($url);
        if ($data === false) { $this->response->json(['error'=>'market fetch failed'], 502); return; }
        $this->response->json(json_decode($data, true) ?: []);
    }

    public function chart(): void
    {
        $q = $this->request->query();
        $id = $q['id'] ?? 'bitcoin';
        $vs = $q['vs'] ?? 'usd';
        $days = $q['days'] ?? '1';
        $interval = $q['interval'] ?? 'hourly';
        $url = 'https://api.coingecko.com/api/v3/coins/' . rawurlencode($id) . '/market_chart?vs_currency=' . urlencode($vs) . '&days=' . urlencode($days) . '&interval=' . urlencode($interval);
        $data = @file_get_contents($url);
        if ($data === false) { $this->response->json(['error'=>'chart fetch failed'], 502); return; }
        $this->response->json(json_decode($data, true) ?: []);
    }
}