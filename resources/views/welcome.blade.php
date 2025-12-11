<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QA Studio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        gray: { 900: '#111827', 800: '#1f2937', 700: '#374151' }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .glass {
            background: rgba(31, 41, 55, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body
    class="bg-gray-900 text-gray-100 min-h-screen flex flex-col items-center justify-center p-6 relative overflow-hidden">

    <!-- Background decorations -->
    <div
        class="absolute top-0 left-0 w-96 h-96 bg-blue-600 rounded-full mix-blend-multiply filter blur-3xl opacity-10 animate-blob">
    </div>
    <div
        class="absolute top-0 right-0 w-96 h-96 bg-purple-600 rounded-full mix-blend-multiply filter blur-3xl opacity-10 animate-blob animation-delay-2000">
    </div>

    <div class="glass w-full max-w-5xl p-12 rounded-3xl shadow-2xl z-10 text-center">
        <h1 class="text-5xl font-bold mb-4 text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-500">
            QA Studio
        </h1>
        <p class="text-xl text-gray-400 mb-16">Sua central de automação e documentação de qualidade.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-3xl mx-auto">
            <a href="{{ url('/test-scenarios') }}"
                class="group relative block p-8 bg-gray-800 rounded-2xl border border-gray-700 hover:border-blue-500 transition-all duration-300 transform hover:-translate-y-2 hover:shadow-xl hover:shadow-blue-500/20">
                <div
                    class="absolute inset-0 bg-gradient-to-br from-blue-600/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity rounded-2xl">
                </div>
                <div class="relative z-10">
                    <div
                        class="w-16 h-16 bg-blue-500/20 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:bg-blue-500 transition-colors duration-300">
                        <svg class="w-8 h-8 text-blue-400 group-hover:text-white" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                            </path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-2">Cenários de Teste</h2>
                    <p class="text-gray-400 text-sm">Gere cenários BDD/Gherkin a partir de documentações.</p>
                </div>
            </a>

            <a href="{{ url('/bug-reporter') }}"
                class="group relative block p-8 bg-gray-800 rounded-2xl border border-gray-700 hover:border-red-500 transition-all duration-300 transform hover:-translate-y-2 hover:shadow-xl hover:shadow-red-500/20">
                <div
                    class="absolute inset-0 bg-gradient-to-br from-red-600/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity rounded-2xl">
                </div>
                <div class="relative z-10">
                    <div
                        class="w-16 h-16 bg-red-500/20 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:bg-red-500 transition-colors duration-300">
                        <svg class="w-8 h-8 text-red-400 group-hover:text-white" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-2">Documentar Bugs</h2>
                    <p class="text-gray-400 text-sm">Crie tickets de defeito para Jira com padrão profissional.</p>
                </div>
            </a>
        </div>
    </div>
</body>

</html>