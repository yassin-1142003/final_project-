<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CommentReport;
use Illuminate\Http\Request;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CommentReportController extends Controller
{
    use ApiResponses;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of comment reports for admins
     */
    public function index(Request $request)
    {
        try {
            // Check if user is admin
            if (!$request->user()->hasRole('admin')) {
                return $this->forbiddenResponse('You do not have permission to view comment reports');
            }

            $status = $request->query('status', 'pending');
            
            $reports = CommentReport::with(['comment', 'user', 'resolvedBy'])
                ->where('status', $status)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return $this->successResponse($reports, 'Comment reports retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve comment reports', $e->getMessage(), 500);
        }
    }

    /**
     * Resolve a comment report (admin only)
     */
    public function resolve(Request $request, $id)
    {
        try {
            // Check if user is admin
            if (!$request->user()->hasRole('admin')) {
                return $this->forbiddenResponse('You do not have permission to resolve comment reports');
            }

            $validator = Validator::make($request->all(), [
                'resolution_notes' => 'required|string|max:500',
                'action' => 'required|in:approve,reject,delete'
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $report = CommentReport::with('comment')->findOrFail($id);
            
            if ($report->status !== 'pending') {
                return $this->errorResponse('This report has already been resolved', null, 400);
            }

            // Handle the report based on the action
            switch ($request->action) {
                case 'approve':
                    // Mark the report as resolved but keep the comment
                    $report->status = 'resolved';
                    $report->resolved_by = Auth::id();
                    $report->resolution_notes = $request->resolution_notes;
                    $report->resolved_at = now();
                    $report->save();
                    break;

                case 'reject':
                    // Mark the report as rejected
                    $report->status = 'rejected';
                    $report->resolved_by = Auth::id();
                    $report->resolution_notes = $request->resolution_notes;
                    $report->resolved_at = now();
                    $report->save();
                    break;

                case 'delete':
                    // Delete the comment and mark the report as resolved
                    $report->comment->delete();
                    $report->status = 'resolved';
                    $report->resolved_by = Auth::id();
                    $report->resolution_notes = $request->resolution_notes;
                    $report->resolved_at = now();
                    $report->save();
                    break;
            }

            return $this->successResponse($report, 'Comment report resolved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to resolve comment report', $e->getMessage(), 500);
        }
    }
}
