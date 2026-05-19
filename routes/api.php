<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\UserProductController;
use App\Http\Controllers\Api\StoreProductController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\SlideController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\ChatController;

use App\Events\TestBroadcast;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Api Routes
|--------------------------------------------------------------------------
*/

// =============================================
// PUBLIC ROUTES (بدون مصادقة)
// =============================================

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::prefix('auth')->group(function () {

    /* ============================
     |  AUTH (EMAIL / PHONE)
     ============================ */

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::post('otp/send', [AuthController::class, 'sendOtp']);
    Route::post('otp/verify', [AuthController::class, 'verifyOtp']);

    Route::post('password/reset', [AuthController::class, 'resetPassword']);

    /* ============================
     |  GOOGLE AUTH (API STYLE)
     ============================ */

    Route::get('google/url', [SocialAuthController::class, 'googleUrl']);
    Route::post('google/login', [SocialAuthController::class, 'googleLogin']);

    /* ============================
     |  GOOGLE / FACEBOOK (Redirect)
     ============================ */

    Route::get('google/redirect', [SocialAuthController::class, 'googleRedirect']);
    Route::get('google/callback', [SocialAuthController::class, 'googleCallback']);

    Route::get('facebook/redirect', [AuthController::class, 'facebookRedirect']);
    Route::get('facebook/callback', [AuthController::class, 'facebookCallback']);

    // Admin Auth
    Route::prefix('admin')->group(function() {
        Route::post('login', [AdminAuthController::class, 'login']);
    });
});

Route::get('/test-broadcast', function () {
    event(new TestBroadcast());
    return 'event sent';
});

// =============================================
// PUBLIC ROUTES FOR PRODUCTS
// =============================================

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/{id}', [ProductController::class, 'show']);
});


Route::prefix('user/products')->group(function () {
    Route::get('/', [UserProductController::class, 'getAllProducts']);
    Route::get('/{productId}', [UserProductController::class, 'getProduct']);
});


Route::prefix('slides')->group(function () {
    Route::get('/', [SlideController::class, 'index']);       // GET all
    Route::get('/{id}', [SlideController::class, 'show']);    // GET one
});

// =============================================
// PUBLIC STORE ROUTES (للعملاء)
// =============================================

Route::prefix('stores')->group(function () {
    Route::get('/', [StoreController::class, 'getAllStores']);
    Route::get('/{storeName}', [StoreController::class, 'getStoreByName']);
});
// =============================================
// PUBLIC STORE PRODUCTS ROUTES
// =============================================

Route::prefix('stores/{storeName}/products')->group(function () {
    Route::get('/', [StoreProductController::class, 'getStoreProductsPublic']);
});

Route::prefix('stores/products/all')->group(function () {
    Route::get('/', [StoreProductController::class, 'getAllProducts']);
});


    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{id}', [CategoryController::class, 'show']);
    });

// =============================================
// AUTHENTICATED USER ROUTES
// =============================================

Route::middleware('auth:sanctum')->group(function () {

    /* ============================
     |  AUTH
     ============================ */

    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::delete('deleteAccount', [AuthController::class, 'deleteAccount']);
    });

    /* ============================
     |  USER PROFILE
     ============================ */

     Route::get('my-products', [UserProductController::class, 'getMyProducts']);

    Route::prefix('user')->group(function () {
        Route::get('profile', [UserProfileController::class, 'show']);
        Route::put('profile', [UserProfileController::class, 'update']);
        Route::post('profile/update', [UserProfileController::class, 'updateWithAvatar']);
    });

    /* ============================
     |  FAVORITES
     ============================ */

    Route::prefix('favorites')->group(function () {
        Route::get('/', [FavoriteController::class, 'index']);
        Route::post('/', [FavoriteController::class, 'store']);
        Route::delete('/{productId}', [FavoriteController::class, 'destroy']);
        Route::get('/check/{productId}', [FavoriteController::class, 'isFavorite']);
    });

    Route::post('/products', [ProductController::class, 'store']);

    /* ============================
     |  CART
     ============================ */

    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/', [CartController::class, 'store']);
        Route::put('/{cartId}', [CartController::class, 'update']);
        Route::delete('/{cartId}', [CartController::class, 'destroy']);
        Route::post('/clear', [CartController::class, 'clear']);
    });

    /* ============================
     |  ORDERS
     ============================ */

    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{orderId}', [OrderController::class, 'show']);
    });

    /* ============================
     |  MERCHANT VERIFICATION
     ============================ */

    Route::prefix('verification')->group(function () {
        Route::get('status', [VerificationController::class, 'getStatus']);
        Route::post('upload-image', [VerificationController::class, 'uploadImage']);
        Route::post('store-info', [VerificationController::class, 'updateStoreInfo']);
        Route::post('submit', [VerificationController::class, 'submit']);
    });

    /* ============================
     |  MERCHANT STORE
     ============================ */

    Route::prefix('store/products')->group(function () {
        Route::post('/', [StoreProductController::class, 'createProduct']);
        Route::get('/', [StoreProductController::class, 'getStoreProducts']);
        Route::get('/{productId}', [StoreProductController::class, 'getProduct']);
        Route::put('/{productId}', [StoreProductController::class, 'updateProduct']);
        Route::delete('/{productId}', [StoreProductController::class, 'deleteProduct']);
    });

    /* ============================
    |  MERCHANT STORE PRODUCTS
    ============================ */

    Route::prefix('user/products')->group(function () {
        Route::post('/', [UserProductController::class, 'createProduct']);
        Route::put('/{productId}', [UserProductController::class, 'updateProduct']);
        Route::delete('/{productId}', [UserProductController::class, 'deleteProduct']);
    });


/* ============================
 |  NOTIFICATIONS
 ============================ */

Route::prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'getNotifications']);
    Route::post('{notificationId}/read', [NotificationController::class, 'markAsRead']);
    Route::post('read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('{notificationId}', [NotificationController::class, 'deleteNotification']);
    Route::delete('/', [NotificationController::class, 'deleteAllNotifications']);
});

/* ============================
 |  MESSAGES
 ============================ */

Route::prefix('messages')->group(function () {
    Route::get('/', [MessageController::class, 'getMessages']);
    Route::post('/', [MessageController::class, 'sendMessage']);
    Route::get('conversation/{userId}', [MessageController::class, 'getConversation']);
    Route::post('{messageId}/read', [MessageController::class, 'markMessageAsRead']);
});

});

// =============================================
// ADMIN ROUTES
// =============================================

Route::middleware(['auth:sanctum', 'admin'])->group(function () {

    Route::prefix('admin/orders')->group(function () {
        Route::put('/{orderId}/status', [OrderController::class, 'updateStatus']);
    });

    Route::prefix('admin/verification')->group(function () {
        Route::get('pending', [VerificationController::class, 'getPendingVerifications']);
        Route::post('{userId}/approve', [VerificationController::class, 'approve']);
        Route::post('{userId}/reject', [VerificationController::class, 'reject']);
    });

    Route::prefix('admin/slides')->group(function () {
        Route::post('/', [SlideController::class, 'store']);      // CREATE
        Route::post('/{id}', [SlideController::class, 'update']); // UPDATE (or PUT)
        Route::delete('/{id}', [SlideController::class, 'destroy']); // DELETE
    });


    Route::prefix('admin/categories')->group(function () {
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
    });
});



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/conversations', [ChatController::class, 'conversations']);
    Route::get('/messages/{userId}', [ChatController::class, 'index']);
    Route::post('/messages/{userId}', [ChatController::class, 'send']);
    Route::post('/messages/{messageId}/read', [ChatController::class, 'markRead']);
});
