    {{-- PWA Service Worker Registration --}}
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('{{ $swUrl }}')
                    .then((registration) => {
                        console.log('SW {{ $swLabel }} registered:', registration.scope);
                    })
                    .catch((error) => {
                        console.log('SW {{ $swLabel }} registration failed:', error);
                    });
            });
        }
    </script>
