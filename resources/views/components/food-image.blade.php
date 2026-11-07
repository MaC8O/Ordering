@props([
    'item',
    'rounded' => 'rounded-t-2xl',
    'imgClass' => 'transition duration-300 hover:scale-105',
])

@php
    $imageUrl = $item->image_url;
@endphp

<div class="relative aspect-square w-full overflow-hidden {{ $rounded }} bg-brand-50">
    @if ($imageUrl)
        <img src="{{ $imageUrl }}" alt="{{ $item->localized_name }}" loading="lazy"
             class="h-full w-full object-cover {{ $imgClass }}"
             onload="this.style.opacity=1" style="opacity:0;transition:opacity .3s">
    @else
        <div class="flex h-full w-full flex-col items-center justify-center bg-gradient-to-br from-brand-100 to-brand-300 text-brand-800">
            <span class="text-4xl">🍴</span>
            <span class="mt-2 px-4 text-center text-sm font-semibold">{{ $item->localized_name }}</span>
        </div>
    @endif
</div>
