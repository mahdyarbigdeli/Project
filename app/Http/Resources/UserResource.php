<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->reference_id,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'full_name' => $this->first_name,
            // 'avatar_url' => $this->avatar_url,
            // 'last_login_at' => $this->last_login_at,
            'isAvtive' => $this->isActive,
        ];
    }
}
