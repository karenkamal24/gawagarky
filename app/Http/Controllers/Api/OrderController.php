<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\API\ProductTrait;

class OrderController extends Controller
{
    use ProductTrait;

    public function index(Request $request)
    {
        $orders = $request->user()->orders()->with('items.product')->latest()->paginate(10);
        return response()->json(['success'=>true,'message'=>'تم جلب الطلبات','data'=>$orders->map(function($order){
            return array_merge($order->toArray(),[
                'items'=>$order->items->map(fn($i)=>array_merge($this->formatProduct($i->product),['quantity'=>$i->quantity,'price'=>$i->price]))
            ]);
        })]);
    }

    public function show(Request $request,$orderId)
    {
        $order = $request->user()->orders()->with('items.product')->findOrFail($orderId);
        return response()->json(['success'=>true,'message'=>'تم جلب تفاصيل الطلب','data'=>array_merge($order->toArray(),[
            'items'=>$order->items->map(fn($i)=>array_merge($this->formatProduct($i->product),['quantity'=>$i->quantity,'price'=>$i->price]))
        ])]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['address'=>'required|string','phone'=>'required|string|regex:/^\d{10,}$/','notes'=>'nullable|string']);
        $cartItems = $request->user()->cart()->with('product')->get();
        if($cartItems->isEmpty()) return response()->json(['success'=>false,'message'=>'السلة فارغة'],400);

        $totalPrice = $cartItems->sum(fn($i)=>($i->product->discount_price ?? $i->product->price)*$i->quantity);

        $order = $request->user()->orders()->create([
            'total_price'=>$totalPrice,
            'status'=>'pending',
            'address'=>$validated['address'],
            'phone'=>$validated['phone'],
            'notes'=>$validated['notes'] ?? null
        ]);

        foreach($cartItems as $item){
            $order->items()->create([
                'store_product_id'=>$item->store_product_id,
                'quantity'=>$item->quantity,
                'price'=>$item->product->discount_price ?? $item->product->price
            ]);
        }
        $request->user()->cart()->delete();
        return response()->json(['success'=>true,'message'=>'تم إنشاء الطلب بنجاح ✅','data'=>array_merge($order->toArray(),[
            'items'=>$order->items->map(fn($i)=>array_merge($this->formatProduct($i->product),['quantity'=>$i->quantity,'price'=>$i->price]))
        ])],201);
    }

    public function updateStatus(Request $request,$orderId)
    {
        $validated = $request->validate(['status'=>'required|in:pending,confirmed,shipped,delivered']);
        $order = Order::findOrFail($orderId);
        $order->update(['status'=>$validated['status']]);
        return response()->json(['success'=>true,'message'=>'تم تحديث حالة الطلب','data'=>$order]);
    }
}
