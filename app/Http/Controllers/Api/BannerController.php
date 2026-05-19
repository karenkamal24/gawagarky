<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index()
    {
        return Banner::with('slides')->get();
    }

    public function store(Request $request)
    {
        $banner = Banner::create([
            'name' => $request->name
        ]);

        return response()->json($banner);
    }

    public function destroy($id)
    {
        Banner::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}