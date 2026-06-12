<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    /** Is the given user a participant in this message's conversation? */
    public function involves(int $userId): bool
    {
        return $this->sender_id === $userId || $this->recipient_id === $userId;
    }
}
