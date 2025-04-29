<?php
namespace App\Services;

use App\Models\GiftCard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;


class PlebicomService {

    protected string $baseUrl;
    protected string $login;
    protected string $credentials;
    protected ?string $token;
    protected string $cacheKey = 'plebicom_api_token';

    public function __construct(){
        $this->initSettings();
    }

    protected function initSettings(){
        $this->baseUrl = config('services.plebicom.url');
        $this->login = config('services.plebicom.login');
        $this->credentials = config('services.plebicom.pwd');
    }

    public function authenticate() {
        try{
            $cached = Cache::get($this->cacheKey);

            if($cached && isset($cached['token'], $cached['expiration'])){
                $now = now()->timestamp * 1000;
    
                if($cached['expiration'] > $now) {
                    $this->token = $cached['token'];
    
                    return $this->token;
                }
            }
    
            $url = $this->baseUrl . '/api/v1/login';
    
            $response = Http::withHeaders([
                'Accept' => 'application/json'
            ])->post($url, [
                'login' => $this->login,
                'credentials' => $this->credentials
            ]);
    
            if($response->successful() && isset($response['payload'])) {
                $this->token = $response['payload']['token'];
                $expiration = $response['payload']['expiration'];
    
                Cache::put($this->cacheKey, [
                    'token' => $this->token,
                    'expiration' => $expiration
                ], now()->addMinutes(60));
    
                return $this->token;
            }
            
            throw new Exception("Authentication failed:" . $response->status());
            
        }catch(Exception $e) {
            Log::error("Error while authenticating with Plebicom: " . $response->body());
            throw new Exception("Authentication failed, error : ", $e->getMessage());
        }
    }

    protected function ensureToken() {
        if(!$this->token){
            $this->authenticate();
        }
    }

    public function getCatalog(int $offset = 0, int $limit = 50) {
        $this->ensureToken();

        try{
            $url = $this->baseUrl . '/api/v1/service/ebons/partner';
            $response = Http::withToken($this->token)
            ->timeout(10)
            ->retry(3, 100)
            ->get($url, [
                'offset' => $offset,
                'limit' => $limit,
            ]);

            if($response->successful()) {
                $catalog = $response->json('payload') ?? [];

                $this->saveCatalogToDB($catalog);

                return [
                    'payload' => $catalog,
                    'pagination' => $response->json('paging.total') ?? count($catalog)
                ];
            }

            throw new Exception ("Failed to fetch data : ", $response->status());
        }
        catch(Exception $e){
            Log::error("Error while fetching catalog from Plebicom: " . $e->getMessage());
            throw new Exception("Failed to fetch catalog from Plebicom", 500);
        }
    }

    protected function saveCatalogToDB(array $catalog) {
        DB::transaction(function() use ($catalog){
            foreach($catalog as $item){
                GiftCard::updateOrCreate(
                ['name' => $item['name'], 'discount' => $item['discount']],
                [
                    'description' => $item['description'],
                    'min_amount' => $item['montant min'] ?? null, 
                    'max_amount' => $item['montant max'] ?? null, 
                    'image' => $item['visuel'] ?? null, 
                ]
                );
            }
        });
    }

}