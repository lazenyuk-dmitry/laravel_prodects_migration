<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\WbService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class WbController extends BaseController
{
    protected $wbService;

    public function __construct(WbService $wbService)
    {
        $this->wbService = $wbService;
    }

    public function syncOrders(Request $request)
    {
        $dateFrom = $request->get('dateFrom', now()->subDays(30)->format('Y-m-d'));

        $data = $this->wbService::fetchData('orders', $dateFrom);

        if (!$data) {
            return response()->json(['error' => 'No data from WB'], 500);
        }

        foreach ($data as $item) {
            // Обновляем или создаем запись (по g_number)
            Order::updateOrCreate(
                ['g_number' => $item['gNumber']],
                [
                    'date' => $item['date'],
                    'last_change_date' => $item['lastChangeDate'],
                    'supplier_article' => $item['supplierArticle'],
                    'tech_size' => $item['techSize'],
                    'barcode' => $item['barcode'],
                    'total_price' => $item['totalPrice'],
                    'discount_percent' => $item['discountPercent'],
                    'warehouse_name' => $item['warehouseName'],
                    'nm_id' => $item['nmId'],
                ]
            );
        }

        return response()->json(['status' => 'Orders synced', 'count' => count($data)]);
    }
}
