<?php

namespace App\Api\v2\Jobs;

use App\Api\v2\Models\StationHinvModel;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StationHinvJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $data;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 10;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags()
    {
        return ['v.'.config('apollo.version'), 'class:'.substr(strrchr(__CLASS__, '\\'), 1)];
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::debug('***** Job('.__CLASS__.'): '.$this->data['count'].'/'.$this->data['total'].' - SCNL='.$this->data['net'].'.'.$this->data['sta'].'.'.$this->data['cha'].' ***** ');
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...
            Log::info(' batch cancelled');

            return;
        }

        Log::info('START - '.__CLASS__.' -> '.__FUNCTION__);
        $r = StationHinvModel::getData([
            'net' => $this->data['net'],
            'sta' => $this->data['sta'],
            'cha' => $this->data['cha'],
            'cache' => $this->data['cache'],
        ], config('apollo.cacheTimeout'));

        /*
        $cacheKey = $this->data['random_string'];
        if (Cache::has($cacheKey)) {
            $o = Cache::get($cacheKey);
            Cache::put($cacheKey, $o.$r, $seconds = 60);
        } else {
            Cache::put($cacheKey, $r, $seconds = 60);
        }
        */
        Log::info('END - '.__CLASS__.' -> '.__FUNCTION__);
    }
}
