<?php

declare(strict_types=1);

namespace Tests\Commands\Upgrade\V6_16_0;

use Artisan;

class CommandTest extends \Tests\Commands\Upgrade\TestCase
{
    public function testCommand()
    {
        Artisan::call('upgrade:v6.16.0');
    }
}
