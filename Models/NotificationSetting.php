<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'email_notifications',
        'push_notifications',
        'new_message_notification',
        'new_property_notification',
        'saved_search_notification',
        'booking_update_notification',
        'system_notification'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_notifications' => 'boolean',
        'push_notifications' => 'boolean',
        'new_message_notification' => 'boolean',
        'new_property_notification' => 'boolean',
        'saved_search_notification' => 'boolean',
        'booking_update_notification' => 'boolean',
        'system_notification' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that owns the notification settings.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 