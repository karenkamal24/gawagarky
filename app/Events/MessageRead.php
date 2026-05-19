<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    public function broadcastOn(): PresenceChannel
    {
        $ids = [
            $this->message->sender_id,
            $this->message->receiver_id
        ];

        sort($ids);

        return new PresenceChannel(
            'chat.' . implode('.', $ids)
        );
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'read_at'    => $this->message->read_at,
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageRead';
    }
}
