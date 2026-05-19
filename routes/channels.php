<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| هنا بتسجل القنوات اللي هتستخدمها في الـ Realtime
|
*/

// 🧪 قناة عامة للتست (اختياري)
Broadcast::channel('test-channel', function () {
    return true;
});

Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return true;
});


// Broadcast::channel('chat.{id1}.{id2}', function ($user, $id1, $id2) {
//     return (int) $user->id === (int) $id1 || (int) $user->id === (int) $id2;
// });



Broadcast::channel(
    'chat.{id1}.{id2}',
    function ($user, $id1, $id2) {
        return [
            'id'   => $user->id,
            'name' => $user->name,
        ];
    }
);
