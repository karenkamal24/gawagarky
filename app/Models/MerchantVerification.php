<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantVerification extends Model
{
    protected $fillable = [
        'user_id',
        'id_card_front',
        'id_card_back',
        'commercial_register',
        'store_front',
        'owner_photo',
        'status',
        'rejection_reason',
        'store_name',
        'store_description',
        'store_category',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // الحصول على نسبة إكمال الصور
    public function getCompletionPercentage(): int
    {
        $total = 0;
        $completed = 0;

        $requiredFields = [
            'id_card_front',
            'id_card_back',
            'commercial_register',
            'store_front',
            'owner_photo',
        ];

        foreach ($requiredFields as $field) {
            $total++;
            if ($this->$field) {
                $completed++;
            }
        }

        return $total > 0 ? (int) ($completed / $total * 100) : 0;
    }

    // فحص إذا كانت جميع الصور موجودة
    public function isComplete(): bool
    {
        return $this->getCompletionPercentage() === 100;
    }

    public function getIdCardFrontAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    public function getIdCardBackAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    public function getCommercialRegisterAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    public function getStoreFrontAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    public function getOwnerPhotoAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    // الحصول على الصور كـ URLs
    public function getImages(): array
    {
        return [
            'id_card_front' => $this->id_card_front,
            'id_card_back' => $this->id_card_back,
            'commercial_register' => $this->commercial_register,
            'store_front' => $this->store_front,
            'owner_photo' => $this->owner_photo,
        ];
    }
}