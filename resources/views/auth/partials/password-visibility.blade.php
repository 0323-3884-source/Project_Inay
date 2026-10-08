@once
@push('styles')
<style>
    .auth-password-wrap { position: relative; display: block; width: 100%; min-width: 0; }
    .auth-password-wrap input { padding-right: 54px !important; }
    .auth-password-wrap .auth-password-toggle {
        position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
        display: flex; align-items: center; justify-content: center;
        width: 40px; height: 40px; min-height: 0; margin: 0; padding: 8px;
        border: 0; border-radius: 6px; background: transparent;
        color: #c90b67; font: inherit; font-size: 12px; font-weight: 700; cursor: pointer;
    }
    .auth-password-toggle svg { width: 22px; height: 22px; pointer-events: none; }
    .auth-password-wrap .auth-password-toggle:hover { background: #fff0f7; }
    .auth-password-wrap .auth-password-toggle:focus-visible { outline: 2px solid #ec0a78; outline-offset: -2px; }
</style>
@endpush
@endonce
@push('scripts')
    <script src="{{ asset('js/password-visibility.js') }}?v={{ filemtime(public_path('js/password-visibility.js')) }}" defer></script>
@endpush
