<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use App\Events\NewNotificationEvent;

class MessageController extends Controller
{
    // 📥 الحصول على جميع الرسائل
    public function getMessages(Request $request)
    {
        $userId = $request->user()->id;

        $messages = Message::with(['sender', 'recipient'])
            ->where('sender_id', $userId)
            ->orWhere('recipient_id', $userId)
            ->latest()
            ->paginate(10);

        $unreadCount = Message::where('recipient_id', $userId)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب الرسائل',
            'unread_count' => $unreadCount,
            'data' => $messages->map(fn($m) => [
                'id' => $m->id,

                // ✅ استخدام UserResource
                'sender' => new UserResource($m->sender),
                'recipient' => new UserResource($m->recipient),

                'sender_id' => $m->sender_id,
                'recipient_id' => $m->recipient_id,

                'content' => $m->content,
                'read_at' => $m->read_at,
                'created_at' => $m->created_at->format('h:i A'),
                'is_mine' => $m->sender_id === $userId,
            ])
        ]);
    }

    // ➕ إرسال رسالة
public function sendMessage(Request $request)
{
    $validated = $request->validate([
        'recipient_id' => 'required|exists:users,id',
        'content' => 'required|string|min:1',
    ]);

    $message = Message::create([
        'sender_id' => $request->user()->id,
        'recipient_id' => $validated['recipient_id'],
        'content' => $validated['content'],
    ]);

    // 🔔 إنشاء إشعار
    $recipient = User::find($validated['recipient_id']);

    if ($recipient) {
        $notification = $recipient->notifications()->create([
            'title' => 'رسالة جديدة',
            'message' => 'حصلت على رسالة من ' . $request->user()->name,
            'type' => 'message_received',
        ]);

        // ⚡️ REALTIME EVENT
        event(new NewNotificationEvent($notification));
    }

    return response()->json([
        'success' => true,
        'message' => 'تم إرسال الرسالة ✅',
        'data' => [
            'id' => $message->id,
            'content' => $message->content,
            'created_at' => $message->created_at,
        ]
    ], 201);
}

    // 💬 الحصول على محادثة مع مستخدم معين
    public function getConversation(Request $request, $userId)
    {
        $currentUserId = $request->user()->id;

        $messages = Message::with(['sender', 'recipient'])
            ->where(function ($q) use ($currentUserId, $userId) {
                $q->where('sender_id', $currentUserId)
                  ->where('recipient_id', $userId);
            })
            ->orWhere(function ($q) use ($currentUserId, $userId) {
                $q->where('sender_id', $userId)
                  ->where('recipient_id', $currentUserId);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        if ($messages->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'لا توجد رسائل بين المستخدمين'
            ]);
        }

        $data = $messages->map(fn($m) => [
            'id' => $m->id,

            // ✅ استخدام UserResource
            'sender' => new UserResource($m->sender),
            'recipient' => new UserResource($m->recipient),

            'sender_id' => $m->sender_id,
            'recipient_id' => $m->recipient_id,

            'content' => $m->content,
            'created_at' => $m->created_at->format('Y-m-d H:i:s'),
            'is_mine' => $m->sender_id === $currentUserId
        ]);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // ✅ تحديد رسالة كمقروءة
    public function markMessageAsRead(Request $request, $messageId)
    {
        $message = Message::where('id', $messageId)
            ->where('recipient_id', $request->user()->id)
            ->firstOrFail();

        $message->update([
            'read_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديد الرسالة كمقروءة'
        ]);
    }
}