<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-lg font-bold text-xs text-white tracking-wide bg-bs-gradient hover:shadow-bs-glow hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition-all duration-150 shadow-sm']) }}>
    {{ $slot }}
</button>
