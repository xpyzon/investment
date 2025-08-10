<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

class InvestmentController
{
    private Request $request;
    private Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function products(): void
    {
        $pdo = Database::pdo();
        $rows = $pdo->query('SELECT id, name, type, symbol, min_invest, max_invest FROM products WHERE enabled = 1 ORDER BY type, name')->fetchAll(PDO::FETCH_ASSOC);
        $this->response->json($rows);
    }

    public function invest(): void
    {
        $userId = Auth::requireUser($this->request->header('X-User-Id'));
        $data = $this->request->input();
        $productId = (int)($data['product_id'] ?? 0);
        $amount = (float)($data['amount'] ?? 0);
        if ($productId<=0 || $amount<=0) { $this->response->json(['error'=>'Invalid request'],422); return; }

        $pdo = Database::pdo();
        $p = $pdo->prepare('SELECT * FROM products WHERE id = ? AND enabled = 1');
        $p->execute([$productId]);
        $product = $p->fetch(PDO::FETCH_ASSOC);
        if (!$product) { $this->response->json(['error'=>'Product not found'],404); return; }
        if ($product['min_invest'] > 0 && $amount < (float)$product['min_invest']) { $this->response->json(['error'=>'Below minimum'],422); return; }
        if ($product['max_invest'] > 0 && $amount > (float)$product['max_invest']) { $this->response->json(['error'=>'Above maximum'],422); return; }

        // For simplicity: 1 unit = amount at price 1.0
        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare('INSERT INTO investments (user_id, product_id, type, units, entry_price, status) VALUES (?,?,?,?,?,?)');
            $ins->execute([$userId, $productId, $product['type'], $amount, 1.0, 'active']);
            $txn = $pdo->prepare('INSERT INTO transactions (user_id, type, amount, currency, meta) VALUES (?,?,?,?,?)');
            $txn->execute([$userId, 'buy', $amount, 'USD', json_encode(['product_id'=>$productId])]);
            $pdo->commit();
            $this->response->json(['ok'=>true]);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->response->json(['error'=>'Failed to invest','message'=>$e->getMessage()],500);
        }
    }
}