@php
    $message = session('success') ?? session('status') ?? session('error');

    $alertClass = '';
    if (session('success') || session('status')) {
        $alertClass = 'alert-success';
    } elseif (session('error')) {
        $alertClass = 'alert-danger';
    }
@endphp

@if ($message && $alertClass)
    <div {{ $attributes->merge(['class' => 'alert ' . $alertClass . ' alert-dismissible fade show']) }} role="alert">
        {{ $message }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
