<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Favorite;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of user's favorite apartments
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $favorites = Favorite::with(['apartment.images', 'apartment.user'])
                ->where('user_id', Auth::id())
                ->paginate(10);

            return $this->successResponse($favorites, 'Favorites retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve favorites', $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created favorite
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $apartment = Apartment::findOrFail($request->apartment_id);

            // Check if already favorited
            $existingFavorite = Favorite::where('user_id', Auth::id())
                ->where('apartment_id', $apartment->id)
                ->first();

            if ($existingFavorite) {
                return $this->errorResponse('Apartment already in favorites', null, 422);
            }

            DB::beginTransaction();

            $favorite = Favorite::create([
                'user_id' => Auth::id(),
                'apartment_id' => $apartment->id
            ]);

            DB::commit();

            return $this->successResponse(
                ['favorite' => $favorite->load('apartment')],
                'Apartment added to favorites successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to add to favorites', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified favorite
     *
     * @param Apartment $apartment
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Apartment $apartment)
    {
        try {
            $favorite = Favorite::where('user_id', Auth::id())
                ->where('apartment_id', $apartment->id)
                ->first();

            if (!$favorite) {
                return $this->notFoundResponse('Favorite not found');
            }

            DB::beginTransaction();

            $favorite->delete();

            DB::commit();

            return $this->successResponse(null, 'Apartment removed from favorites successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to remove from favorites', $e->getMessage(), 500);
        }
    }

    /**
     * Check if an apartment is favorited by the user
     *
     * @param Apartment $apartment
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(Apartment $apartment)
    {
        try {
            $isFavorited = Favorite::where('user_id', Auth::id())
                ->where('apartment_id', $apartment->id)
                ->exists();

            return $this->successResponse([
                'is_favorited' => $isFavorited
            ], 'Favorite status retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to check favorite status', $e->getMessage(), 500);
        }
    }

    /**
     * Toggle favorite status for an apartment
     *
     * @param Apartment $apartment
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggle(Apartment $apartment)
    {
        try {
            DB::beginTransaction();

            $favorite = Favorite::where('user_id', Auth::id())
                ->where('apartment_id', $apartment->id)
                ->first();

            if ($favorite) {
                $favorite->delete();
                $message = 'Apartment removed from favorites successfully';
            } else {
                Favorite::create([
                    'user_id' => Auth::id(),
                    'apartment_id' => $apartment->id
                ]);
                $message = 'Apartment added to favorites successfully';
            }

            DB::commit();

            return $this->successResponse([
                'is_favorited' => !$favorite
            ], $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to toggle favorite status', $e->getMessage(), 500);
        }
    }
} 