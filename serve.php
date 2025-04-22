<?php
/**
 * Real Estate API Server Script
 * 
 * This script provides a way to run the application in development with production-like settings
 */

$host = '0.0.0.0';
$port = 8000;

echo "Starting Real Estate API server...\n";
echo "====================================\n\n";
echo "API will be accessible at: http://$host:$port\n";
echo "To stop the server, press Ctrl+C\n\n";

// Check if PHP's built-in server is available
if (PHP_SAPI !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

// Set environment to production
putenv('APP_ENV=production');
putenv('APP_DEBUG=false');

// Command to start the server with appropriate settings
$command = sprintf(
    'php -d display_errors=0 -d variables_order=EGPCS -S %s:%d -t public',
    $host,
    $port
);

echo "Running command: $command\n\n";
passthru($command); 