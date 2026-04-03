<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Project;
use App\Models\Task;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\UserResource;


class TaskResource extends JsonResource
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
            'title'       => $this->title,
            'description' => $this->description,
            'status'      => $this->status->value,
            'priority'    => $this->priority->value,
            'due_date'    => $this->due_date?->toDateString(),
            'project_id'  => $this->project_id,
            'created_by'  => $this->created_by,
            'created_at'  => $this->created_at->toISOString(),
            'project'     => new ProjectResource($this->whenLoaded('project')),
            'assignees'   => UserResource::collection($this->whenLoaded('assignees')),
            'creator'     => new UserResource($this->whenLoaded('creator')),
        ];
    }
}
