<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // الحصول على جميع الإشعارات
    public function getNotifications(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(10);

        $unreadCount = $request->user()
            ->notifications()
            ->where('read_at', null)
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب الإشعارات',
            'unread_count' => $unreadCount,
            'data' => $notifications->map(fn($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'message' => $n->message,
                'type' => $n->type,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at->format('h:i A'),
            ])
        ]);
    }

    // تحديد إشعار كمقروء
    public function markAsRead(Request $request, $notificationId)
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($notificationId);

        $notification->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديد الإشعار كمقروء'
        ]);
    }

    // تحديد جميع الإشعارات كمقروءة
    public function markAllAsRead(Request $request)
    {
        $request->user()
            ->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديد جميع الإشعارات كمقروءة'
        ]);
    }

    // حذف إشعار
    public function deleteNotification(Request $request, $notificationId)
    {
        $request->user()
            ->notifications()
            ->findOrFail($notificationId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الإشعار'
        ]);
    }

    // حذف جميع الإشعارات
    public function deleteAllNotifications(Request $request)
    {
        $request->user()->notifications()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف جميع الإشعارات'
        ]);
    }
}