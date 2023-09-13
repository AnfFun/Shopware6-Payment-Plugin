<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitba1f1031fcc3dd923505464b3465c0cf
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'Anf\\PaymentPlugin\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Anf\\PaymentPlugin\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitba1f1031fcc3dd923505464b3465c0cf::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitba1f1031fcc3dd923505464b3465c0cf::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitba1f1031fcc3dd923505464b3465c0cf::$classMap;

        }, null, ClassLoader::class);
    }
}
