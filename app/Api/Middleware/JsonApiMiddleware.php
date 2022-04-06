<?php

namespace App\Api\Middleware;

use Closure;

class JsonApiMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        \Log::debug("START - " . __CLASS__ . ' -> ' . __FUNCTION__);

        $checkJson = false;

        /* Get and split 'Content-Type' */
        if ($request->header('Content-Type')) {
            $contentType__exploded = explode(";", strtolower(str_replace(' ', '', $request->header('Content-Type'))));
            $contentType = $contentType__exploded[0] ?? null;
            $charset = $contentType__exploded[1] ?? null;
        }

        /* Check Content-Type */
        if ($request->isMethod('POST') || $request->isMethod('PUT')) {
            if ($contentType != 'application/json' || ($charset != 'charset=utf-8' && $charset != null)) {
                abort(415, 'Unsupported Media Type; your header "Content-Type=["' . $request->header('Content-Type') . '"]" but must be "Content-Type=["application/json"]"');
            }
            $checkJson = true;
        } elseif ($request->isMethod('PATCH')) {
            if ($request->header('Content-Type') != 'application/merge-patch+json' || ($charset != 'charset=utf-8' && $charset != null)) { //RFC 7386 - https://forum.italia.it/t/le-insidie-di-put-e-patch-nella-scrittura-di-api/14479
                abort(415, 'Unsupported Media Type; your header "Content-Type=["' . $request->header('Content-Type') . '"]" but must be "Content-Type=["application/merge-patch+json"]"');
            }
            $checkJson = true;
        }

        /* Check input data is valid JSON */
        if ($checkJson) {
            json_decode($request->getContent());
            if (json_last_error() != JSON_ERROR_NONE) {
                abort(400, 'Bad Request - Input data must be a valid JSON!');
            }
        }

        \Log::debug("END - " . __CLASS__ . ' -> ' . __FUNCTION__);
        return $next($request);
    }
}
