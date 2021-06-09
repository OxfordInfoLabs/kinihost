<?php

namespace Kinihost;

use Kinikit\Core\Bootstrapper;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\ORM\Mapping\ORMMapping;
use Kinikit\Persistence\Tools\TestDataInstaller;


class TestBase extends \PHPUnit\Framework\TestCase {

    private static $run = false;

    public static function setUpBeforeClass(): void {

        if (!self::$run) {
            $testDataInstaller = Container::instance()->get(TestDataInstaller::class);
            $testDataInstaller->run(true, ["../src"]);

            // Clear mappings
            ORMMapping::clearMappings();


            Container::instance()->get(Bootstrapper::class);

            self::$run = true;

            passthru("rm -rf FileStorage/*");


        }
    }

}

