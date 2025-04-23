<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Listing;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use App\Traits\ApiResponses;
use Illuminate\Auth\Events\PasswordReset;

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
        $this->middleware('auth:sanctum', ['except' => [
            'login', 
            'register', 
            'forgotPassword', 
            'resetPassword', 
            'verifyEmail', 
            'resendVerificationEmail',
            'redirectToGoogle',
            'handleGoogleCallback',
            'redirectToFacebook',
            'handleFacebookCallback'
        ]]);
        $this->middleware('throttle:10,1')->only(['login', 'register']);
    }

    /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'phone' => 'required|string|max:20',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'id_card_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            // Get default user role (assuming 'user' is the default role)
            $defaultRole = Role::where('name', 'user')->first();
            if (!$defaultRole) {
                // Create the role if it doesn't exist
                $defaultRole = Role::create([
                    'name' => 'user',
                    'description' => 'Regular user role'
                ]);
                
                if (!$defaultRole) {
                    Log::error('Failed to create default user role');
                    return $this->errorResponse('Registration failed', 'Could not create default role', 500);
                }
            }

            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role_id' => $defaultRole->id,
                'is_active' => true
            ];

            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('profiles', 'public');
                $userData['profile_image'] = $path;
            }

            if ($request->hasFile('id_card_image')) {
                $path = $request->file('id_card_image')->store('id_cards', 'public');
                $userData['id_card_image'] = $path;
            }

            $user = User::create($userData);
            event(new Registered($user));

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'access_token' => $token,
                'token_type' => 'bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
            ], 'User registered successfully', 201);

        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return $this->errorResponse('Registration failed', $e->getMessage(), 500);
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
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            if (!Auth::attempt($request->only('email', 'password'))) {
                return $this->unauthorizedResponse('Invalid login credentials');
            }

            $user = User::where('email', $request->email)->firstOrFail();
            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_image' => $user->profile_image,
                ]
            ], 'Login successful');
        } catch (\Exception $e) {
            return $this->errorResponse('Login failed', $e->getMessage(), 500);
        }
    }

    /**
     * Get the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        return $this->successResponse($request->user());
    }

    /**
     * Logout user (Revoke the token)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return $this->successResponse(null, 'Successfully logged out');
        } catch (\Exception $e) {
            return $this->errorResponse('Logout failed', $e->getMessage(), 500);
        }
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        try {
            $user = $request->user();
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Token refreshed successfully',
                'access_token' => $token,
                'token_type' => 'bearer',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Token refresh error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Token refresh failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send password reset link
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $status = Password::sendResetLink($request->only('email'));

            if ($status === Password::RESET_LINK_SENT) {
                return $this->successResponse(null, __($status));
            }

            return $this->errorResponse(__($status), null, 400);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to send reset link', $e->getMessage(), 500);
        }
    }

    /**
     * Reset password
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password)
                    ])->setRememberToken(Str::random(60));

                    $user->save();

                    event(new PasswordReset($user));
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return $this->successResponse(null, __($status));
            }

            return $this->errorResponse(__($status), null, 400);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reset password', $e->getMessage(), 500);
        }
    }

    /**
     * Verify email address.
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::find($id);

        if (!$user || !hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification link'], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified']);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json(['message' => 'Email has been verified']);
    }

    /**
     * Resend verification email.
     */
    public function resendVerificationEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent']);
    }

    /**
     * Redirect to Google for authentication.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google callback.
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            $user = User::where('email', $googleUser->email)->first();
            
            // Get default user role
            $defaultRole = Role::where('name', 'user')->first();
            if (!$defaultRole) {
                $defaultRole = Role::create([
                    'name' => 'user',
                    'description' => 'Regular user role'
                ]);
            }
            
            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'password' => Hash::make(Str::random(16)),
                    'email_verified_at' => now(),
                    'role_id' => $defaultRole->id,
                    'is_active' => true
                ]);
            } else {
                $user->update([
                    'google_id' => $googleUser->id,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            }
            
            $token = $user->createToken('google-auth')->plainTextToken;
            
            return response()->json([
                'message' => 'Google login successful',
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'bearer'
            ]);
        } catch (\Exception $e) {
            Log::error('Social login failure: ' . $e->getMessage());
            return response()->json(['message' => 'Authentication failed', 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * Redirect to Facebook for authentication.
     */
    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->redirect();
    }

    /**
     * Handle Facebook callback.
     */
    public function handleFacebookCallback()
    {
        try {
            $facebookUser = Socialite::driver('facebook')->user();
            
            $user = User::where('email', $facebookUser->email)->first();
            
            // Get default user role
            $defaultRole = Role::where('name', 'user')->first();
            if (!$defaultRole) {
                $defaultRole = Role::create([
                    'name' => 'user',
                    'description' => 'Regular user role'
                ]);
            }
            
            if (!$user) {
                $user = User::create([
                    'name' => $facebookUser->name,
                    'email' => $facebookUser->email,
                    'facebook_id' => $facebookUser->id,
                    'password' => Hash::make(Str::random(16)),
                    'email_verified_at' => now(),
                    'role_id' => $defaultRole->id,
                    'is_active' => true
                ]);
            } else {
                $user->update([
                    'facebook_id' => $facebookUser->id,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            }
            
            $token = $user->createToken('facebook-auth')->plainTextToken;
            
            return response()->json([
                'message' => 'Facebook login successful',
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'bearer'
            ]);
        } catch (\Exception $e) {
            Log::error('Social login failure: ' . $e->getMessage());
            return response()->json(['message' => 'Authentication failed', 'error' => $e->getMessage()], 400);
        }
    }

    // Use more efficient queries with select()
    public function index()
    {
        $query = Listing::with(['user:id,name', 'adType:id,name'])->select(['id', 'title', 'price', 'user_id', 'ad_type_id']);
        // Rest of the method code...
    }
}

// Create ApiResponse helper
class ApiResponse {
    public static function success($data, $message = '', $code = 200) {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public static function error($message, $errors = [], $code = 422) {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $code);
    }
}