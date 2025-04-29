<?php

namespace App\Http\Controllers;

use App\Services\PlebicomService;
use Illuminate\Http\Request;
use Exception;

class PlebicomController extends Controller
{
    protected $plebicomService;

    public function __construct(PlebicomService $plebicomService){
        $this->plebicomService = $plebicomService;
    }

    public function listCatalog(Request $request)
    {
        $offset = (int) $request->query('offset', 0);
        $limit = (int) $request->query('limit', 50);

        try{
            $result = $this->plebicomService->getCatalog($offset, $limit);

            return [
               'payload' => $result,
                'pagination' => $result['paging.total'] ?? null
            ];

        } catch(Exception $e) {
            return [ 
                'error' => true,
                'message' => $e->getMessage()
            ];
        }

    }
}
