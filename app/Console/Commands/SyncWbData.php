<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Order;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Income;

class SyncWbData extends Command
{
    protected $signature = 'wb:sync';
    protected $description = 'Стянуть все данные через пагинацию API в БД';

    public function handle()
    {
        $dateFrom = '2026-02-23'; // Можно вынести в настройки
        $token = env('WB_API_KEY');
        $host = env('WB_API_HOST');

        $endpoints = [
            'orders'  => Order::class,
            // 'sales'   => Sale::class,
            // 'stocks'  => Stock::class,
            // 'incomes' => Income::class,
        ];

        foreach ($endpoints as $path => $modelClass) {
            $this->info("=== Начинаю загрузку: {$path} ===");

            $currentPage = 1;
            $lastPage = 1;

            while ($lastPage - $currentPage >= 0) {
                $this->comment("Загружаю страницу: " . $currentPage);

                $response = Http::retry(3, 100)
                    ->timeout(10)
                    ->get("{$host}/api/{$path}", [
                        'dateFrom' => $dateFrom,
                        'dateTo' => now()->format('Y-m-d'),
                        'key' => $token,
                        'limit' => 100,
                        'page' => $currentPage,
                    ]);

                if ($response->failed()) {
                    $this->error("Ошибка API: " . $response->status() . " - " . $response->body());
                    break;
                }

                $json = $response->json();
                $items = $json['data'] ?? [];
                $meta = $json['meta'] ?? [];
                $fetched = $meta['to'] ?? 0;
                $total = $meta['total'] ?? 0;
                $lastPage = $meta['last_page'] ?? $currentPage;

                // Сохраняем данные текущей страницы
                foreach ($items as $item) {
                    $modelClass::updateOrCreate(
                        $this->getUniqueKeys($path, $item),
                        $item
                    );
                }

                $this->info("Сохранено " . count($items) . " записей");
                $this->info("Общий прогресс " . $fetched . "/" . $total);

                $currentPage++;

                if ($total - $fetched > 0) {
                    sleep(1); // 1 сек
                } else {
                    $this->info("=== Загрузка завершена: {$path} === \n");
                }
            };
        }

        $this->info("Синхронизация полностью завершена!");
    }

    private function getUniqueKeys($path, $item) {
        return match($path) {
            'orders'  => ['g_number' => $item['g_number']],
            'sales'   => ['sale_id' => $item['sale_id'] ?? $item['g_number']],
            'stocks'  => ['nm_id' => $item['nm_id'], 'warehouse_name' => $item['warehouse_name']],
            'incomes' => ['income_id' => $item['income_id']],
            default   => ['id' => $item['id'] ?? null]
        };
    }
}
