<?php

namespace App\Services;

use Kreait\Firebase\Factory;

class FirebaseChatService
{
    protected $database;

    public function __construct()
    {
        $this->database = (new Factory)

            ->withServiceAccount(
                storage_path('app/firebase/firebase-key.json')
            )

            ->withDatabaseUri(
                'https://gawahergy-42754-default-rtdb.firebaseio.com/'
            )

            ->createDatabase();
    }

    public function sendMessage($message)
    {
        $ids = [
            $message->sender_id,
            $message->receiver_id
        ];

        sort($ids);

        $roomId = 'private_'.$ids[0].'_'.$ids[1];

        $this->database
            ->getReference('chats/'.$roomId.'/messages')
            ->push([

                'id' => $message->id,

                'sender_id' => $message->sender_id,

                'receiver_id' => $message->receiver_id,

                'body' => $message->body,

                'created_at' => $message->created_at
                    ->toDateTimeString(),
            ]);
    }
}
