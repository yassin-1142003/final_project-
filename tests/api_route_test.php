<?php
/**
 * API Route Testing Script
 * 
 * This script tests the connectivity and accessibility of the main API routes
 * on the new server at http://localhost:8000/api
 */

$baseUrl = 'http://localhost:8000/api';

// Define routes to test
$routesToTest = [
    // Public routes
    '/test' => 'GET',
    '/health' => 'GET',
    '/apartments' => 'GET',
    '/featured-apartments' => 'GET',
    
    // These routes require authentication, will return 401 if no token
    '/user' => 'GET', 
    '/favorites' => 'GET',
    '/saved-searches' => 'GET'
];

echo "Testing API Routes on $baseUrl\n";
echo "======================================\n\n";

foreach ($routesToTest as $route => $method) {
    $url = $baseUrl . $route;
    
    echo "Testing $method $route...\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    if ($error) {
        echo "Error: $error\n";
    } else {
        echo "Status: $httpCode\n";
        
        // For successful responses, show a preview
        if ($httpCode >= 200 && $httpCode < 300) {
            $responseData = json_decode($response, true);
            echo "Response: " . json_encode(array_slice($responseData, 0, 3, true)) . "...\n";
        }
    }
    
    curl_close($ch);
    echo "--------------------------------------\n";
}

echo "\nTest completed.\n";
echo "For authenticated routes, a 401 status is expected without a valid token.\n"; 