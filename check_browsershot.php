<?php

/**
 * Browsershot Diagnostic Script
 * Run this on your server: php check_browsershot.php
 */

echo "--------------------------------------------------\n";
echo "Browsershot Diagnostic Tool for Ofuq Platform\n";
echo "--------------------------------------------------\n";

// 1. Check OS
$os = PHP_OS;
echo "[INFO] Operating System: " . $os . "\n";
$isWindows = strtoupper(substr($os, 0, 3)) === 'WIN';
echo "[INFO] Detected Platform: " . ($isWindows ? "Windows" : "Linux/Unix") . "\n";

// 2. Check PHP Functions
$requiredFunctions = ['exec', 'shell_exec', 'file_exists', 'is_readable'];
foreach ($requiredFunctions as $func) {
    if (!function_exists($func)) {
        echo "[ERROR] PHP function '$func' is disabled or missing. Browsershot requires it.\n";
    } else {
        echo "[PASS] PHP function '$func' is available.\n";
    }
}

// 3. Load .env (Simple parser to avoid dependencies if vendor not loaded)
$envPath = __DIR__ . '/.env';
$env = [];
if (file_exists($envPath)) {
    echo "[PASS] .env file found.\n";
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $env[trim($parts[0])] = trim($parts[1]);
        }
    }
} else {
    echo "[WARNING] .env file NOT found in " . __DIR__ . "\n";
}

// 4. Check Environment Variables
$keysToCheck = [
    'BROWSERSHOT_NODE_PATH',
    'BROWSERSHOT_CHROME_PATH',
    'BROWSERSHOT_NODE_PATH_LINUX',
    'BROWSERSHOT_CHROME_PATH_LINUX',
    'BROWSERSHOT_NO_SANDBOX'
];

echo "\n--- Environment Variables Check ---\n";
foreach ($keysToCheck as $key) {
    $val = $env[$key] ?? getenv($key);
    if ($val) {
        echo "[INFO] $key = $val\n";
        // Check if path exists
        if (strpos($key, 'PATH') !== false) {
            if (file_exists($val)) {
                echo "       -> [PASS] Path exists.\n";
            } else {
                echo "       -> [FAIL] Path does NOT exist on this server.\n";
            }
        }
    } else {
        echo "[INFO] $key is NOT set.\n";
    }
}

// 5. Check Node and NPM
echo "\n--- Node & NPM Check ---\n";
$nodeBinary = $env['BROWSERSHOT_NODE_PATH_LINUX'] ?? $env['BROWSERSHOT_NODE_PATH'] ?? ($isWindows ? 'node' : '/usr/bin/node');

// Try to find node if not set
if ($nodeBinary === 'node' || !file_exists($nodeBinary)) {
    $foundNode = exec($isWindows ? "where node" : "which node");
    if ($foundNode) {
        echo "[INFO] Found Node automatically at: $foundNode\n";
        $nodeBinary = $foundNode;
    } else {
        echo "[ERROR] Node binary not found in PATH. Please install Node.js.\n";
    }
}

if ($nodeBinary && file_exists($nodeBinary)) {
    $nodeVersion = exec("$nodeBinary -v");
    echo "[PASS] Node Version: $nodeVersion\n";
} else {
    echo "[FAIL] Node binary is not executable or not found.\n";
}

// 6. Check Chrome/Chromium
echo "\n--- Chrome/Chromium Check ---\n";
$chromeBinary = $env['BROWSERSHOT_CHROME_PATH_LINUX'] ?? $env['BROWSERSHOT_CHROME_PATH'] ?? null;

if (!$chromeBinary) {
    echo "[INFO] No custom Chrome path set. Browsershot will try to find it via Puppeteer.\n";
    // Try to find it manually for info
    $commonLinuxPaths = [
        '/usr/bin/google-chrome',
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser',
        '/snap/bin/chromium'
    ];
    foreach ($commonLinuxPaths as $path) {
        if (file_exists($path)) {
            echo "[INFO] Found potential Chrome binary at: $path\n";
            break;
        }
    }
} else {
    if (file_exists($chromeBinary)) {
        echo "[PASS] Configured Chrome path exists: $chromeBinary\n";
    } else {
        echo "[FAIL] Configured Chrome path does NOT exist: $chromeBinary\n";
    }
}

// 7. Puppeteer Check
echo "\n--- Puppeteer Check ---\n";
if (file_exists(__DIR__ . '/node_modules/puppeteer')) {
    echo "[PASS] 'puppeteer' found in node_modules.\n";
} else {
    echo "[WARNING] 'puppeteer' NOT found in node_modules. Browsershot might install it automatically or fail.\n";
}

echo "\n--------------------------------------------------\n";
echo "Diagnostic Complete.\n";
echo "If you see [FAIL] or [ERROR], please fix those issues.\n";
echo "Recommended .env settings for Linux:\n";
echo "BROWSERSHOT_NODE_PATH_LINUX=/usr/bin/node (or output of 'which node')\n";
echo "BROWSERSHOT_CHROME_PATH_LINUX=/usr/bin/google-chrome (or output of 'which google-chrome')\n";
echo "BROWSERSHOT_NO_SANDBOX=true\n";
echo "--------------------------------------------------\n";
