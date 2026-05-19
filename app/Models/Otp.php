<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Otp extends Model
{
    protected $fillable = [
        'identifier',
        'otp',
        'type',
        'is_used',
        'expires_at',
        'ip_address',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    /**
     * Scope: OTP صالح
     */
    public function scopeValid($query)
    {
        return $query
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now());
    }

    /**
     * تعليم OTP كمُستخدم
     */
    public function markAsUsed(): void
    {
        $this->update([
            'is_used' => true,
        ]);
    }
}
