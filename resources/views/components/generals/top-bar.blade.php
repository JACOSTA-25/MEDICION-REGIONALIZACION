@props([
    'title' => 'Modulo',
    'description' => null,
])

<div class="ms-topbar">
    <div>
        <h1>{{ $title }}</h1>
        @if ($description)
            <p>{{ $description }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div>
            {{ $actions }}
        </div>
    @endif
</div>
