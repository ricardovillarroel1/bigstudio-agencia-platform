@props([
    'items' => [], // array de ['label' => 'X', 'url' => '#' (opcional)]
])

@if(count($items) > 0)
<nav class="text-xs text-gray-500 flex items-center gap-1.5 flex-wrap mb-4" aria-label="Breadcrumb">
    <a href="{{ url('/') }}" class="hover:text-brand-600 transition-colors flex items-center gap-1">
        <i class="fas fa-home text-xs"></i>
    </a>
    @foreach($items as $i => $item)
        <span class="text-gray-300">/</span>
        @if(isset($item['url']) && $i < count($items) - 1)
            <a href="{{ $item['url'] }}" class="hover:text-brand-600 transition-colors">{{ $item['label'] }}</a>
        @else
            <span class="{{ $i === count($items) - 1 ? 'text-gray-700 font-semibold' : '' }}">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
@endif
