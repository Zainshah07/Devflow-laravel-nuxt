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

class TaskStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Task   $task,
        public readonly string $oldStatus,
        public readonly string $newStatus
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
            new PrivateChannel('project.' . $this->task->project_id),
        ];
    }
    public function broadcastWith(): array
    {
        return [
            'task_id'    => $this->task->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'project_id' => $this->task->project_id,
        ];
    }

    public function broadcastAs(): string
    {
        return 'task.status_changed';
    }
}
