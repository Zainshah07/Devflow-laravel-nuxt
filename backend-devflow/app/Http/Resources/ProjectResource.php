<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\TaskResource;
use App\Http\Resources\UserResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
          return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'user_id'     => $this->user_id,
            'created_at'  => $this->created_at->toISOString(),
            'owner'       => new UserResource($this->whenLoaded('owner')),
            'tasks'       => TaskResource::collection($this->whenLoaded('tasks')),
            'tasks_count' => $this->whenCounted('tasks'),
            'members'     => $this->whenLoaded('members', fn () =>
                $this->members->map(fn ($member) => [
                    'id'    => $member->id,
                    'name'  => $member->name,
                    'email' => $member->email,
                    'role'  => $member->pivot->role,
                ])
            ),
        ];
    }
}
