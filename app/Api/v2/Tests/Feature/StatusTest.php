<?php

namespace App\Api\v2\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class StatusTest extends TestCase
{
    public function test_get_status()
    {
        /* Insert data and check resposne */
        $responseGet = $this->get(route('status.index'));
        $responseGet->assertStatus(200);

        $jsonStructure = [
            'status',
            'instance',
            'title',
            'detail',
            'version'
        ];

        /* Check JSON structure */
        $responseGet->assertJsonStructure($jsonStructure);
    }
}
