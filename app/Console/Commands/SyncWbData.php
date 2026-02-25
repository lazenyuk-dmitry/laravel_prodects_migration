<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Income;

class SyncWbData extends Command
{
    protected $signature = 'wb:sync
                        {--dateFrom= : Дата в формате YYYY-MM-DD}
                        {--dateTo= : Дата в формате YYYY-MM-DD}
                        {--type=all : Тип данных (orders, sales, stocks, incomes или all)}
                        {--limit=500 : Лимит загрузки данных за один запрос (max 1000)}';

    protected $description = 'Стянуть все данные через пагинацию API в БД';

    private $dateFromFormat = 'Y-m-d';
    private $dateToFormat = 'Y-m-d H:i:s';

    public function handle()
    {
        $dateFrom = $this->option('dateFrom')
            ? Carbon::parse($this->option('dateFrom'))->format($this-> dateFromFormat)
            : now()->subYears(2)->format($this-> dateFromFormat);
        $dateTo = $this->option('dateTo')
            ? Carbon::parse($this->option('dateTo'))->format($this-> dateToFormat)
            : now()->format($this-> dateToFormat);
        $type = $this->option('type');
        $limit = $this->option('limit');

        $token = env('WB_API_KEY');
        $host = env('WB_API_HOST');

        $allEndpoints = [
            'orders'  => Order::class,
            'sales'   => Sale::class,
            'stocks'  => Stock::class,
            'incomes' => Income::class,
        ];

        $endpoints = ($type === 'all')
            ? $allEndpoints
            : array_intersect_key($allEndpoints, [$type => '']);

        if (empty($endpoints)) {
            $this->error("Ошибка: Тип '{$type}' не поддерживается.");
            return;
        }

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
                        'dateTo' => $dateTo,
                        'key' => $token,
                        'limit' => $limit,
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
