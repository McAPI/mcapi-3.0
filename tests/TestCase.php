<?php

use PHPUnit\Framework\Assert;

abstract class TestCase extends Laravel\Lumen\Testing\TestCase
{
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    public static function assertJsonStructure(array $structure, array $responseData)
    {
        foreach ($structure as $key => $value) {
            if (is_array($value) && $key === '*') {
                Assert::assertInternalType('array', $responseData);
                foreach ($responseData as $responseDataItem) {
                    self::ssertJsonStructure($structure['*'], $responseDataItem);
                }
            } elseif (is_array($value)) {
                Assert::assertArrayHasKey($key, $responseData);
                self::assertJsonStructure($structure[$key], $responseData[$key]);
            } else {
                Assert::assertArrayHasKey($value, $responseData);
            }
        }

    }

    public static function assertDefaultJsonStructure(array $responseData)
    {
        self::assertJsonStructure([
            'meta'  => [
                'status',
                'message',
            ],
            'data',
            'cache' => [
                'stored',
                'updated',
                'expires',
            ]
        ], $responseData);
    }

    public static function assertDataKeys(array $keys, array $responseData)
    {

        $data = $responseData['data'];
        foreach ($keys as $key) {

            $current = $data;
            $path = explode('.', $key);

            foreach ($path as $entry) {

                Assert::assertArrayHasKey($entry, $current, sprintf("Data Integrity Violation: '%s' is missing.", $key));
                $current = $current[$entry];

            }

        }

    }

}
