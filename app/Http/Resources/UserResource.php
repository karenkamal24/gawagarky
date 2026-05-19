<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'email' => $this->email,
        'phone' => $this->phone,
        'role' => $this->role,
        'avatar' => $this->avatar ? asset('storage/' . $this->avatar) : null,
        'email_verified_at' => $this->email_verified_at,
        'phone_verified' => $this->phone_verified,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
    ];
}
}
