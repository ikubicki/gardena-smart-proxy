<?php

echo "Building PHAR application with full vendor...\n";

if (!class_exists('Phar')) {
    echo "Error: PHAR extension is not enabled.\n";
    exit(1);
}

if (ini_get('phar.readonly')) {
    echo "Error: PHAR creation is disabled by phar.readonly setting.\n";
    echo "Run with: php -d phar.readonly=0 tools/build.php\n";
    exit(1);
}

if (!is_dir('dist')) {
    mkdir('dist', 0755, true);
}

$pharFile = 'dist/app.phar';

if (file_exists($pharFile)) {
    unlink($pharFile);
}

try {
    $phar = new Phar($pharFile);
    $phar->startBuffering();

    echo "Adding source files...\n";
    $srcIterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator('src/', RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    $srcCount = 0;
    foreach ($srcIterator as $file) {
        if ($file->getExtension() === 'php') {
            $relativePath = str_replace('\\', '/', $file->getPathname());
            $phar->addFile($file->getPathname(), $relativePath);
            $srcCount++;
        }
    }
    echo "  ✓ Added $srcCount source files\n";

    echo "Adding ALL vendor files...\n";
    $vendorIterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator('vendor/', RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    $vendorCount = 0;
    foreach ($vendorIterator as $file) {
        if ($file->isFile()) {
            $relativePath = str_replace('\\', '/', $file->getPathname());
            $phar->addFile($file->getPathname(), $relativePath);
            $vendorCount++;

            if ($vendorCount % 100 === 0) {
                echo "    Processed $vendorCount vendor files...\n";
            }
        }
    }
    echo "  ✓ Added $vendorCount vendor files\n";

    $indexContent = '<?php
Phar::mapPhar("app.phar");

require_once "phar://app.phar/vendor/autoload.php";

__HALT_COMPILER();
';
    
    $phar->setStub($indexContent);

    echo "Compressing PHAR...\n";
    $phar->compressFiles(Phar::GZ);
    
    $phar->stopBuffering();
    
    echo "\n✓ PHAR created successfully!\n";
    echo "File: $pharFile\n";
    echo "Size: " . number_format(filesize($pharFile)) . " bytes\n";

    $deployIndex = '<?php

// Gardena Proxy Application

require_once __DIR__ . "/app.phar";
require_once __DIR__ . "/config.php";

use GardenaProxy\Proxy;
use GardenaProxy\Config;
use Slim\Factory\AppFactory;

$app = AppFactory::create();
$app->setBasePath("");
$config = new Config();
$proxy = new Proxy($app, $config);
$proxy->setup();
$proxy->run();

';
    
    file_put_contents('dist/index.php', $deployIndex);
    
    // .htaccess
    $htaccess = "RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L,E=PATH_INFO:/$1]
";
    file_put_contents('dist/.htaccess', $htaccess);

    $composerJson = json_decode(file_get_contents('composer.json'));
    $appVersion = $composerJson->version ?? 'dev';
    $configContent = '<?php

use GardenaProxy\Config;

Config::set(\'VERSION\', \'' . $appVersion . '\');
Config::set(\'SQLITE_DIR\', __DIR__ . \'/sqlite\');
Config::set(\'BASIC_AUTH_EXCLUDE\', implode(\',\', [\'/favicon.ico\', \'/callbacks\']));
Config::set(\'BASIC_AUTH_USER\', \'gardena\');
Config::set(\'BASIC_AUTH_PASSWORD\', \'proxy\');
Config::set(\'GARDENA_API_URL\', \'https://api.smart.gardena.dev\');
Config::set(\'GARDENA_API_CLIENT_ID\', \'<Your Gardena API Client ID>\');
Config::set(\'GARDENA_API_CLIENT_SECRET\', \'<Your Gardena API Client Secret>\');
Config::set(\'GARDENA_AUTH_URL\', \'https://api.authentication.husqvarnagroup.dev/v1/oauth2/token\');
';
    
    file_put_contents('dist/config.php', $configContent);

    echo "\n✓ Deployment files ready in dist/\n";
    echo "  - index.php (entry point)\n";
    echo "  - app.phar (complete application)\n";
    echo "  - .htaccess (Apache rewrite rules)\n";
    echo "  - config.php (application configuration)\n";
    
    echo "\nDeployment info:\n";
    echo "  - Upload dist/ contents to your server\n";
    echo "  - Modify config.php with your settings\n";
    echo "  - Ensure sqlite/ directory is writable\n";
    echo "  - Application will be available at http://your-domain/\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
