<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class MessageSent implements ShouldBroadcast
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
            'id'          => $this->message->id,
            'sender_id'   => $this->message->sender_id,
            'receiver_id' => $this->message->receiver_id,

            'body'        => $this->message->body,

            'image_url'   => $this->message->image_path
                ? Storage::url($this->message->image_path)
                : null,

            'type'        => $this->message->type,

            'read_at'     => $this->message->read_at,

            'created_at'  => $this->message->created_at,

            'sender'      => [
                'id'     => $this->message->sender->id,
                'name'   => $this->message->sender->name,
                'avatar' => $this->message->sender->avatar,
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }
}
