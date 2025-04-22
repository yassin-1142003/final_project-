<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new database based on .env configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $databaseName = config('database.connections.mysql.database');
        
        try {
            $this->info("Creating database: {$databaseName}");
            
            // Connect to MySQL without specific database
            $pdo = DB::connection('mysql')->getPdo();
            
            // Create the database
            $pdo->exec(sprintf(
                'CREATE DATABASE IF NOT EXISTS %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;',
                $databaseName
            ));
            
            $this->info("Database {$databaseName} created successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Database creation failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 