#!/usr/bin/env php
<?php
/**
 * eBay Connector Package Test Script
 * 
 * This script validates the package structure and basic functionality
 * WITHOUT requiring a full Bagisto installation.
 */

echo "====================================\n";
echo "eBay Connector Package Test Suite\n";
echo "====================================\n\n";

$errors = [];
$warnings = [];
$passed = 0;
$failed = 0;

// Test 1: Check directory structure
echo "Test 1: Directory Structure... ";
$requiredDirs = [
    'src',
    'src/Providers',
    'src/Services',
    'src/Models',
    'src/Http/Controllers/Admin',
    'src/Console/Commands',
    'src/Database/Migrations',
    'resources/views/admin',
    'resources/lang/en',
    'publishable/config',
    'publishable/assets',
    'publishable/assets/css',
    'publishable/assets/fonts/ebay',
];

$dirTest = true;
foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        $errors[] = "Missing directory: $dir";
        $dirTest = false;
    }
}

if ($dirTest) {
    echo "✓ PASSED\n";
    $passed++;
} else {
    echo "✗ FAILED\n";
    $failed++;
}

// Test 2: Check required files
echo "Test 2: Required Files... ";
$requiredFiles = [
    'composer.json',
    'package.json',
    'README.md',
    'LICENSE',
    'INSTALLATION.md',
    'GITHUB_INSTALL.md',
    'CHECKLIST.md',
    'src/Providers/EbayConnectorServiceProvider.php',
    'src/Services/EbayApiClient.php',
    'src/Services/ProductSyncService.php',
    'src/Services/OrderSyncService.php',
    'publishable/config/ebayconnector.php',
    'publishable/config/menu.php',
    'publishable/config/acl.php',
];

$fileTest = true;
foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        $errors[] = "Missing file: $file";
        $fileTest = false;
    }
}

if ($fileTest) {
    echo "✓ PASSED\n";
    $passed++;
} else {
    echo "✗ FAILED\n";
    $failed++;
}

// Test 3: Validate composer.json
echo "Test 3: Composer Configuration... ";
$composerJson = json_decode(file_get_contents('composer.json'), true);

if (!$composerJson) {
    echo "✗ FAILED (Invalid JSON)\n";
    $failed++;
    $errors[] = "composer.json is not valid JSON";
} else {
    $composerTest = true;
    
    if ($composerJson['name'] !== 'kevinbharris/ebayconnector') {
        $errors[] = "Package name is not 'kevinbharris/ebayconnector'";
        $composerTest = false;
    }
    
    if ($composerJson['license'] !== 'MIT') {
        $errors[] = "License is not MIT";
        $composerTest = false;
    }
    
    if (!isset($composerJson['autoload']['psr-4']['KevinBHarris\\EbayConnector\\'])) {
        $errors[] = "PSR-4 autoload not configured correctly";
        $composerTest = false;
    }
    
    if ($composerTest) {
        echo "✓ PASSED\n";
        $passed++;
    } else {
        echo "✗ FAILED\n";
        $failed++;
    }
}

// Test 4: Check PHP syntax
echo "Test 4: PHP Syntax Check... ";
$phpFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('src')
);

$syntaxTest = true;
foreach ($phpFiles as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $output = [];
        $return = 0;
        exec("php -l " . escapeshellarg($file->getPathname()) . " 2>&1", $output, $return);
        
        if ($return !== 0) {
            $errors[] = "Syntax error in: " . $file->getPathname();
            $syntaxTest = false;
        }
    }
}

if ($syntaxTest) {
    echo "✓ PASSED\n";
    $passed++;
} else {
    echo "✗ FAILED\n";
    $failed++;
}

// Test 5: Check namespace consistency
echo "Test 5: Namespace Consistency... ";
$namespaceTest = true;
$expectedNamespace = 'KevinBHarris\\EbayConnector';

foreach ($phpFiles as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        
        // Skip migration files
        if (strpos($file->getPathname(), 'Migrations') !== false) {
            continue;
        }
        
        // Skip route files
        if (strpos($file->getPathname(), 'routes.php') !== false) {
            continue;
        }
        
        if (preg_match('/^namespace\s+([^;]+);/m', $content, $matches)) {
            $namespace = $matches[1];
            if (strpos($namespace, $expectedNamespace) !== 0) {
                $errors[] = "Wrong namespace in " . $file->getPathname() . ": $namespace";
                $namespaceTest = false;
            }
        }
    }
}

if ($namespaceTest) {
    echo "✓ PASSED\n";
    $passed++;
} else {
    echo "✗ FAILED\n";
    $failed++;
}

// Test 6: Check migration files
echo "Test 6: Migration Files... ";
$migrations = glob('src/Database/Migrations/*.php');
$migrationTest = count($migrations) === 4;

if (!$migrationTest) {
    $errors[] = "Expected 4 migration files, found " . count($migrations);
}

if ($migrationTest) {
    echo "✓ PASSED\n";
    $passed++;
} else {
    echo "✗ FAILED\n";
    $failed++;
}

// Test 7: Check blade templates
echo "Test 7: Blade Templates... ";
$bladeFiles = glob('resources/views/admin/*/*.blade.php');
$bladeTest = count($bladeFiles) === 4;

if (!$bladeTest) {
    $errors[] = "Expected 4 blade templates, found " . count($bladeFiles);
}

// Check for x-admin layout usage
foreach ($bladeFiles as $bladeFile) {
    $content = file_get_contents($bladeFile);
    if (strpos($content, '<x-admin::layouts>') === false) {
        $warnings[] = "Blade file doesn't use x-admin layout: $bladeFile";
    }
}

if ($bladeTest) {
    echo "✓ PASSED\n";
    $passed++;
} else {
    echo "✗ FAILED\n";
    $failed++;
}

// Test 8: Check configuration files
echo "Test 8: Configuration Files... ";
$configTest = true;

// Mock Laravel env() function for testing
if (!function_exists('env')) {
    function env($key, $default = null) {
        return $default;
    }
}

try {
    $ebayConfig = include 'publishable/config/ebayconnector.php';
    if (!is_array($ebayConfig)) {
        $errors[] = "ebayconnector.php doesn't return an array";
        $configTest = false;
    }

    $menuConfig = include 'publishable/config/menu.php';
    if (!is_array($menuConfig)) {
        $errors[] = "menu.php doesn't return an array";
        $configTest = false;
    }

    $aclConfig = include 'publishable/config/acl.php';
    if (!is_array($aclConfig)) {
        $errors[] = "acl.php doesn't return an array";
        $configTest = false;
    }
} catch (Exception $e) {
    $errors[] = "Error loading config files: " . $e->getMessage();
    $configTest = false;
}

if ($configTest) {
    echo "✓ PASSED\n";
    $passed++;
} else {
    echo "✗ FAILED\n";
    $failed++;
}

// Test 9: Check documentation
echo "Test 9: Documentation... ";
$docTest = true;
$docFiles = ['README.md', 'INSTALLATION.md', 'GITHUB_INSTALL.md', 'CHECKLIST.md', 'SUMMARY.md'];

foreach ($docFiles as $doc) {
    if (!file_exists($doc)) {
        $errors[] = "Missing documentation: $doc";
        $docTest = false;
    } else {
        $size = filesize($doc);
        if ($size < 100) {
            $warnings[] = "Documentation file seems too small: $doc ($size bytes)";
        }
    }
}

if ($docTest) {
    echo "✓ PASSED\n";
    $passed++;
} else {
    echo "✗ FAILED\n";
    $failed++;
}

// Test 10: Check license
echo "Test 10: License File... ";
$licenseContent = file_get_contents('LICENSE');
$licenseTest = stripos($licenseContent, 'MIT License') !== false &&
               stripos($licenseContent, 'Kevin B. Harris') !== false;

if (!$licenseTest) {
    $errors[] = "LICENSE file doesn't contain MIT License or author name";
}

if ($licenseTest) {
    echo "✓ PASSED\n";
    $passed++;
} else {
    echo "✗ FAILED\n";
    $failed++;
}

// Test 11: Check assets (icon font)
echo "Test 11: Assets (Icon Font)... ";
$assetsTest = true;

// Check for assets directory
if (!is_dir('publishable/assets')) {
    $errors[] = "Missing publishable/assets directory";
    $assetsTest = false;
}

// Check for font files
$requiredFontFiles = [
    'publishable/assets/fonts/ebay/ebay-icons.eot',
    'publishable/assets/fonts/ebay/ebay-icons.woff2',
    'publishable/assets/fonts/ebay/ebay-icons.woff',
    'publishable/assets/fonts/ebay/ebay-icons.ttf',
    'publishable/assets/fonts/ebay/ebay-icons.svg',
];

foreach ($requiredFontFiles as $fontFile) {
    if (!file_exists($fontFile)) {
        $errors[] = "Missing font file: $fontFile";
        $assetsTest = false;
    }
}

// Check for CSS file
if (!file_exists('publishable/assets/css/ebay-icons.css')) {
    $errors[] = "Missing CSS file: publishable/assets/css/ebay-icons.css";
    $assetsTest = false;
} else {
    // Validate CSS content
    $cssContent = file_get_contents('publishable/assets/css/ebay-icons.css');
    if (strpos($cssContent, '@font-face') === false) {
        $errors[] = "CSS file doesn't contain @font-face definition";
        $assetsTest = false;
    }
    if (strpos($cssContent, '.icon-ebay') === false) {
        $errors[] = "CSS file doesn't contain .icon-ebay class";
        $assetsTest = false;
    }
    if (strpos($cssContent, 'ebay-icons') === false) {
        $errors[] = "CSS file doesn't reference ebay-icons font family";
        $assetsTest = false;
    }
}

// Check that menu.php uses the icon-ebay class
$menuConfig = include 'publishable/config/menu.php';
if (is_array($menuConfig) && isset($menuConfig[0]['icon'])) {
    if ($menuConfig[0]['icon'] !== 'icon-ebay') {
        $errors[] = "Menu config doesn't use 'icon-ebay' class";
        $assetsTest = false;
    }
} else {
    $errors[] = "Menu config structure is invalid";
    $assetsTest = false;
}

if ($assetsTest) {
    echo "✓ PASSED\n";
    $passed++;
} else {
    echo "✗ FAILED\n";
    $failed++;
}


// Summary
echo "\n====================================\n";
echo "Test Results Summary\n";
echo "====================================\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";

if (count($warnings) > 0) {
    echo "\nWarnings (" . count($warnings) . "):\n";
    foreach ($warnings as $warning) {
        echo "  ⚠ $warning\n";
    }
}

if (count($errors) > 0) {
    echo "\nErrors (" . count($errors) . "):\n";
    foreach ($errors as $error) {
        echo "  ✗ $error\n";
    }
}

echo "\n";

if ($failed === 0) {
    echo "✓ ALL TESTS PASSED! Package is ready to use.\n";
    exit(0);
} else {
    echo "✗ SOME TESTS FAILED. Please review the errors above.\n";
    exit(1);
}
