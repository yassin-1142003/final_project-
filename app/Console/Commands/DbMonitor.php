<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DbMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor database connection and display status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking database connection...');
        
        try {
            $connection = DB::connection()->getName();
            $database = DB::connection()->getDatabaseName();
            $host = config('database.connections.' . $connection . '.host');
            $port = config('database.connections.' . $connection . '.port');
            $username = config('database.connections.' . $connection . '.username');
            
            $this->info('✓ Connected successfully!');
            $this->table(
                ['Connection', 'Host', 'Port', 'Database', 'Username'],
                [[$connection, $host, $port, $database, $username]]
            );
            
            // Test a simple query
            $tables = DB::select('SHOW TABLES');
            $this->info('✓ Query test successful!');
            $this->info('Found ' . count($tables) . ' tables in the database.');
            
            // Check if essential tables exist
            $usersTableExists = false;
            $rolesTableExists = false;
            
            foreach ($tables as $table) {
                $tableName = current((array)$table);
                if ($tableName == 'users') {
                    $usersTableExists = true;
                }
                if ($tableName == 'roles') {
                    $rolesTableExists = true;
                }
            }
            
            if ($usersTableExists) {
                $userCount = DB::table('users')->count();
                $this->info('✓ Users table exists with ' . $userCount . ' records.');
            } else {
                $this->warn('✗ Users table not found! You may need to run migrations.');
            }
            
            if ($rolesTableExists) {
                $roleCount = DB::table('roles')->count();
                $this->info('✓ Roles table exists with ' . $roleCount . ' records.');
            } else {
                $this->warn('✗ Roles table not found! You may need to run migrations.');
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('✗ Database connection failed!');
            $this->error('Error: ' . $e->getMessage());
            
            // Show current configuration for debugging
            $this->line('Current database configuration:');
            $config = config('database.connections.' . config('database.default'));
            $this->table(
                ['Setting', 'Value'],
                [
                    ['Connection', config('database.default')],
                    ['Host', $config['host']],
                    ['Port', $config['port']],
                    ['Database', $config['database']],
                    ['Username', $config['username']],
                ]
            );
            
            $this->info('Tip: Make sure your .env file has the correct database settings.');
            $this->info('Tip: Check that your database server is running and accessible.');
            
            return Command::FAILURE;
        }
    }
} 