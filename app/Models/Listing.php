<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Listing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'ad_type_id',
        'title',
        'description',
        'price',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'bedrooms',
        'bathrooms',
        'area',
        'property_type',
        'listing_type',
        'is_furnished',
        'floor_number',
        'insurance_months',
        'features',
        'is_approved',
        'is_active',
        'is_paid',
        'expiry_date',
        'is_featured',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'area' => 'decimal:2',
        'is_approved' => 'boolean',
        'is_active' => 'boolean',
        'is_paid' => 'boolean',
        'is_featured' => 'boolean',
        'is_furnished' => 'boolean',
        'features' => 'array',
        'expiry_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function adType()
    {
        return $this->belongsTo(AdType::class);
    }

    public function images()
    {
        return $this->hasMany(ListingImage::class);
    }

    public function propertyImages()
    {
        return $this->hasMany(ListingImage::class)
            ->where('is_ownership_proof', false);
    }

    public function ownershipProofImages()
    {
        return $this->hasMany(ListingImage::class)
            ->where('is_ownership_proof', true);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function favoriteByUsers()
    {
        return $this->belongsToMany(User::class, 'favorites')
            ->withTimestamps();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getPrimaryImage()
    {
        return $this->images()->where('is_primary', true)->first() 
            ?? $this->images->first();
    }

    public function incrementViews()
    {
        $this->increment('views');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where('is_approved', true)
                     ->where('is_paid', true)
                     ->where('expiry_date', '>=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCity($query, $city)
    {
        return $query->where('city', $city);
    }

    public function scopeByPropertyType($query, $propertyType)
    {
        return $query->where('property_type', $propertyType);
    }

    public function scopeByListingType($query, $listingType)
    {
        return $query->where('listing_type', $listingType);
    }

    public function scopeByPriceRange($query, $min, $max)
    {
        return $query->where('price', '>=', $min)
                     ->where('price', '<=', $max);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function apartment()
    {
        return $this->hasOne(Apartment::class);
    }
}
