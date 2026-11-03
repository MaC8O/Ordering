@extends('layouts.admin')

@section('title', 'Menu management')

@section('content')
    <h1 class="mb-5 text-2xl font-bold">Menu management</h1>

    <div x-data="{ tab: @js(request('tab', 'items')) }">
        {{-- Tabs --}}
        <div class="mb-5 inline-flex rounded-xl border border-line bg-surface p-1">
            <button @click="tab = 'items'" :class="tab === 'items' ? 'bg-brand-600 text-white' : 'text-ink-soft'" class="rounded-lg px-4 py-2 text-sm font-semibold">Items</button>
            <button @click="tab = 'categories'" :class="tab === 'categories' ? 'bg-brand-600 text-white' : 'text-ink-soft'" class="rounded-lg px-4 py-2 text-sm font-semibold">Categories</button>
        </div>

        {{-- ================= ITEMS ================= --}}
        <div x-show="tab === 'items'" class="space-y-5">
            @if ($categories->where('is_active', true)->isEmpty())
                <div class="card p-5 text-sm text-ink-soft">Add a category first, then you can add items.</div>
            @else
                {{-- Add item --}}
                <div x-data="{ open: false }" class="card p-5">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold">Items ({{ $items->count() }})</h2>
                        <button @click="open = !open" class="btn-primary !py-2 text-sm" x-text="open ? 'Close' : '+ Add item'"></button>
                    </div>
                    <div x-show="open" x-cloak class="mt-4">
                        @include('admin.menu.partials.item-form', ['action' => route('admin.items.store'), 'item' => null, 'categories' => $categories])
                    </div>
                </div>
            @endif

            {{-- Item list --}}
            <div class="overflow-x-auto rounded-2xl border border-line bg-surface">
                <table class="w-full text-left text-sm">
                    <thead class="bg-surface-2 text-xs uppercase tracking-wide text-ink-soft">
                        <tr>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Price</th>
                            <th class="px-4 py-3">Available</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($items as $item)
                            <tr x-data="{ editing: false }">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-brand-50">
                                            @if ($item->image_path && file_exists(public_path($item->image_path)))
                                                <img src="{{ asset($item->image_path) }}" alt="{{ $item->name }}" class="h-full w-full object-cover">
                                            @else
                                                <div class="grid h-full w-full place-items-center text-lg">🍴</div>
                                            @endif
                                        </div>
                                        <span class="font-semibold">{{ $item->name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-ink-soft">{{ $item->category?->name ?? '—' }}</td>
                                <td class="px-4 py-3 font-semibold">{{ config('ordering.currency') }} {{ $item->price_formatted }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.items.toggle', $item) }}">
                                        @csrf @method('PATCH')
                                        <button class="badge {{ $item->is_available ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">
                                            {{ $item->is_available ? 'Available' : 'Hidden' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button @click="editing = !editing" class="btn-ghost !px-3 !py-1.5 text-sm">Edit</button>
                                        <form method="POST" action="{{ route('admin.items.destroy', $item) }}" onsubmit="return confirm('Remove this item? Past orders keep their history.')">
                                            @csrf @method('DELETE')
                                            <button class="btn-ghost !px-3 !py-1.5 text-sm text-red-600">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr x-show="editing" x-cloak>
                                <td colspan="5" class="bg-surface-2 px-4 py-4">
                                    @include('admin.menu.partials.item-form', ['action' => route('admin.items.update', $item), 'item' => $item, 'categories' => $categories])
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-ink-soft">No items yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ================= CATEGORIES ================= --}}
        <div x-show="tab === 'categories'" x-cloak class="space-y-5">
            <div class="card p-5">
                <h2 class="text-lg font-bold">Add category</h2>
                <form method="POST" action="{{ route('admin.categories.store') }}" class="mt-3 flex gap-2">
                    @csrf
                    <input name="name" placeholder="e.g. Breakfast" class="input" required>
                    <button class="btn-primary">Add</button>
                </form>
                @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="overflow-hidden rounded-2xl border border-line bg-surface" id="category-list">
                <table class="w-full text-left text-sm">
                    <thead class="bg-surface-2 text-xs uppercase tracking-wide text-ink-soft">
                        <tr>
                            <th class="px-4 py-3">Order</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Items</th>
                            <th class="px-4 py-3">Active</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($categories as $i => $category)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="flex gap-1">
                                        <button onclick="moveCategory({{ $category->id }}, -1)" class="rounded border px-2 {{ $i === 0 ? 'opacity-30' : '' }}" {{ $i === 0 ? 'disabled' : '' }}>↑</button>
                                        <button onclick="moveCategory({{ $category->id }}, 1)" class="rounded border px-2 {{ $loop->last ? 'opacity-30' : '' }}" {{ $loop->last ? 'disabled' : '' }}>↓</button>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="flex items-center gap-2">
                                        @csrf @method('PATCH')
                                        <input name="name" value="{{ $category->name }}" class="input !py-1.5" required>
                                        <button class="btn-ghost !px-3 !py-1.5 text-xs">Save</button>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-ink-soft">{{ $category->menu_items_count }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.categories.toggle', $category) }}">
                                        @csrf @method('PATCH')
                                        <button class="badge {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">{{ $category->is_active ? 'Active' : 'Hidden' }}</button>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                                        @csrf @method('DELETE')
                                        <button class="btn-ghost !px-3 !py-1.5 text-sm text-red-600">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-ink-soft">Use ↑ / ↓ to change the order categories appear in on the storefront.</p>
        </div>
    </div>

    <script>
        const CATEGORY_ORDER = @json($categories->pluck('id')->values());
        function moveCategory(id, dir) {
            const order = [...CATEGORY_ORDER];
            const idx = order.indexOf(id);
            const next = idx + dir;
            if (idx < 0 || next < 0 || next >= order.length) return;
            [order[idx], order[next]] = [order[next], order[idx]];
            fetch('{{ route('admin.categories.reorder') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify({ order }),
            }).then(() => location.reload());
        }
    </script>
@endsection
