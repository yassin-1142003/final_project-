<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'sender' => $this->when($this->relationLoaded('sender'), function () {
                return [
                    'id' => $this->sender->id,
                    'name' => $this->sender->name,
                    'avatar' => $this->sender->profile_image ?? null,
                ];
            }),
            'receiver' => $this->when($this->relationLoaded('receiver'), function () {
                return [
                    'id' => $this->receiver->id,
                    'name' => $this->receiver->name,
                    'avatar' => $this->receiver->profile_image ?? null,
                ];
            }),
            'is_read' => $this->isRead(),
            'is_from_auth_user' => $this->sender_id === Auth::id(),
        ];
    }
} 