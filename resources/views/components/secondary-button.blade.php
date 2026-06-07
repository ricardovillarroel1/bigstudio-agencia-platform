<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-white border border-brand-200 rounded-lg font-semibold text-xs text-brand-700 tracking-wide shadow-sm hover:bg-brand-50 hover:border-brand-400 hover:text-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-25 transition-all duration-150']) }}>
    {{ $slot }}
</button>
