@push('styles')
<style>
    [data-recovery-form] .auth-submit {
        background: #f38bc5;
        box-shadow: none;
        transition: background-color .2s ease, box-shadow .2s ease;
    }
    [data-recovery-form] .auth-submit.is-ready {
        background: #ec0a78;
        box-shadow: 0 0 0 3px rgba(236, 10, 120, .12), 0 5px 18px rgba(236, 10, 120, .35);
    }
    [data-recovery-form] .auth-submit.is-ready:hover {
        background: #ce0969;
        box-shadow: 0 0 0 4px rgba(236, 10, 120, .16), 0 6px 22px rgba(236, 10, 120, .4);
    }
    [data-recovery-form] .auth-submit:focus-visible {
        outline: 3px solid #a60855;
        outline-offset: 4px;
    }
    @media (prefers-reduced-motion: reduce) {
        [data-recovery-form] .auth-submit { transition: none; }
    }
</style>
@endpush
@push('scripts')
    <script src="{{ asset('js/recovery-button.js') }}?v={{ filemtime(public_path('js/recovery-button.js')) }}" defer></script>
@endpush
