<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'apartment_id',
        'content',
        'rating',
        'is_approved',
        'is_pinned',
        'helpful_count',
        'unhelpful_count',
        'is_featured',
        'status'
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_pinned' => 'boolean',
        'is_featured' => 'boolean',
        'rating' => 'integer',
        'helpful_count' => 'integer',
        'unhelpful_count' => 'integer'
    ];

    /**
     * Get the user that owns the comment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the apartment that this comment belongs to.
     */
    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }

    /**
     * Get the votes for this comment.
     */
    public function votes()
    {
        return $this->hasMany(CommentVote::class);
    }

    /**
     * Check if a user has voted on this comment.
     */
    public function hasUserVoted($userId)
    {
        return $this->votes()->where('user_id', $userId)->exists();
    }

    /**
     * Get the user's vote for this comment.
     */
    public function getUserVote($userId)
    {
        return $this->votes()->where('user_id', $userId)->first();
    }

    /**
     * Scope a query to only include approved comments.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope a query to only include featured comments.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include pinned comments.
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope a query to order comments by helpfulness.
     */
    public function scopeHelpful($query)
    {
        return $query->orderByDesc('helpful_count');
    }
} 