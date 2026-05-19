<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use Kreait\Firebase\Factory;

Route::get('/firebase-test', function () {

    $factory = (new Factory)
        ->withServiceAccount(storage_path('app/firebase/firebase-key.json'));

    $auth = $factory->createAuth();

    return 'Firebase Connected Successfully';
});
