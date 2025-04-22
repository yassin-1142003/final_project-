<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\SavedSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SavedSearchController extends Controller
{
    /**
     * Display a listing of the saved searches.
     */
    public function index(Request $request)
    {
        $savedSearches = SavedSearch::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $savedSearches,
            'meta' => [
                'total' => $savedSearches->total(),
                'per_page' => $savedSearches->perPage(),
                'current_page' => $savedSearches->currentPage(),
                'last_page' => $savedSearches->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created saved search.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'search_params' => 'required|array',
            'is_notifiable' => 'boolean',
            'notification_frequency' => 'nullable|in:daily,weekly,monthly',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $savedSearch = SavedSearch::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'search_params' => $request->search_params,
            'is_notifiable' => $request->is_notifiable ?? false,
            'notification_frequency' => $request->notification_frequency,
        ]);

        return response()->json([
            'message' => 'Search saved successfully',
            'data' => $savedSearch,
        ], 201);
    }

    /**
     * Display the specified saved search.
     */
    public function show(Request $request, SavedSearch $savedSearch)
    {
        // Ensure the user owns this saved search
        if ($savedSearch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => $savedSearch,
        ]);
    }

    /**
     * Update the specified saved search.
     */
    public function update(Request $request, SavedSearch $savedSearch)
    {
        // Ensure the user owns this saved search
        if ($savedSearch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'search_params' => 'sometimes|required|array',
            'is_notifiable' => 'sometimes|boolean',
            'notification_frequency' => 'nullable|in:daily,weekly,monthly',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $savedSearch->update($request->only([
            'name', 'search_params', 'is_notifiable', 'notification_frequency'
        ]));

        return response()->json([
            'message' => 'Saved search updated successfully',
            'data' => $savedSearch->fresh(),
        ]);
    }

    /**
     * Remove the specified saved search.
     */
    public function destroy(Request $request, SavedSearch $savedSearch)
    {
        // Ensure the user owns this saved search
        if ($savedSearch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $savedSearch->delete();

        return response()->json(['message' => 'Saved search deleted successfully']);
    }

    /**
     * Execute a saved search and return matching listings.
     */
    public function execute(Request $request, SavedSearch $savedSearch)
    {
        // Ensure the user owns this saved search
        if ($savedSearch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $params = $savedSearch->search_params;
        $query = Listing::query()->with(['user', 'adType', 'propertyImages'])
            ->where('status', 'approved');

        // Apply filters from the saved search params
        if (!empty($params['city'])) {
            $query->where('city', $params['city']);
        }

        if (!empty($params['state'])) {
            $query->where('state', $params['state']);
        }

        if (!empty($params['property_type'])) {
            $query->where('property_type', $params['property_type']);
        }

        if (!empty($params['listing_type'])) {
            $query->where('listing_type', $params['listing_type']);
        }

        if (!empty($params['min_price'])) {
            $query->where('price', '>=', $params['min_price']);
        }
        
        if (!empty($params['max_price'])) {
            $query->where('price', '<=', $params['max_price']);
        }

        if (!empty($params['bedrooms'])) {
            $query->where('bedrooms', $params['bedrooms']);
        }

        if (!empty($params['bathrooms'])) {
            $query->where('bathrooms', $params['bathrooms']);
        }

        if (!empty($params['min_area'])) {
            $query->where('area', '>=', $params['min_area']);
        }
        
        if (!empty($params['max_area'])) {
            $query->where('area', '<=', $params['max_area']);
        }

        if (isset($params['is_furnished'])) {
            $query->where('is_furnished', $params['is_furnished']);
        }

        // Apply sorting
        $sortBy = $params['sort_by'] ?? 'created_at';
        $sortOrder = $params['sort_order'] ?? 'desc';
        $allowedSortFields = ['price', 'created_at', 'area', 'bedrooms'];
        
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        $listings = $query->paginate(15);

        return response()->json([
            'data' => $listings->items(),
            'meta' => [
                'total' => $listings->total(),
                'per_page' => $listings->perPage(),
                'current_page' => $listings->currentPage(),
                'last_page' => $listings->lastPage(),
            ],
            'search_name' => $savedSearch->name,
        ]);
    }
} 