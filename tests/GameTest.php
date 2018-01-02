<?php

use App\Status;

class GameTest extends Laravel\Lumen\Testing\TestCase
{
    /**
     * Tests the game/versions endpoint.
     *
     * @return void
     */
    public function testExample()
    {

        $response = $this->json('GET', 'game/versions')->response->getOriginalContent();

        $this->assertResponseStatus(Status::OK());
        self::assertDefaultJsonStructure($response);
        self::assertDataKeys(['versions', 'latest'], $response);

    }

    /**
     * Creates the application.
     *
     * Needs to be implemented by subclasses.
     *
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }
}