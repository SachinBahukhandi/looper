<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = parent::toArray($request);
        if (!empty($user['token']) && !empty($user['token']->plainTextToken)) {
            $user['token'] = $user['token']->plainTextToken;
        }
        return $user;
    }
}
