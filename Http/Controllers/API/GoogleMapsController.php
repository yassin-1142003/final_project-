<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\SavedLocation;
use Illuminate\Support\Facades\Log;

class GoogleMapsController extends Controller
{
    /**
     * Get nearby places based on location
     */
    public function getNearbyPlaces(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'nullable|integer|min:500|max:50000',
            'type' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $apiKey = config('services.google.maps_key');
        $radius = $request->radius ?? 1500;
        $type = $request->type ?? 'restaurant';
        
        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
                'location' => $request->lat . ',' . $request->lng,
                'radius' => $radius,
                'type' => $type,
                'key' => $apiKey
            ]);
            
            return response()->json([
                'data' => $response->json()
            ]);
        } catch (\Exception $e) {
            Log::error('Google Maps API error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch nearby places',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get directions between two points
     */
    public function getDirections(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin_lat' => 'required|numeric',
            'origin_lng' => 'required|numeric',
            'destination_lat' => 'required|numeric',
            'destination_lng' => 'required|numeric',
            'mode' => 'nullable|string|in:driving,walking,bicycling,transit',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $apiKey = config('services.google.maps_key');
        $mode = $request->mode ?? 'driving';
        
        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/directions/json', [
                'origin' => $request->origin_lat . ',' . $request->origin_lng,
                'destination' => $request->destination_lat . ',' . $request->destination_lng,
                'mode' => $mode,
                'key' => $apiKey
            ]);
            
            return response()->json([
                'data' => $response->json()
            ]);
        } catch (\Exception $e) {
            Log::error('Google Maps API error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch directions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Geocode an address to coordinates
     */
    public function geocodeAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $apiKey = config('services.google.maps_key');
        
        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $request->address,
                'key' => $apiKey
            ]);
            
            return response()->json([
                'data' => $response->json()
            ]);
        } catch (\Exception $e) {
            Log::error('Google Maps API error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to geocode address',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Save a location for a user
     */
    public function saveUserLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'address' => 'required|string|max:255',
            'type' => 'required|string|in:home,work,favorite,other',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $location = SavedLocation::create([
                'user_id' => $request->user()->id,
                'name' => $request->name,
                'latitude' => $request->lat,
                'longitude' => $request->lng,
                'address' => $request->address,
                'type' => $request->type,
            ]);
            
            return response()->json([
                'message' => 'Location saved successfully',
                'data' => $location
            ], 201);
        } catch (\Exception $e) {
            Log::error('Save location error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to save location',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get user's saved locations
     */
    public function getUserSavedLocations(Request $request)
    {
        try {
            $locations = SavedLocation::where('user_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'data' => $locations
            ]);
        } catch (\Exception $e) {
            Log::error('Get saved locations error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch saved locations',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 