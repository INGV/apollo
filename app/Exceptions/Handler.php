<?php

namespace App\Exceptions;

use App\Dante\Events\ExceptionWasThrownEvent;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        Log::debug('START - '.__CLASS__.' -> '.__FUNCTION__);

        /* 1/2 - Build array to trigger the Event 'ExceptionWasThrownEvent' to send email */
        $eventArray['url'] = $request->fullUrl();
        $eventArray['random_string'] = config('ingv-logging.random_string');
        $eventArray['log_file'] = config('ingv-logging.log_file');

        /* 1/2 - Build RCF7807 Problem Details for HTTP APIs: https://tools.ietf.org/html/rfc7807 */
        $rfc7807Output['type'] = 'unknown';
        $rfc7807Output['title'] = 'unknown';
        $rfc7807Output['status'] = 500;
        $rfc7807Output['detail'] = 'unknown';
        $rfc7807Output['instance'] = $request->fullUrl();
        $rfc7807Output['version'] = config('dante.version');
        $rfc7807Output['request_submitted'] = date("Y-m-d\TH:m:s T");

        /* Set header to get JSON render exception */
        $request->headers->set('Accept', 'application/json');

        /* Get default render */
        $defaultRender = parent::render($request, $exception);

        /* Set errors array */
        $emailErrors = '';
        //if (method_exists($defaultRender, 'getData')) {
        if (isset($defaultRender->getData()->errors)) {
            /* for output API */
            $rfc7807Output['errors'] = (array) $defaultRender->getData()->errors;
            /* for email */
            $emailErrors = json_encode($rfc7807Output['errors']);
        }

        /* Get status */
        $status = null;
        if ($exception instanceof \Illuminate\Database\QueryException) {
            if (config('database.connections.'.config('database.default'))['driver'] == 'pgsql') {
                if ($exception->getCode() == 23505) { // Unique violation
                    $status = 409;
                }
            } elseif (config('database.connections.'.config('database.default'))['driver'] == 'mysql') {
                if ($exception->getCode() == 1062) { // Unique violation
                    $status = 409;
                }
            }
        }
        if (is_null($status)) {
            $status = (parent::isHttpException($exception) ? $exception->getStatusCode() : ($defaultRender->getStatusCode() ? $defaultRender->getStatusCode() : 500));
        }

        /* Get status_code and status_message */
        $statusMessage = Response::$statusTexts[$status] ? Response::$statusTexts[$status] : '--';
        $message = $defaultRender->getData()->message;

        /* 2/2 - Build RCF7807 Problem Details for HTTP APIs: https://tools.ietf.org/html/rfc7807 */
        $rfc7807Output['type'] = config('dante.rfc7231')[$status] ?? 'about:blank';
        $rfc7807Output['title'] = $statusMessage;
        $rfc7807Output['status'] = $status;
        $rfc7807Output['detail'] = $message ? $message : $statusMessage;

        /* 2/2 - Build array to trigger the Event 'DanteExceptionWasThrownEvent' to send email */
        $eventArray['message'] = $exception->getMessage() ? $exception->getMessage() : '--';
        $eventArray['status'] = $status;
        $eventArray['statusMessage'] = $statusMessage;
        $eventArray['message'] .= ' - '.$emailErrors.' - '.$exception->getFile().':'.$exception->getLine();

        /* Set header to 'application/problem+json' */
        $defaultRender->header('Content-type', 'application/problem+json');

        /* Add debug */
        if (config('app.debug')) {
            $rfc7807Output['debug'] = (array) $defaultRender->getData();
        }

        /* Set output with new fields */
        $defaultRender->setData($rfc7807Output);
        $defaultRender->setStatusCode($status);

        /* set output */
        $prepareOutput = $defaultRender;

        /* print into log */
        Log::debug(' exception:', $rfc7807Output);

        /* Trigger the event */
        /*
        try {
            event(new ExceptionWasThrownEvent($eventArray));
        } catch (\Swift_TransportException $e) {
            \Log::error(" Error sending email: " . $e->getMessage());
        }
        */
        /* Empty cache in case of Exception. Issue: https://gitlab.rm.ingv.it/caravel/dante8/-/issues/74 */
        if (config('dante.enableQueryCache')) {
            Log::debug(' empty cache!');
            Artisan::call('cache:clear');
        }

        Log::debug('END - '.__CLASS__.' -> '.__FUNCTION__);

        return $prepareOutput;
    }
}
