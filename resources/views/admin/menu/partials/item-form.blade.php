<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">
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

    <div>
        <label class="label">Price ({{ config('ordering.currency') }})</label>
        <input name="price" type="number" step="0.01" min="0" value="{{ old('price', $item ? number_format($item->price / 100, 2, '.', '') : '') }}" class="input" required>
    </div>

    <div class="sm:col-span-2">
        <label class="label">Description (English)</label>
        <textarea name="description" rows="2" maxlength="500" class="input">{{ old('description', $item?->description) }}</textarea>
    </div>

    <div class="sm:col-span-2">
        <label class="label">คำอธิบาย (ไทย) <span class="font-normal text-ink-soft">— Thai description</span></label>
        <textarea name="description_th" rows="2" maxlength="500" class="input">{{ old('description_th', $item?->description_th) }}</textarea>
    </div>

    <div>
        <label class="label">Photo</label>
        <input name="image" type="file" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-4 file:py-2 file:font-semibold file:text-white">
        @if ($item && $item->image_path)
            <p class="mt-1 text-xs text-ink-soft">Leave empty to keep the current photo.</p>
        @else
            <p class="mt-1 text-xs text-ink-soft">Optional. Max 5 MB.</p>
        @endif
    </div>

    <div class="flex items-end">
        <label class="flex items-center gap-2 text-sm font-semibold">
            <input type="hidden" name="is_available" value="0">
            <input type="checkbox" name="is_available" value="1" @checked(old('is_available', $item?->is_available ?? true)) class="h-4 w-4 accent-brand-600">
            Available on the storefront
        </label>
    </div>

    <div class="sm:col-span-2">
        <button class="btn-primary">{{ $item ? 'Save changes' : 'Add item' }}</button>
    </div>
</form>
