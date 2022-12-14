<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit55cc0d1f2b6a14892ffcbe5dab4be6fd
{
    public static $prefixLengthsPsr4 = array (
        'N' => 
        array (
            'NarrysTech\\Api_Rest\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'NarrysTech\\Api_Rest\\' => 
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
            $loader->prefixLengthsPsr4 = ComposerStaticInit55cc0d1f2b6a14892ffcbe5dab4be6fd::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit55cc0d1f2b6a14892ffcbe5dab4be6fd::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit55cc0d1f2b6a14892ffcbe5dab4be6fd::$classMap;

        }, null, ClassLoader::class);
    }
}
