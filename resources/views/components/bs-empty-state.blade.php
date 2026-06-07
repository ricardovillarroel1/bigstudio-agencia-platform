@props([
    'icon' => 'fa-inbox',
    'title' => 'Aún no hay nada por aquí',
    'message' => null,
    'ctaUrl' => null,
    'ctaLabel' => null,
])

<div class="p-12 text-center">
    <div class="inline-flex w-20 h-20 rounded-2xl mb-4 items-center justify-center"
         style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%);">
        <i class="fas {{ $icon }} text-3xl text-brand-600"></i>
    </div>
    <h4 class="bs-display text-xl text-gray-700 m-0">{{ $title }}</h4>
    @if($message)
        <p class="text-sm text-gray-500 mt-2 mb-0 max-w-md mx-auto">{{ $message }}</p>
    @endif
    @if($ctaUrl && $ctaLabel)
        <a href="{{ $ctaUrl }}" class="bs-btn-primary mt-5 inline-flex">
            {{ $ctaLabel }}
        </a>
    @endif
</div>
