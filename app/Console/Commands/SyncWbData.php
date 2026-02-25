<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SyncWbService;
use Carbon\Carbon;

class SyncWbData extends Command
{
    protected $signature = 'wb:sync
                        {--dateFrom= : Дата в формате YYYY-MM-DD}
                        {--dateTo= : Дата в формате YYYY-MM-DD}
                        {--type=all : Тип данных (orders, sales, stocks, incomes или all)}
                        {--limit=500 : Лимит загрузки данных за один запрос (max 1000)}';

    protected $description = 'Стянуть все данные через пагинацию API в БД';

    public function handle()
    {
        $wbService = new SyncWbService([
            'dateFrom' => $this->option('dateFrom'),
            'dateTo' => $this->option('dateTo'),
            'limit' => $this->option('limit'),
        ], $this->option('type'), $this);

        $wbService->fetch();
    }
}
