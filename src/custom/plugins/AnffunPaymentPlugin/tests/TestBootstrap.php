<?php declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

$loader = (new TestBootstrapper())
    ->addCallingPlugin()
    ->addActivePlugins('AnffunPaymentPlugin')
    ->bootstrap()
    ->getClassLoader();

$loader->addPsr4('AnffunPaymentPlugin\\Tests\\', __DIR__);