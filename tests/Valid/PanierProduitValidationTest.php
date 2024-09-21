<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PanierProduitValidationTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        // $routerService = static::getContainer()->get('router');
        // $myCustomService = static::getContainer()->get(CustomService::class);
    }
}
