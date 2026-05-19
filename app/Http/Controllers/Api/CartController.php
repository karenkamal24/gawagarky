<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\StoreProduct;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ProductTrait;

    public function index(Request $request)
    {
        $cartItems = $request->user()->cart()->with('product')->get();
        $totalPrice = $cartItems->sum(fn($item)=>($item->product->discount_price ?? $item->product->price)*$item->quantity);

        return response()->json([
            'success'=>true,
            'message'=>'تم جلب السلة',
            'data'=>[
                'items'=>$cartItems->map(fn($i)=>array_merge($this->formatProduct($i->product),['quantity'=>$i->quantity])),
                'total_price'=>$totalPrice,
                'count'=>$cartItems->count()
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['store_product_id'=>'required|exists:store_products,id','quantity'=>'required|integer|min:1']);
        $cartItem = $request->user()->cart()->updateOrCreate(['store_product_id'=>$validated['store_product_id']],['quantity'=>$validated['quantity']]);
        return response()->json(['success'=>true,'message'=>'تمت الإضافة للسلة 🛒','data'=>array_merge($this->formatProduct($cartItem->product),['quantity'=>$cartItem->quantity])],201);
    }

    public function update(Request $request,$cartId)
    {
        $validated = $request->validate(['quantity'=>'required|integer|min:1']);
        $cartItem = $request->user()->cart()->findOrFail($cartId);
        $cartItem->update(['quantity'=>$validated['quantity']]);
        return response()->json(['success'=>true,'message'=>'تم تحديث الكمية','data'=>array_merge($this->formatProduct($cartItem->product),['quantity'=>$cartItem->quantity])]);
    }

    public function destroy(Request $request,$cartId)
    {
        $request->user()->cart()->findOrFail($cartId)->delete();
        return response()->json(['success'=>true,'message'=>'تم حذف من السلة']);
    }

    public function clear(Request $request)
    {
        $request->user()->cart()->delete();
        return response()->json(['success'=>true,'message'=>'تم تفريغ السلة']);
    }
}
