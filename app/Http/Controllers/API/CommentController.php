<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Apartment;
use Illuminate\Http\Request;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\CommentReport;

class CommentController extends Controller
{
    use ApiResponses;

    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    /**
     * Display a listing of comments for a specific apartment.
     */
    public function index(Request $request, $apartmentId)
    {
        try {
            $apartment = Apartment::findOrFail($apartmentId);
            $comments = $apartment->comments()
                ->with('user:id,name,profile_image')
                ->where('is_approved', true)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return $this->successResponse($comments, 'Comments retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve comments', $e->getMessage(), 500);
        }
    }

    /**
     * Store a new comment.
     */
    public function store(Request $request, $apartmentId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:1000',
                'rating' => 'required|integer|min:1|max:5'
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $apartment = Apartment::findOrFail($apartmentId);

            $comment = Comment::create([
                'user_id' => Auth::id(),
                'apartment_id' => $apartmentId,
                'content' => $request->content,
                'rating' => $request->rating,
                'is_approved' => true // You might want to change this based on your moderation needs
            ]);

            $comment->load('user:id,name,profile_image');

            return $this->successResponse($comment, 'Comment created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create comment', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified comment.
     */
    public function show($apartmentId, $id)
    {
        try {
            $comment = Comment::with('user:id,name,profile_image')
                ->where('apartment_id', $apartmentId)
                ->where('id', $id)
                ->where('is_approved', true)
                ->firstOrFail();

            return $this->successResponse($comment, 'Comment retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Comment not found', $e->getMessage(), 404);
        }
    }

    /**
     * Update the specified comment.
     */
    public function update(Request $request, $apartmentId, $id)
    {
        try {
            $comment = Comment::where('apartment_id', $apartmentId)
                ->where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:1000',
                'rating' => 'required|integer|min:1|max:5'
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $comment->update([
                'content' => $request->content,
                'rating' => $request->rating
            ]);

            $comment->load('user:id,name,profile_image');

            return $this->successResponse($comment, 'Comment updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update comment', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified comment.
     */
    public function destroy($apartmentId, $id)
    {
        try {
            $comment = Comment::where('apartment_id', $apartmentId)
                ->where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $comment->delete();

            return $this->successResponse(null, 'Comment deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete comment', $e->getMessage(), 500);
        }
    }

    /**
     * Get average rating for an apartment.
     */
    public function getAverageRating($apartmentId)
    {
        try {
            $apartment = Apartment::findOrFail($apartmentId);
            $averageRating = $apartment->comments()
                ->where('is_approved', true)
                ->avg('rating');

            return $this->successResponse([
                'average_rating' => round($averageRating, 1),
                'total_reviews' => $apartment->comments()->where('is_approved', true)->count()
            ], 'Average rating retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve average rating', $e->getMessage(), 500);
        }
    }

    /**
     * Get all pending comments (admin only).
     */
    public function pendingComments(Request $request)
    {
        try {
            // Check if user is admin
            if (!$request->user()->hasRole('admin')) {
                return $this->forbiddenResponse('You do not have permission to view pending comments');
            }

            $comments = Comment::with(['user:id,name,profile_image', 'apartment:id,title'])
                ->where('is_approved', false)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return $this->successResponse($comments, 'Pending comments retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve pending comments', $e->getMessage(), 500);
        }
    }

    /**
     * Approve a comment (admin only).
     */
    public function approveComment(Request $request, $id)
    {
        try {
            // Check if user is admin
            if (!$request->user()->hasRole('admin')) {
                return $this->forbiddenResponse('You do not have permission to approve comments');
            }

            $comment = Comment::findOrFail($id);
            $comment->is_approved = true;
            $comment->save();

            return $this->successResponse($comment, 'Comment approved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to approve comment', $e->getMessage(), 500);
        }
    }

    /**
     * Report a comment.
     */
    public function reportComment(Request $request, $apartmentId, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $comment = Comment::where('apartment_id', $apartmentId)
                ->where('id', $id)
                ->firstOrFail();

            // Create a report in the reports table
            $report = CommentReport::create([
                'comment_id' => $id,
                'user_id' => Auth::id(),
                'reason' => $request->reason,
                'status' => 'pending'
            ]);

            return $this->successResponse($report, 'Comment reported successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to report comment', $e->getMessage(), 500);
        }
    }
}
