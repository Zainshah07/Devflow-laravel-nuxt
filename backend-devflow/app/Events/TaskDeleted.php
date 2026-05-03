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
use Illuminate\Contracts\Queue\ShouldQueue;

class TaskDeleted implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
         public readonly int $taskId,
        public readonly int $projectId
    )
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('project.' . $this->projectId),
        ];
    }
    public function broadcastWith(): array
    {
        return [
            'task_id'    => $this->taskId,
            'project_id' => $this->projectId,
        ];
    }

    public function broadcastAs(): string
    {
        return 'task.deleted';
    }
}
