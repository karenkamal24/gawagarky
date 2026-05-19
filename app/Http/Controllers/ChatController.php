<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\FCMService;
use App\Models\User;


class ChatController extends Controller
{
    /**
     * Get all conversations
     */



 public function conversations()
{
    $userId = auth()->id();

    $messages = Message::where('sender_id', $userId)
        ->orWhere('receiver_id', $userId)
        ->with(['sender', 'receiver'])
        ->latest()
        ->get();

    $conversations = $messages->groupBy(function ($message) use ($userId) {

        return $message->sender_id === $userId
            ? $message->receiver_id
            : $message->sender_id;

    })->map(function ($msgs) use ($userId) {

        $last = $msgs->first();

        $other = $last->sender_id === $userId
            ? $last->receiver
            : $last->sender;

        // avatar url
        $other->avatar = $other->avatar
            ? url(Storage::url($other->avatar))
            : null;

        return [

            'user' => [
                'id' => $other->id,
                'name' => $other->name,
                'email' => $other->email,
                'email_verified_at' => $other->email_verified_at,
                'role' => $other->role,
                'created_at' => $other->created_at,
                'updated_at' => $other->updated_at,
                'phone' => $other->phone,
                'avatar' => $other->avatar,
                'phone_verified' => $other->phone_verified,
                'phone_verified_at' => $other->phone_verified_at,
                'google_id' => $other->google_id,
                'facebook_id' => $other->facebook_id,
                'fcm_token' => $other->fcm_token,
            ],

            'last_message' => [

                'id' => $last->id,
                'body' => $last->body,

                'image_url' => $last->image_path
                    ? url(Storage::url($last->image_path))
                    : null,

                'type' => $last->type,

                'created_at' => $last->created_at,

            ],

            'unread_count' => $msgs->where('receiver_id', $userId)
                ->whereNull('read_at')
                ->count(),

        ];

    })->values();

    return response()->json($conversations, 200);
}
    /**
     * Get messages between users  puser
     */
public function index($userId)
{
    $messages = Message::where(function ($q) use ($userId) {

            $q->where('sender_id', auth()->id())
              ->where('receiver_id', $userId);

        })
        ->orWhere(function ($q) use ($userId) {

            $q->where('sender_id', $userId)
              ->where('receiver_id', auth()->id());

        })
        ->with('sender')
        ->orderBy('created_at')
        ->get()
        ->map(function ($message) {

            return [

                'id' => $message->id,

                'sender_id' => $message->sender_id,

                'receiver_id' => $message->receiver_id,

                'body' => $message->body,

                'image_url' => $message->image_path
                    ? url(Storage::url($message->image_path))
                    : null,

                'type' => $message->type,

                'read_at' => $message->read_at
                    ? $message->read_at
                        ->timezone('Africa/Cairo')
                        ->format('h:i')
                    : null,

                'created_at' => $message->created_at
                    ->timezone('Africa/Cairo')
                    ->format('h:i'),

                'sender' => [

                    'id' => $message->sender->id,

                    'name' => $message->sender->name,

                    'avatar' => $message->sender->avatar
                        ? url(Storage::url($message->sender->avatar))
                        : null,

                ],

            ];
        });

    return response()->json($messages);
}
  public function send(Request $request, $receiverId)
{
    $request->validate([
        'body' => 'required|string',
    ]);

    $message = Message::create([
        'sender_id'   => auth()->id(),
        'receiver_id' => $receiverId,
        'body'        => $request->body,
        'type'        => 'text',
    ]);

    $message->load('sender');

    broadcast(new MessageSent($message))->toOthers();

    $receiver = User::find($receiverId);

    if ($receiver && $receiver->fcm_token) {

        try {

            $fcm = new FCMService();

            $fcm->sendNotification(
                $receiver->fcm_token,
                $message->sender->name,
                $message->body,
                [
                    'type'        => 'new_message',
                    'sender_id'   => (string) $message->sender_id,
                    'sender_name' => $message->sender->name,
                    'message_id'  => (string) $message->id,
                ]
            );

        } catch (\Exception $e) {

            Log::error('FCM Error', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    return response()->json([

        'id' => $message->id,

        'sender_id' => $message->sender_id,

        'receiver_id' => $message->receiver_id,

        'body' => $message->body,

        'type' => $message->type,

        'created_at' => $message->created_at
            ->timezone('Africa/Cairo')
            ->toIso8601String(),

        'sender' => [

            'id' => $message->sender->id,

            'name' => $message->sender->name,

            'avatar' => $message->sender->avatar
                ? url(Storage::url($message->sender->avatar))
                : null,

        ],

    ], 201);
}
public function markRead($messageId)
{
    $message = Message::findOrFail($messageId);


    if ($message->receiver_id !== auth()->id()) {
        return response()->json(['status' => 'unauthorized'], 403);
    }

    $message->update([
        'read_at' => now()->setTimezone('Africa/Cairo'),
    ]);


    broadcast(new \App\Events\MessageRead($message))->toOthers();

    return response()->json(['status' => 'ok']);
}
}
