<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class DatabaseMigrationController extends Controller
{
    /**
     * Run database migrations.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function runMigrations(Request $request)
    {
        // Only admins should be able to run migrations
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action. Admin privileges required.'
            ], 403);
        }

        $options = [];
        
        // Check for migration options
        if ($request->has('fresh') && $request->fresh) {
            $options['--fresh'] = true;
        }
        
        if ($request->has('seed') && $request->seed) {
            $options['--seed'] = true;
        }
        
        if ($request->has('force') && $request->force) {
            $options['--force'] = true;
        }
        
        if ($request->has('step')) {
            $options['--step'] = $request->step;
        }

        // Run the migration command
        try {
            Artisan::call('migrate', $options);
            $output = Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => 'Migrations completed successfully',
                'details' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Migration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rollback the last database migration.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rollbackMigration(Request $request)
    {
        // Only admins should be able to rollback migrations
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action. Admin privileges required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'step' => 'sometimes|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $options = [];
        
        // Check for rollback options
        if ($request->has('step')) {
            $options['--step'] = $request->step;
        }

        if ($request->has('force') && $request->force) {
            $options['--force'] = true;
        }

        try {
            Artisan::call('migrate:rollback', $options);
            $output = Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => 'Rollback completed successfully',
                'details' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rollback failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get migration status.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMigrationStatus()
    {
        // Only admins should be able to view migration status
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action. Admin privileges required.'
            ], 403);
        }

        try {
            Artisan::call('migrate:status');
            $output = Artisan::output();
            
            // Parse the output to create a more structured response
            $lines = explode("\n", $output);
            $migrations = [];
            
            foreach ($lines as $line) {
                if (preg_match('/\|\s+(\d{4}_\d{2}_\d{2}_\d{6}_\w+)\s+\|\s+(Yes|No)\s+\|/', $line, $matches)) {
                    $migrations[] = [
                        'migration' => $matches[1],
                        'ran' => $matches[2] === 'Yes'
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'migrations' => $migrations,
                'raw_output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve migration status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset all migrations.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetMigrations()
    {
        // Only admins should be able to reset migrations
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action. Admin privileges required.'
            ], 403);
        }

        try {
            Artisan::call('migrate:reset', ['--force' => true]);
            $output = Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => 'Migration reset completed successfully',
                'details' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Migration reset failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run database seeder.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function runSeeder(Request $request)
    {
        // Only admins should be able to seed the database
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action. Admin privileges required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'class' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $options = ['--force' => true];
        
        // Check for specific seeder class
        if ($request->has('class')) {
            $options['--class'] = $request->class;
        }

        try {
            Artisan::call('db:seed', $options);
            $output = Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => 'Database seeding completed successfully',
                'details' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database seeding failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 