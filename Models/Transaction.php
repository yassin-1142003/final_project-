<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'payment_method_id',
        'booking_id',
        'amount',
        'currency',
        'status', // 'pending', 'completed', 'failed', 'refunded'
        'payment_gateway', // 'stripe', 'paypal', 'vodafone_cash', etc.
        'transaction_id', // External transaction ID
        'metadata',
        'description',
        'refund_reason'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'float',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the user that owns the transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment method used for this transaction.
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the booking associated with this transaction.
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Scope a query to only include transactions with a specific status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include transactions for a specific payment gateway.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $gateway
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUsingGateway($query, $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }
} 