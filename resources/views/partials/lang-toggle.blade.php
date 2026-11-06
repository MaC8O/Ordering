<div class="flex items-center overflow-hidden rounded-lg border border-line text-xs font-semibold">
    <a href="{{ route('lang.switch', 'en') }}"
       class="px-2.5 py-1.5 transition {{ app()->getLocale() === 'en' ? 'bg-brand-600 text-white' : 'text-ink-soft hover:bg-hover' }}">EN</a>
    <a href="{{ route('lang.switch', 'th') }}"
       class="px-2.5 py-1.5 transition {{ app()->getLocale() === 'th' ? 'bg-brand-600 text-white' : 'text-ink-soft hover:bg-hover' }}">ไทย</a>
</div>
