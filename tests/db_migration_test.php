<?php
/**
 * Simple test script to verify the database connection and schema
 * that should be run after applying all migrations
 * for the new server at http://localhost:8000
 */

echo "Testing database connection and schema...\n";
echo "======================================\n\n";

// Required tables after migration
$requiredTables = [
    'users',
    'roles',
    'apartments',
    'comments',
    'favorites',
    'bookings',
    'payment_methods',
    'transactions',
    'saved_searches',
    'notifications'
];

// Connect to database
try {
    // Get database credentials from environment or use defaults
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '3306';
    $database = getenv('DB_DATABASE') ?: 'realestate';
    $username = getenv('DB_USERNAME') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';
    
    $dsn = "mysql:host=$host;port=$port;dbname=$database";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "✓ Connected to database '$database' on '$host'\n\n";
    
    // Check that all required tables exist
    $query = "SHOW TABLES";
    $stmt = $pdo->query($query);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Checking for required tables...\n";
    
    $missingTables = [];
    foreach ($requiredTables as $table) {
        if (in_array($table, $tables)) {
            echo "✓ Table '$table' exists\n";
        } else {
            echo "✗ Table '$table' is missing\n";
            $missingTables[] = $table;
        }
    }
    
    echo "\n";
    
    if (empty($missingTables)) {
        echo "All required tables are present\n";
    } else {
        echo "Missing tables: " . implode(', ', $missingTables) . "\n";
        echo "Please run 'php artisan migrate' to create these tables\n";
        exit(1);
    }
    
    echo "\nSchema verification successful!\n";
    exit(0);
    
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "Please check your database configuration in .env file\n";
    exit(1);
} 