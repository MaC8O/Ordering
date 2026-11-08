@php
    $currency = config('ordering.currency');
    $fmt = fn ($c) => $c === null ? '' : number_format($c / 100, 2, '.', '');

    // Build the initial protein rows for the Alpine editor. On a failed submit
    // we repopulate from old('proteins_json'); otherwise from the saved item.
    $initialRows = [];
    if (old('proteins_json')) {
        $decoded = json_decode(old('proteins_json'), true);
        foreach (is_array($decoded) ? $decoded : [] as $r) {
            $initialRows[] = [
                'name' => (string) ($r['name'] ?? ''),
                'normal' => (string) ($r['normal'] ?? ''),
                'large' => (string) ($r['large'] ?? ''),
            ];
        }
    } elseif ($item && $item->hasProteins()) {
        foreach ($item->proteins as $p) {
            $pp = $item->protein_prices[$p] ?? null;
            $initialRows[] = [
                'name' => $p,
                // Fall back to the dish price for dishes saved before per-protein
                // pricing existed, so the admin sees sensible starting numbers.
                'normal' => $pp && isset($pp['normal']) ? $fmt($pp['normal']) : $fmt($item->price),
                'large' => $pp && array_key_exists('large', $pp) && $pp['large'] !== null
                    ? $fmt($pp['large'])
                    : $fmt($item->large_price),
            ];
        }
    }

    $dishNormal = old('price', $item && ! ($item?->hasProteins()) ? $fmt($item->price) : '');
    $dishLarge = old('large_price', $item && ! ($item?->hasProteins()) && $item->large_price !== null ? $fmt($item->large_price) : '');
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2"
      x-data="{ rows: @js($initialRows) }">
    @csrf
    @if ($item) @method('PATCH') @endif

    <div>
        <label class="label">Name (English)</label>
        <input name="name" value="{{ old('name', $item?->name) }}" class="input" required>
    </div>

    <div>
        <label class="label">ชื่อ (ไทย) <span class="font-normal text-ink-soft">— Thai name</span></label>
        <input name="name_th" value="{{ old('name_th', $item?->name_th) }}" class="input" placeholder="เช่น ผัดไทยหมู">
    </div>

    <div>
        <label class="label">Category</label>
        <select name="category_id" class="input" required>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $item?->category_id) === $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex items-end">
        <label class="flex items-center gap-2 text-sm font-semibold">
            <input type="hidden" name="is_available" value="0">
            <input type="checkbox" name="is_available" value="1" @checked(old('is_available', $item?->is_available ?? true)) class="h-4 w-4 accent-brand-600">
            Available on the storefront
        </label>
    </div>

    {{-- ===================== PRICING ===================== --}}
    <div class="sm:col-span-2 rounded-xl border border-line bg-surface p-4">
        <div class="flex items-center justify-between gap-3">
            <label class="label !mb-0">Pricing ({{ $currency }})</label>
            <button type="button" @click="rows.push({ name: '', normal: '', large: '' })"
                    class="btn-ghost !px-3 !py-1.5 text-sm">+ Add protein</button>
        </div>

        {{-- Carries the protein rows to the server. Blank-named rows are dropped. --}}
        <input type="hidden" name="proteins_json" :value="JSON.stringify(rows.filter(r => (r.name || '').trim() !== ''))">

        {{-- No proteins: one Normal + Large price for the whole dish --}}
        <div x-show="rows.length === 0" class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">Normal price</label>
                <input name="price" type="number" step="0.01" min="0" value="{{ $dishNormal }}" class="input">
            </div>
            <div>
                <label class="label">Large price <span class="font-normal text-ink-soft">— optional</span></label>
                <input name="large_price" type="number" step="0.01" min="0" value="{{ $dishLarge }}" class="input">
            </div>
        </div>

        {{-- Per-protein prices: each protein gets its own Normal + Large --}}
        <div x-show="rows.length > 0" x-cloak class="mt-3 space-y-2">
            <div class="hidden grid-cols-[1fr_6rem_6rem_2rem] gap-2 text-xs font-semibold uppercase tracking-wide text-ink-soft sm:grid">
                <span>Protein</span>
                <span>Normal</span>
                <span>Large</span>
                <span></span>
            </div>
            <template x-for="(row, i) in rows" :key="i">
                <div class="grid grid-cols-[1fr_6rem_6rem_2rem] items-center gap-2">
                    <input x-model="row.name" type="text" placeholder="e.g. Pork" class="input !py-2">
                    <input x-model="row.normal" type="number" step="0.01" min="0" placeholder="Normal" class="input !py-2">
                    <input x-model="row.large" type="number" step="0.01" min="0" placeholder="Large" class="input !py-2">
                    <button type="button" @click="rows.splice(i, 1)"
                            class="grid h-9 w-9 place-items-center rounded-lg text-lg text-red-600 hover:bg-red-50" title="Remove protein">×</button>
                </div>
            </template>
            <p class="text-xs text-ink-soft">Leave <span class="font-semibold">Large</span> empty if a protein has no large size.</p>
        </div>

        <p x-show="rows.length === 0" class="mt-2 text-xs text-ink-soft">Add proteins to give each one its own Normal &amp; Large price.</p>

        @error('proteins_json') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('price') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('large_price') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="label">Description (English)</label>
        <textarea name="description" rows="2" maxlength="500" class="input">{{ old('description', $item?->description) }}</textarea>
    </div>

    <div class="sm:col-span-2">
        <label class="label">คำอธิบาย (ไทย) <span class="font-normal text-ink-soft">— Thai description</span></label>
        <textarea name="description_th" rows="2" maxlength="500" class="input">{{ old('description_th', $item?->description_th) }}</textarea>
    </div>

    <div class="sm:col-span-2">
        <label class="label">Photo</label>
        <input name="image" type="file" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-4 file:py-2 file:font-semibold file:text-white">
        @if ($item && $item->image_path)
            <p class="mt-1 text-xs text-ink-soft">Leave empty to keep the current photo.</p>
        @else
            <p class="mt-1 text-xs text-ink-soft">Optional. Max 5 MB.</p>
        @endif
    </div>

    <div class="sm:col-span-2">
        <button class="btn-primary">{{ $item ? 'Save changes' : 'Add item' }}</button>
    </div>
</form>
