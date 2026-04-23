<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Http\Resources\TaskResource;
use App\Models\Task;


class TaskUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public readonly Task $task)
    {
        //
    }

     // DSA — Graph: broadcast to the project channel (a set of socket IDs)
    // Only users subscribed to this project's private channel receive the event\

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('project.' . $this->task->project_id),
        ];
    }
    public function broadcastWith(): array
    {
        return (new TaskResource($this->task->load(['assignees', 'creator'])))
            ->resolve();
    }

    public function broadcastAs(): string
    {
        return 'task.updated';
    }
}
