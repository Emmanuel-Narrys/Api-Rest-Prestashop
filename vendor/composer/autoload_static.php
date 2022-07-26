<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit55cc0d1f2b6a14892ffcbe5dab4be6fd
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'ApiRest\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ApiRest\\' => 
        array (
            0 => __DIR__ . '/../..' . '/',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit55cc0d1f2b6a14892ffcbe5dab4be6fd::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit55cc0d1f2b6a14892ffcbe5dab4be6fd::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}