<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    use ApiResponses;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except([
            'login',
            'register',
            'forgotPassword',
            'resetPassword'
        ]);
    }

    /**
     * Register a new user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            // First, log the incoming request for debugging
            Log::info('Registration request received', [
                'request_data' => $request->except(['password', 'password_confirmation']),
                'headers' => $request->header()
            ]);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'role_id' => 'nullable|exists:roles,id',
                'phone' => 'nullable|string|max:20',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'id_card_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                Log::warning('Registration validation failed', ['errors' => $validator->errors()]);
                return $this->validationError($validator->errors());
            }

            // Check if roles table exists and has data
            try {
                $roleCount = DB::table('roles')->count();
                Log::info("Number of roles in database: $roleCount");
                
                // Use default role if not provided or doesn't exist
                $roleId = $request->has('role_id') ? $request->role_id : 2; // Assuming 2 is user role
            } catch (\Exception $e) {
                Log::error('Error accessing roles table: ' . $e->getMessage());
                // If we can't access roles table, use a safe default
                $roleId = 2; // Default to regular user role
            }

            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $roleId,
                'phone' => $request->phone ?? null,
                'is_active' => true
            ];

            // Handle profile image upload if provided
            if ($request->hasFile('profile_image')) {
                try {
                    $profileImage = $request->file('profile_image');
                    $profileImageName = time() . '_' . uniqid() . '_profile.' . $profileImage->getClientOriginalExtension();
                    
                    // Ensure the storage directory exists
                    Storage::disk('public')->makeDirectory('profiles');
                    
                    // Store the file
                    $path = $profileImage->storeAs('profiles', $profileImageName, 'public');
                    if (!$path) {
                        throw new \Exception('Failed to store profile image');
                    }
                    $userData['profile_image'] = $path;
                    Log::info('Profile image stored at: ' . $path);
                } catch (\Exception $e) {
                    Log::error('Profile image upload failed: ' . $e->getMessage());
                    // Continue registration without the image
                }
            }

            // Handle ID card image upload if provided
            if ($request->hasFile('id_card_image')) {
                try {
                    $idCardImage = $request->file('id_card_image');
                    $idCardImageName = time() . '_' . uniqid() . '_id_card.' . $idCardImage->getClientOriginalExtension();
                    
                    // Ensure the storage directory exists
                    Storage::disk('public')->makeDirectory('id_cards');
                    
                    // Store the file
                    $path = $idCardImage->storeAs('id_cards', $idCardImageName, 'public');
                    if (!$path) {
                        throw new \Exception('Failed to store ID card image');
                    }
                    $userData['id_card_image'] = $path;
                    Log::info('ID card image stored at: ' . $path);
                } catch (\Exception $e) {
                    Log::error('ID card image upload failed: ' . $e->getMessage());
                    // Continue registration without the image
                }
            }

            // Log what we're about to insert into the database
            Log::info('Creating new user', ['userData' => array_diff_key($userData, ['password' => ''])]);
            
            // Create the user with robust error handling
            try {
                $user = User::create($userData);
                
                // Verify the user was created
                if (!$user || !$user->id) {
                    throw new \Exception('User creation failed without throwing an exception');
                }
                
                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'message' => 'User registered successfully',
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer'
                ], 201);
            } catch (\Exception $e) {
                Log::error('User creation failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Registration error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
                'details' => app()->environment('production') ? null : [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            ], 500);
        }
    }

    /**
     * Login user and create token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            if (!Auth::attempt($request->only('email', 'password'))) {
                return $this->unauthorizedResponse('Invalid credentials');
            }

            $user = User::where('email', $request->email)->firstOrFail();
            
            // Revoke all existing tokens
            $user->tokens()->delete();
            
            // Create new token
            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ], 'User logged in successfully');

        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Login failed', $e->getMessage(), 500);
        }
    }

    /**
     * Get authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        try {
            return $this->successResponse([
                'user' => $request->user()
            ], 'User profile retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get user information', $e->getMessage(), 500);
        }
    }

    /**
     * Logout user (Revoke the token)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        PersonalAccessToken::findToken($request->bearerToken())->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        try {
            $user = $request->user();
            
            // Delete all existing tokens
            $user->tokens()->delete();
            
            // Create new token
            $newToken = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Token refreshed successfully',
                'access_token' => $newToken,
                'token_type' => 'Bearer',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token refresh failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all available roles
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRoles()
    {
        $roles = \App\Models\Role::select('id', 'name', 'display_name', 'description')->get();
        
        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    /**
     * Send a password reset link to the user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $status = \Illuminate\Support\Facades\Password::sendResetLink($request->only('email'));

        return $status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Reset link sent to your email'])
            : response()->json(['message' => 'Unable to send reset link'], 400);
    }

    /**
     * Reset user's password.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $status = \Illuminate\Support\Facades\Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        return $status === \Illuminate\Support\Facades\Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password has been reset successfully'])
            : response()->json(['message' => 'Unable to reset password'], 400);
    }
} 