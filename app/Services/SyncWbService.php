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
            $this->log("Ошибка: Тип '{$type}' не поддерживается.", 'error');
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

        $params = $this->applyParamsFormat($path, $params);

        while ($lastPage - $currentPage >= 0) {
            $this->log("Загружаю страницу: " . $currentPage);

            $response = Http::retry(3, 2000)
                ->timeout(15)
                ->get("{$this->host}/api/{$path}", [
                    'dateFrom' => $params['dateFrom'],
                    'dateTo' => $params['dateTo'],
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
            $this->log("Общий прогресс {$path}: " . $fetched . "/" . $total);

            $currentPage++;

            if ($total - $fetched > 0) {
                sleep(1); // сек
            } else {
                $this->log("=== Загрузка завершена: {$path} === \n");
            }
        };
    }

    private function update($path, $modelClass, $items) {
        if (empty($items)) return;

        $modelClass::upsert(
            $items,
            $this->getUniqueKeys($path),
            array_keys($items[0])
        );
    }

    private function getUniqueKeys($path) {
        return match($path) {
            'orders'  => ['g_number'],
            'sales'   => ['sale_id', 'g_number'],
            'stocks'  => ['nm_id', 'warehouse_name', 'barcode'],
            'incomes' => ['income_id', 'barcode'],
            default   => ['id']
        };
    }

    private function applyParamsFormat(string $path, array $params): array
    {
        $minDate = match($path) {
            'stocks' => now(),
            default => null
        };

        $params['dateFrom'] = Carbon::parse($params['dateFrom'])->format($this-> dateFromFormat);
        $params['dateTo'] = Carbon::parse($params['dateTo'])->format($this-> dateToFormat);

        if ($minDate) {
            $inputDate = Carbon::parse($params['dateFrom']);

            if ($inputDate->lessThan($minDate)) {
                $params['dateFrom'] = $minDate->format($this->dateFromFormat);
                $this->log("Дата 'dateFrom' для {$path} ограничена: " . $params['dateFrom']);
            }
        }

        if ($path == 'incomes') {
            $params['dateTo'] = Carbon::parse($params['dateTo'])->format($this->dateFromFormat);
        }

        return $params;
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
