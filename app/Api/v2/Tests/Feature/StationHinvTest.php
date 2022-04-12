<?php

namespace App\Api\v2\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class StationHinvTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // additional setup
    }

    public function test_get_successful()
    {
        $this->withoutExceptionHandling();

        $input = [
            'net'    => 'IV',
            'sta'    => 'ACER',
            'cha'    => 'HHZ',
            'loc'    => '--',
            'starttime' => '2021-03-24'
        ];

        $response = $this->get(route('v2.location.station-hinv', $input));
        $response->assertSuccessful();
        $response->assertSee('ACER  IV ZHHZ  40  472020N 15  565620E 690     1  0.00  0.00  0.00  0.00 0  1.00--');
    }
}
