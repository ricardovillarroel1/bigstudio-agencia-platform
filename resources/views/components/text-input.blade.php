@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 rounded-lg shadow-sm transition-all duration-150 hover:border-gray-300']) !!}>
