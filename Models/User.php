/**
 * Get the messages sent by the user.
 */
public function sentMessages()
{
    return $this->hasMany(Message::class, 'sender_id');
}

/**
 * Get the messages received by the user.
 */
public function receivedMessages()
{
    return $this->hasMany(Message::class, 'receiver_id');
}

/**
 * Get the notifications for the user.
 */
public function notifications()
{
    return $this->hasMany(Notification::class);
}

/**
 * Get the notification settings for the user.
 */
public function notificationSettings()
{
    return $this->hasOne(NotificationSetting::class);
}

/**
 * Get the payment methods for the user.
 */
public function paymentMethods()
{
    return $this->hasMany(PaymentMethod::class);
}

/**
 * Get the transactions for the user.
 */
public function transactions()
{
    return $this->hasMany(Transaction::class);
} 