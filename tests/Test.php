<?php

use App\Status;

class Test extends TestCase
{
    /**
     * Tests the game/versions endpoint.
     *
     * @return void
     */
    public function testVersions()
    {

        $response = $this->json('GET', 'game/versions')->response->getOriginalContent();

        $this->assertResponseStatus(Status::OK());
        self::assertDefaultJsonStructure($response);
        self::assertDataKeys(['versions', 'latest'], $response);

    }

}