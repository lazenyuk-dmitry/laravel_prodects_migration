<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WbService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.wb.key') ?? env('WB_API_KEY');
        $this->baseUrl = config('services.wb.host') ?? env('WB_API_HOST');
    }

    public function fetchData($endpoint, $dateFrom)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey
            ])->get("{$this->baseUrl}/api/v1/supplier/{$endpoint}", [
                'dateFrom' => $dateFrom,
                'key' => $this->apiKey
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("WB API Error {$endpoint}: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("WB Connection Error: " . $e->getMessage());
            return null;
        }
    }
}
