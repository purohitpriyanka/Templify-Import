<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit8283c873e984087f8020f756403ba3f9
{
    public static $prefixLengthsPsr4 = array (
        'K' => 
        array (
            'AwesomeMotive\\WPContentImporter2\\' => 33,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'TemplifyWP\\TemplifyImporterTemplates\\' => 
        array (
            0 => __DIR__ . '/../..' . '/include/resources',
        ),
        'AwesomeMotive\\WPContentImporter2\\' => 
        array (
            0 => __DIR__ . '/../..' . '/wxr-importer',
        ),
    );

    public static $classMap = array (
        'AwesomeMotive\\WPContentImporter2\\Importer' => __DIR__ . '/../..' . '/wxr-importer/Importer.php',
        'AwesomeMotive\\WPContentImporter2\\WPImporterLogger' => __DIR__ . '/../..' . '/wxr-importer/WPImporterLogger.php',
        'AwesomeMotive\\WPContentImporter2\\WPImporterLoggerCLI' => __DIR__ . '/../..' . '/wxr-importer/WPImporterLoggerCLI.php',
        'AwesomeMotive\\WPContentImporter2\\WXRImportInfo' => __DIR__ . '/../..' . '/wxr-importer/WXRImportInfo.php',
        'AwesomeMotive\\WPContentImporter2\\WXRImporter' => __DIR__ . '/../..' . '/wxr-importer/WXRImporter.php',
        // 'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit8283c873e984087f8020f756403ba3f9::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit8283c873e984087f8020f756403ba3f9::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit8283c873e984087f8020f756403ba3f9::$classMap;

        }, null, ClassLoader::class);
    }
}
