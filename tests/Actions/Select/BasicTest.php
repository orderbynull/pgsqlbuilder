<?php

declare(strict_types=1);

/**
 * Class BasicTest
 */
class BasicTest extends BaseTest
{
    public function testName()
    {
        $this->assertTrue($this->equal('SELECT 1 as a', '[{"a":1}]'));
    }

    protected function up(): void
    {
        // TODO: Implement up() method.
    }
}