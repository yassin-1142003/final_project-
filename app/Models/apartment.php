<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Apartment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'price',
        'location',
        'bedrooms',
        'bathrooms',
        'area',
        'status',
        'type',
        'user_id',
        'is_featured',
        'is_published'
    ];

    protected $casts = [
        'price' => 'float',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'area' => 'float',
        'is_featured' => 'boolean',
        'is_published' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ApartmentImage::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Get the comments for the apartment.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get the average rating of the apartment.
     */
    public function getAverageRatingAttribute()
    {
        return $this->comments()->where('is_approved', true)->avg('rating') ?? 0;
    }

    /**
     * Get the total number of reviews.
     */
    public function getTotalReviewsAttribute()
    {
        return $this->comments()->where('is_approved', true)->count();
    }
}
