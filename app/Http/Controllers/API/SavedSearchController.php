<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SavedSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponses;

class SavedSearchController extends Controller
{
    use ApiResponses;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the user's saved searches
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $savedSearches = SavedSearch::where('user_id', Auth::id())->get();
            return $this->successResponse($savedSearches, 'Saved searches retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve saved searches', $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created saved search
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'filters' => 'required|json',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $savedSearch = SavedSearch::create([
                'name' => $request->name,
                'filters' => $request->filters,
                'user_id' => Auth::id(),
            ]);

            return $this->successResponse($savedSearch, 'Search saved successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to save search', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified saved search
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $savedSearch = SavedSearch::where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$savedSearch) {
                return $this->errorResponse('Saved search not found', null, 404);
            }

            return $this->successResponse($savedSearch, 'Saved search retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve saved search', $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified saved search
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $savedSearch = SavedSearch::where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$savedSearch) {
                return $this->errorResponse('Saved search not found', null, 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'filters' => 'sometimes|json',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            if ($request->has('name')) {
                $savedSearch->name = $request->name;
            }

            if ($request->has('filters')) {
                $savedSearch->filters = $request->filters;
            }

            $savedSearch->save();

            return $this->successResponse($savedSearch, 'Saved search updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update saved search', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified saved search
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $savedSearch = SavedSearch::where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$savedSearch) {
                return $this->errorResponse('Saved search not found', null, 404);
            }

            $savedSearch->delete();

            return $this->successResponse(null, 'Saved search deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete saved search', $e->getMessage(), 500);
        }
    }
}
