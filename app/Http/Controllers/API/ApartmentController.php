<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApartmentController extends Controller
{
    use ApiResponses;

    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'search', 'featured']);
    }

    /**
     * Display a listing of apartments
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Apartment::with(['images', 'user']);

            // Apply filters
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }
            if ($request->has('location')) {
                $query->where('location', 'like', '%' . $request->location . '%');
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $apartments = $query->paginate(10);

            return $this->successResponse($apartments, 'Apartments retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve apartments', $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created apartment
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            Log::info('Starting apartment creation with files:', ['has_files' => $request->hasFile('images')]);
            
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
                'price' => 'required|numeric',
                'location' => 'required|string',
                'bedrooms' => 'required|integer',
                'bathrooms' => 'required|integer',
                'area' => 'required|numeric',
                'type' => 'nullable|string|in:sale,rent',
                'status' => 'nullable|string|in:available,rented,sold',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

            DB::beginTransaction();

            $apartment = new Apartment();
            $apartment->title = $request->title;
            $apartment->description = $request->description;
            $apartment->price = $request->price;
            $apartment->location = $request->location;
            $apartment->bedrooms = $request->bedrooms;
            $apartment->bathrooms = $request->bathrooms;
            $apartment->area = $request->area;
            $apartment->type = $request->type ?? 'sale';
            $apartment->status = $request->status ?? 'available';
            $apartment->user_id = Auth::id();
            $apartment->save();

            // Handle image uploads
        if ($request->hasFile('images')) {
                Log::info('Processing images for apartment', ['apartment_id' => $apartment->id]);
                
                // Ensure storage directory exists
                $storageDir = storage_path('app/public/apartments');
                if (!file_exists($storageDir)) {
                    mkdir($storageDir, 0755, true);
                    Log::info('Created apartments directory', ['path' => $storageDir]);
                }

            foreach ($request->file('images') as $index => $image) {
                    try {
                        Log::info('Processing image', [
                            'index' => $index,
                            'original_name' => $image->getClientOriginalName(),
                            'mime_type' => $image->getMimeType(),
                            'size' => $image->getSize()
                        ]);

                        // Generate unique filename
                        $imageName = 'apartment_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                        $fullPath = 'apartments/' . $imageName;
                        
                        // Store the file
                        $path = Storage::disk('public')->putFileAs('apartments', $image, $imageName);
                        
                        if (!$path) {
                            throw new \Exception('Failed to store image');
                        }

                        Log::info('Image stored successfully', [
                            'path' => $path,
                            'full_path' => Storage::disk('public')->path($path),
                            'exists' => Storage::disk('public')->exists($path)
                        ]);

                        // Create image record
                        $imageRecord = $apartment->images()->create([
                            'path' => $path
                        ]);

                        Log::info('Image record created', ['image_id' => $imageRecord->id]);

                    } catch (\Exception $e) {
                        Log::error('Failed to process image', [
                            'index' => $index,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        DB::rollBack();
                        return response()->json([
                            'message' => 'Failed to upload apartment image',
                            'error' => $e->getMessage()
                        ], 500);
                    }
                }
            }

            DB::commit();

            // Load the apartment with images and return full URLs
            $apartment->load('images');
            $apartment->images->transform(function ($image) {
                $image->full_url = asset('storage/' . $image->path);
                return $image;
            });

            return response()->json([
                'message' => 'Apartment created successfully',
                'data' => $apartment,
                'storage_info' => [
                    'storage_path' => storage_path('app/public/apartments'),
                    'public_url' => asset('storage/apartments'),
                    'exists' => file_exists(storage_path('app/public/apartments')),
                    'is_writable' => is_writable(storage_path('app/public/apartments'))
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating apartment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error creating apartment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified apartment
     *
     * @param Apartment $apartment
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Apartment $apartment)
    {
        try {
            $apartment->load(['images', 'user']);
            return $this->successResponse($apartment, 'Apartment retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve apartment', $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified apartment
     *
     * @param Request $request
     * @param Apartment $apartment
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Apartment $apartment)
    {
        try {
        if ($apartment->user_id !== Auth::id()) {
                return $this->forbiddenResponse('You do not have permission to update this apartment');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
                'location' => 'sometimes|string|max:255',
            'bedrooms' => 'sometimes|integer|min:0',
            'bathrooms' => 'sometimes|integer|min:0',
                'size' => 'sometimes|numeric|min:0',
                'images.*' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            DB::beginTransaction();

            $apartment->update($request->only([
                'title', 'description', 'price', 'location',
                'bedrooms', 'bathrooms', 'size', 'is_featured'
            ]));

            // Handle image uploads
            if ($request->hasFile('images')) {
                // Delete old images
                foreach ($apartment->images as $image) {
                    Storage::disk('public')->delete($image->image_path);
                }
                $apartment->images()->delete();

                // Upload new images
            foreach ($request->file('images') as $image) {
                    $path = $image->store('apartments', 'public');
                $apartment->images()->create([
                        'image_path' => $path
                ]);
            }
        }

            DB::commit();

            return $this->successResponse(
                ['apartment' => $apartment->load('images')],
                'Apartment updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update apartment', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified apartment
     *
     * @param Apartment $apartment
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Apartment $apartment)
    {
        try {
        if ($apartment->user_id !== Auth::id()) {
                return $this->forbiddenResponse('You do not have permission to delete this apartment');
        }

            DB::beginTransaction();

            // Delete images from storage
        foreach ($apartment->images as $image) {
                Storage::disk('public')->delete($image->image_path);
        }

        $apartment->delete();

            DB::commit();

            return $this->successResponse(null, 'Apartment deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to delete apartment', $e->getMessage(), 500);
        }
    }

    /**
     * Search apartments
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'query' => 'required|string|min:2',
                'min_price' => 'sometimes|numeric|min:0',
                'max_price' => 'sometimes|numeric|min:0',
                'bedrooms' => 'sometimes|integer|min:0',
                'bathrooms' => 'sometimes|integer|min:0'
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $query = Apartment::with(['images', 'user'])
                ->where(function($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->query . '%')
                      ->orWhere('description', 'like', '%' . $request->query . '%')
                      ->orWhere('location', 'like', '%' . $request->query . '%');
                });

            // Apply filters
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }
        if ($request->has('bedrooms')) {
                $query->where('bedrooms', $request->bedrooms);
        }
        if ($request->has('bathrooms')) {
                $query->where('bathrooms', $request->bathrooms);
            }

            $apartments = $query->paginate(10);

            return $this->successResponse($apartments, 'Search results retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Search failed', $e->getMessage(), 500);
        }
    }

    /**
     * Get featured apartments
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function featured()
    {
        try {
            $apartments = Apartment::with(['images', 'user'])
                ->where('is_featured', true)
                ->latest()
                ->take(6)
                ->get();

            return $this->successResponse($apartments, 'Featured apartments retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve featured apartments', $e->getMessage(), 500);
        }
    }

    /**
     * Get user's apartments
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userApartments(Request $request)
    {
        try {
            $apartments = Apartment::with(['images'])
                ->where('user_id', Auth::id())
            ->latest()
            ->paginate(10);
        
            return $this->successResponse($apartments, 'User apartments retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user apartments', $e->getMessage(), 500);
        }
    }
} 