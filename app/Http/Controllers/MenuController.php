<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->with(['availableItems'])
            ->orderBy('display_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (Category $c) => $c->availableItems->isNotEmpty())
            ->values();

        $search = trim((string) $request->query('q', ''));

        if ($search !== '') {
            $categories->each(function (Category $category) use ($search) {
                $category->setRelation(
                    'availableItems',
                    $category->availableItems->filter(
                        fn (MenuItem $item) => str_contains(mb_strtolower($item->name), mb_strtolower($search))
                            || str_contains(mb_strtolower((string) $item->description), mb_strtolower($search))
                    )->values()
                );
            });

            $categories = $categories
                ->filter(fn (Category $c) => $c->availableItems->isNotEmpty())
                ->values();
        }

        return view('menu.index', [
            'categories' => $categories,
            'search' => $search,
        ]);
    }

    public function show(MenuItem $menuItem)
    {
        if (! $menuItem->is_available) {
            abort(404);
        }

        $menuItem->load('category');

        return view('menu.show', [
            'item' => $menuItem,
        ]);
    }
}
