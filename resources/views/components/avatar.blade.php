@props(['user', 'size' => 40])

@if ($user)
    @if ($user->photo_url)
        <img src="{{ $user->photo_url }}" class="rounded-circle" width="{{ $size }}" height="{{ $size }}" alt="{{ $user->name }}" style="object-fit: cover;">
    @else
        <div class="avatar-circle d-flex align-items-center justify-content-center" style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ $size / 2.5 }}px;">
            <span>{{ $user->initials }}</span>
        </div>
    @endif
@else
    <div class="avatar-circle d-flex align-items-center justify-content-center" style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ $size / 2.5 }}px;">
        <span>?</span>
    </div>
@endif
