<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Income;

class SyncWbService
{
    private array $params;
    private $token;
    private $host;
    private $endpoints;
    private $console;

    private $dateFromFormat = 'Y-m-d';
    private $dateToFormat = 'Y-m-d H:i:s';

    public function __construct($params, $type='all', $console = null)
    {

        $defaults = [
            'dateFrom' => now()->subYears(2)->format($this->dateFromFormat),
            'dateTo'   => now()->format($this->dateToFormat),
            'limit'    => 500,
        ];

        $this->params = array_merge($defaults, array_filter($params));
        $this->console = $console;
        $this->host = env('WB_API_HOST');
        $this->token = env('WB_API_KEY');

        $allEndpoints = [
            'orders'  => Order::class,
            'sales'   => Sale::class,
            'stocks'  => Stock::class,
            'incomes' => Income::class,
        ];

        $this->endpoints = ($type === 'all')
            ? $allEndpoints
            : array_intersect_key($allEndpoints, [$type => '']);

        if (empty($this->endpoints)) {
            $this->error("Ошибка: Тип '{$type}' не поддерживается.");
            return;
        }
    }

    public function fetch()
    {
        $this->log("Начало синхронизации c {$this->params['dateFrom']} по {$this->params['dateTo']} для типов: " . implode(', ', array_keys($this->endpoints)));

        foreach ($this->endpoints as $path => $modelClass) {
            $this->fetchOne($path, $this->params, $modelClass);
        }

        $this->log("Синхронизация полностью завершена!");
    }

    private function fetchOne($path, $params, $modelClass) {
        $this->log("=== Начинаю загрузку: {$path} ===");

        $currentPage = 1;
        $lastPage = 1;

        while ($lastPage - $currentPage >= 0) {
            $this->log("Загружаю страницу: " . $currentPage);

            $response = Http::retry(3, 100)
                ->timeout(10)
                ->get("{$this->host}/api/{$path}", [
                    'dateFrom' => Carbon::parse($params['dateFrom'])->format($this-> dateFromFormat),
                    'dateTo' => Carbon::parse($params['dateTo'])->format($this-> dateToFormat),
                    'key' => $this->token,
                    'limit' => $params['limit'],
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

            $this->update($path, $modelClass, $items);

            $this->log("Сохранено " . count($items) . " записей");
            $this->log("Общий прогресс " . $fetched . "/" . $total);

            $currentPage++;

            if ($total - $fetched > 0) {
                sleep(1); // 1 сек
            } else {
                $this->log("=== Загрузка завершена: {$path} === \n");
            }
        };
    }

    private function update($path, $modelClass, $items) {
        $uniqueKeys = array_keys($this->getUniqueKeys($path, $items[0]));
        $updateKeys = array_keys($items[0]);

        $modelClass::upsert(
            $items,
            $updateKeys,
            array_keys($items[0])
        );
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

    private function log(string $message, string $type = 'info'): void
    {
        if ($this->console) {
            $this->console->$type($message);
        } else {
            Log::$type($message);
        }
    }
}
