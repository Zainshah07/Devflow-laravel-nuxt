<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    protected $fillable = ['user_id', 'token_hash', 'expires_at', 'revoked'];
    protected $casts = ['expires_at' => 'datetime', 'revoked' => 'boolean'];

    public function user(){
        return $this->belongsTo(User::class);
    }
      // Check whether this refresh token is still usable
    public function isValid(): bool
    {
        return !$this->revoked
            && $this->expires_at->isFuture();
    }
}
