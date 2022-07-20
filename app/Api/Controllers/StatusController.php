<?php

namespace App\Api\Controllers;

use App\Http\Controllers\Controller;

class StatusController extends Controller
{
    public function index()
    {
        $status = 200;
        $statusMessage = \Symfony\Component\HttpFoundation\Response::$statusTexts[$status] ? \Symfony\Component\HttpFoundation\Response::$statusTexts[$status] : '--';
        if ($status == 200) {
            $message = 'The service is working properly';
        } else {
            $message = $statusMessage;
        }

        return response([
            'status' => $status,
            'title' => $statusMessage,
            'detail' => $message,
        ])->header('Content-Type', 'application/problem+json');
    }
}
