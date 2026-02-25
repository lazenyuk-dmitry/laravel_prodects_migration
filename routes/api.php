<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\SyncWbService;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/sync', function (Request $request) {
    config(['octane.max_execution_time' => 3600]);

    $params = [
        'dateFrom' => '2000-01-01',
    ];

    $orders = new SyncWbService($params, 'orders');
    $sales = new SyncWbService($params, 'sales');
    $incomes = new SyncWbService($params, 'incomes');
    $stocks = new SyncWbService($params, 'stocks');

    Octane::concurrently([
        fn() => $orders->fetch(),
        fn() => $sales->fetch(),
        fn() => $incomes->fetch(),
        fn() => $stocks->fetch(),
    ], 36000);

    return "Завершено";
});
