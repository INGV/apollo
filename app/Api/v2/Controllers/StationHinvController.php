<?php

namespace App\Api\v2\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Api\v2\Models\StationHinvModel;
use Illuminate\Support\Facades\Validator;
use App\Api\v2\Requests\StationHinvRequest;

class StationHinvController extends Controller
{
    public function query(StationHinvRequest $request)
    {
        Log::debug("START - " . __CLASS__ . ' -> ' . __FUNCTION__);

        /* From GET, process only '$parameters_permitted' */
        $requestOnly = $request->validated();

        /* Get data */
        $data = StationHinvModel::getData($requestOnly, config('apollo.cacheTimeout'));

        /* set headers */
        $headers['Content-type'] = 'text/plain';

        Log::debug("END - " . __CLASS__ . ' -> ' . __FUNCTION__);
        return response()->make($data, 200, $headers);
    }
}
