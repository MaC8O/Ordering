<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;

class MenuController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('menuItems')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $items = \App\Models\MenuItem::with('category')
            ->orderBy('category_id')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return view('admin.menu.index', [
            'categories' => $categories,
            'items' => $items,
        ]);
    }
}
