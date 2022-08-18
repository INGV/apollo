<?php

namespace App\Api\v2\Controllers;

use App\Api\v2\Models\StationHinvModel;
use App\Api\v2\Requests\StationHinvRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class StationHinvController extends Controller
{
    public function query(StationHinvRequest $request)
    {
        Log::debug('START - '.__CLASS__.' -> '.__FUNCTION__);

        /* From GET, process only '$parameters_permitted' */
        $requestOnly = $request->validated();

        /* Get data */
        $data = StationHinvModel::getData($requestOnly, config('apollo.cacheTimeout'));

        /* set headers */
        $headers['Content-type'] = 'text/plain';

        Log::debug('END - '.__CLASS__.' -> '.__FUNCTION__);

        return response()->make($data, 200, $headers);
    }
}
