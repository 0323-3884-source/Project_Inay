@push('styles')
<style>
    .auth-password-wrap { position: relative; width: 100%; }
    .auth-password-wrap .auth-input { padding-right: 76px; }
    .auth-password-toggle {
        position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
        min-width: 60px; min-height: 44px; padding: 8px;
        border: 0; border-radius: 6px; background: transparent;
        color: #c90b67; font: inherit; font-size: 12px; font-weight: 700; cursor: pointer;
    }
    .auth-password-toggle:hover { background: #fff0f7; }
    .auth-password-toggle:focus-visible { outline: 2px solid #ec0a78; outline-offset: -2px; }
</style>
@endpush
@push('scripts')
    <script src="{{ asset('js/password-visibility.js') }}?v={{ filemtime(public_path('js/password-visibility.js')) }}" defer></script>
@endpush
