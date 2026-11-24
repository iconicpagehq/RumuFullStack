<!DOCTYPE html>
<html lang="{{ Auth::check() ? Auth::user()->language : 'en' }}" 
      class="{{ (Auth::check() && Auth::user()->theme === 'dark') || (Auth::check() && Auth::user()->theme === 'auto' && request()->header('Sec-CH-Prefers-Color-Scheme') === 'dark') ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#6f0159">
    <title>@yield('title', 'Rumu App')</title>
    <link rel="icon" type="image/png" href="{{ asset('storage/public/images/icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/Icon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#6f0159',
                        secondary: '#f7daf2',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #6f0159 0%, #4a003d 100%);
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hover-lift {
            transition: all 0.3s ease;
        }

        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(111, 1, 89, 0.3);
        }

        /* Touch optimizations */
        .touch-manipulation {
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }

        @media (max-width: 640px) {
            table {
                font-size: 0.875rem;
            }
            button, a {
                min-height: 44px;
            }
        }

        /* Mobile sidebar transitions */
        .sidebar-mobile {
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
        }

        .sidebar-mobile.open {
            transform: translateX(0);
        }

        /* Overlay for mobile menu */
        .sidebar-overlay {
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease-in-out;
        }

        .sidebar-overlay.show {
            opacity: 1;
            pointer-events: auto;
        }

        /* Dark mode styles - Comprehensive */
        .dark body {
            background-color: #0f172a;
            color: #e2e8f0;
        }

        .dark .bg-white {
            background-color: #1e293b;
        }

        .dark .bg-gray-50 {
            background-color: #0f172a;
        }

        .dark .bg-gray-100 {
            background-color: #1e293b;
        }

        .dark .bg-gray-200 {
            background-color: #334155;
        }

        .dark .bg-gray-300 {
            background-color: #475569;
        }

        /* Text colors */
        .dark .text-gray-900 {
            color: #f8fafc;
        }

        .dark .text-gray-800 {
            color: #f1f5f9;
        }

        .dark .text-gray-700 {
            color: #e2e8f0;
        }

        .dark .text-gray-600 {
            color: #cbd5e1;
        }

        .dark .text-gray-500 {
            color: #94a3b8;
        }

        .dark .text-gray-400 {
            color: #64748b;
        }

        /* Border colors */
        .dark .border-gray-200 {
            border-color: #334155;
        }

        .dark .border-gray-100 {
            border-color: #1e293b;
        }

        .dark .border-gray-300 {
            border-color: #475569;
        }

        /* Shadows */
        .dark .shadow-lg {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
        }

        .dark .shadow-sm {
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.3);
        }

        .dark .shadow-xl {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
        }

        /* Header */
        .dark header {
            background-color: #1e293b;
            border-color: #334155;
        }

        /* Hover states */
        .dark .hover\:bg-gray-50:hover {
            background-color: #334155;
        }

        .dark .hover\:bg-gray-100:hover {
            background-color: #334155;
        }

        .dark .hover\:bg-gray-200:hover {
            background-color: #475569;
        }

        /* Form inputs */
        .dark input[type="text"],
        .dark input[type="email"],
        .dark input[type="password"],
        .dark input[type="tel"],
        .dark textarea,
        .dark select {
            background-color: #334155;
            border-color: #475569;
            color: #f1f5f9;
        }

        .dark input::placeholder,
        .dark textarea::placeholder {
            color: #64748b;
        }

        /* Tables */
        .dark table {
            color: #e2e8f0;
        }

        .dark thead {
            background-color: #334155;
        }

        .dark tbody tr:hover {
            background-color: #334155;
        }

        /* Status badges */
        .dark .bg-green-100 {
            background-color: rgba(34, 197, 94, 0.2);
        }

        .dark .text-green-800 {
            color: #86efac;
        }

        .dark .bg-yellow-100 {
            background-color: rgba(234, 179, 8, 0.2);
        }

        .dark .text-yellow-800 {
            color: #fde047;
        }

        .dark .bg-red-100 {
            background-color: rgba(239, 68, 68, 0.2);
        }

        .dark .text-red-800 {
            color: #fca5a5;
        }

        .dark .bg-blue-100 {
            background-color: rgba(59, 130, 246, 0.2);
        }

        .dark .text-blue-800 {
            color: #93c5fd;
        }

        .dark .bg-gray-100 {
            background-color: rgba(148, 163, 184, 0.2);
        }

        .dark .text-gray-800 {
            color: #e2e8f0;
        }

        /* Cards with colored backgrounds */
        .dark .bg-blue-50 {
            background-color: rgba(59, 130, 246, 0.15);
        }

        .dark .bg-green-50 {
            background-color: rgba(34, 197, 94, 0.15);
        }

        .dark .bg-yellow-50 {
            background-color: rgba(234, 179, 8, 0.15);
        }

        .dark .bg-purple-50 {
            background-color: rgba(168, 85, 247, 0.15);
        }

        .dark .bg-red-50 {
            background-color: rgba(239, 68, 68, 0.15);
        }

        /* Primary colors in dark mode */
        .dark .text-primary {
            color: #f7daf2;
        }

        .dark .bg-primary\/10 {
            background-color: rgba(111, 1, 89, 0.3);
        }

        .dark .bg-primary\/50 {
            background-color: rgba(111, 1, 89, 0.5);
        }
    </style>
</head>

<body class="bg-gray-50">
    @yield('content')

    <script>
        // Mobile menu toggle functionality
        function toggleMobileMenu() {
            const sidebar = document.getElementById('mobile-sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('show');
            }
        }

        // Close sidebar when clicking overlay
        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('sidebar-overlay');
            if (overlay) {
                overlay.addEventListener('click', toggleMobileMenu);
            }
        });

        // Auto theme detection for 'auto' setting
        @if(Auth::check() && Auth::user()->theme === 'auto')
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.classList.add('dark');
            }
            
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                if (e.matches) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            });
        @endif
    </script>

    <script>
        (function () {
            function setVisibleState(button, input, isVisible) {
                input.type = isVisible ? 'text' : 'password';
                button.setAttribute('aria-pressed', String(isVisible));
                const eye = button.querySelector('[data-icon="eye"]');
                const eyeOff = button.querySelector('[data-icon="eye-off"]');
                if (eye && eyeOff) {
                    eye.classList.toggle('hidden', isVisible);
                    eyeOff.classList.toggle('hidden', !isVisible);
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
                    const targetId = button.getAttribute('data-password-toggle');
                    if (!targetId) return;
                    const input = document.getElementById(targetId);
                    if (!input) return;

                    setVisibleState(button, input, false);

                    button.addEventListener('click', function () {
                        const makeVisible = input.type === 'password';
                        setVisibleState(button, input, makeVisible);
                    });
                });
            });
        })();
    </script>
    
    @yield('scripts')
</body>
</html>
