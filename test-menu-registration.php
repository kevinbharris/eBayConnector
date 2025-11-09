#!/usr/bin/env php
<?php
/**
 * eBay Connector Menu Registration Test
 * 
 * This script validates that the ServiceProvider properly handles
 * the case where Bagisto core binding is not present.
 */

echo "====================================\n";
echo "Menu Registration Test\n";
echo "====================================\n\n";

$errors = [];
$passed = 0;
$failed = 0;

// Test 1: Check ServiceProvider has Log import
echo "Test 1: ServiceProvider imports Log facade... ";
$serviceProviderContent = file_get_contents('src/Providers/EbayConnectorServiceProvider.php');

if (strpos($serviceProviderContent, 'use Illuminate\Support\Facades\Log;') !== false) {
    echo "✓ PASSED\n";
    $passed++;
} else {
    echo "✗ FAILED\n";
    $failed++;
    $errors[] = "ServiceProvider missing 'use Illuminate\Support\Facades\Log;' import";
}

// Test 2: Check registerMenuItems has bound check
echo "Test 2: registerMenuItems() checks core binding... ";
if (preg_match('/function\s+registerMenuItems.*?if\s*\(\s*\$this->app->bound\([\'"]core[\'"]\)\s*\)/s', $serviceProviderContent)) {
    echo "✓ PASSED\n";
    $passed++;
} else {
    echo "✗ FAILED\n";
    $failed++;
    $errors[] = "registerMenuItems() missing core binding check";
}

// Test 3: Check registerMenuItems has else clause with logging
echo "Test 3: registerMenuItems() logs when core binding missing... ";
if (preg_match('/function\s+registerMenuItems.*?}\s*else\s*{.*?Log::warning/s', $serviceProviderContent)) {
    echo "✓ PASSED\n";
    $passed++;
} else {
    echo "✗ FAILED\n";
    $failed++;
    $errors[] = "registerMenuItems() missing else clause with Log::warning";
}

// Test 4: Check registerACL has bound check
echo "Test 4: registerACL() checks core binding... ";
if (preg_match('/function\s+registerACL.*?if\s*\(\s*\$this->app->bound\([\'"]core[\'"]\)\s*\)/s', $serviceProviderContent)) {
    echo "✓ PASSED\n";
    $passed++;
} else {
    echo "✗ FAILED\n";
    $failed++;
    $errors[] = "registerACL() missing core binding check";
}

// Test 5: Check registerACL has else clause with logging
echo "Test 5: registerACL() logs when core binding missing... ";
if (preg_match('/function\s+registerACL.*?}\s*else\s*{.*?Log::warning/s', $serviceProviderContent)) {
    echo "✓ PASSED\n";
    $passed++;
} else {
    echo "✗ FAILED\n";
    $failed++;
    $errors[] = "registerACL() missing else clause with Log::warning";
}

// Test 6: Check warning message mentions Bagisto
echo "Test 6: Warning messages mention Bagisto... ";
$warningMatches = preg_match_all('/Log::warning\([^)]*Bagisto[^)]*\)/i', $serviceProviderContent);
if ($warningMatches >= 2) {
    echo "✓ PASSED\n";
    $passed++;
} else {
    echo "✗ FAILED\n";
    $failed++;
    $errors[] = "Warning messages should mention Bagisto";
}

// Test 7: Check INSTALLATION.md has menu troubleshooting section
echo "Test 7: INSTALLATION.md has menu troubleshooting... ";
$installationContent = file_get_contents('INSTALLATION.md');
if (strpos($installationContent, 'Menu Not Appearing') !== false && 
    strpos($installationContent, 'php artisan package:discover') !== false) {
    echo "✓ PASSED\n";
    $passed++;
} else {
    echo "✗ FAILED\n";
    $failed++;
    $errors[] = "INSTALLATION.md missing menu troubleshooting section";
}

// Test 8: Check INSTALLATION.md mentions cache clearing
echo "Test 8: INSTALLATION.md mentions cache clearing... ";
if (preg_match('/config:clear.*cache:clear.*view:clear/s', $installationContent)) {
    echo "✓ PASSED\n";
    $passed++;
} else {
    echo "✗ FAILED\n";
    $failed++;
    $errors[] = "INSTALLATION.md missing cache clearing commands";
}

// Test 9: Check GITHUB_INSTALL.md has enhanced troubleshooting
echo "Test 9: GITHUB_INSTALL.md has enhanced troubleshooting... ";
$githubInstallContent = file_get_contents('GITHUB_INSTALL.md');
if (strpos($githubInstallContent, 'eBay Connector Menu Not Appearing') !== false) {
    echo "✓ PASSED\n";
    $passed++;
} else {
    echo "✗ FAILED\n";
    $failed++;
    $errors[] = "GITHUB_INSTALL.md missing enhanced troubleshooting";
}

// Test 10: Check GITHUB_INSTALL.md mentions core binding check
echo "Test 10: GITHUB_INSTALL.md mentions core binding verification... ";
if (strpos($githubInstallContent, "app()->bound('core')") !== false) {
    echo "✓ PASSED\n";
    $passed++;
} else {
    echo "✗ FAILED\n";
    $failed++;
    $errors[] = "GITHUB_INSTALL.md missing core binding verification step";
}

// Summary
echo "\n====================================\n";
echo "Test Results Summary\n";
echo "====================================\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";

if (count($errors) > 0) {
    echo "\nErrors (" . count($errors) . "):\n";
    foreach ($errors as $error) {
        echo "  ✗ $error\n";
    }
}

echo "\n";

if ($failed === 0) {
    echo "✓ ALL TESTS PASSED! Menu registration improvements are properly implemented.\n";
    exit(0);
} else {
    echo "✗ SOME TESTS FAILED. Please review the errors above.\n";
    exit(1);
}
