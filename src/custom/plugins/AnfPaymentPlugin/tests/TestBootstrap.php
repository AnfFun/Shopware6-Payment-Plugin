<?php declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

$loader = (new TestBootstrapper())
    ->addCallingPlugin()
    ->addActivePlugins('AnfPaymentPlugin')
    ->bootstrap()
    ->getClassLoader();

$loader->addPsr4('AnfPaymentPlugin\\Tests\\', __DIR__);