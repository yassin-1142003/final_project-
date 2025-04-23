<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use ApiResponses;

    /**
     * Get all users
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $users = User::where('id', '!=', Auth::id())->paginate(20);
            
            return $this->successResponse([
                'users' => $users->items(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
                'per_page' => $users->perPage()
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve users', $e->getMessage(), 500);
        }
    }

    /**
     * Get a specific user
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Don't show sensitive information
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_image' => $user->profile_image,
                'created_at' => $user->created_at->toDateTimeString(),
            ];
            
            return $this->successResponse($userData);
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return $this->notFoundResponse('User not found');
            }
            return $this->errorResponse('Failed to retrieve user', $e->getMessage(), 500);
        }
    }

    /**
     * Search for users
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        try {
            $query = $request->get('query', '');
            
            if (empty($query)) {
                return $this->errorResponse('Search query is required', null, 400);
            }
            
            $users = User::where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->where('id', '!=', Auth::id())
                ->limit(10)
                ->get(['id', 'name', 'email', 'profile_image']);
            
            return $this->successResponse(['users' => $users]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to search users', $e->getMessage(), 500);
        }
    }

    /**
     * Get current user's profile
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile()
    {
        try {
            $user = Auth::user();
            
            return $this->successResponse([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_image' => $user->profile_image,
                'created_at' => $user->created_at->toDateTimeString(),
                'email_verified_at' => $user->email_verified_at ? $user->email_verified_at->toDateTimeString() : null,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve profile', $e->getMessage(), 500);
        }
    }

    /**
     * Update user profile
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        try {
        $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . Auth::id(),
        ]);

        if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $user = Auth::user();
            
            if ($request->has('name')) {
                $user->name = $request->name;
            }
            
            if ($request->has('email') && $request->email !== $user->email) {
                $user->email = $request->email;
                $user->email_verified_at = null; // Require re-verification
            }
            
            $user->save();
            
            return $this->successResponse([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_image' => $user->profile_image,
                'email_verified_at' => $user->email_verified_at ? $user->email_verified_at->toDateTimeString() : null,
            ], 'Profile updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update profile', $e->getMessage(), 500);
        }
    }

    /**
     * Update user avatar/profile image
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAvatar(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'avatar' => 'required|image|max:2048', // 2MB max
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $user = Auth::user();
            
            // Delete old avatar if exists
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }
            
            // Store new avatar
            $path = $request->file('avatar')->store('avatars', 'public');
            
            $user->profile_image = $path;
            $user->save();
            
            return $this->successResponse([
                'profile_image' => $user->profile_image,
            ], 'Avatar updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update avatar', $e->getMessage(), 500);
        }
    }

    /**
     * Update user password
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $user = Auth::user();
            
            // Check current password
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->errorResponse('Current password is incorrect', null, 422);
            }
            
            $user->password = Hash::make($request->password);
            $user->save();
            
            return $this->successResponse(null, 'Password updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update password', $e->getMessage(), 500);
        }
    }
} 