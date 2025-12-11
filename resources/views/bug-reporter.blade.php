<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relator de Bugs - QA Studio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        gray: {
                            900: '#111827',
                            800: '#1f2937',
                            700: '#374151',
                        }
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
            background-color: #1f2937; /* Solid gray-800 for cleanliness */
            border: 1px solid #374151; /* gray-700 */
        }

        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #ef4444;
            /* Red for bugs */
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body
    class="bg-gray-950 text-gray-100 min-h-screen flex flex-col items-center justify-center p-6 selection:bg-red-500 selection:text-white">

    <!-- Back Button -->
    <a href="{{ url('/') }}"
        class="absolute top-6 left-6 text-gray-400 hover:text-white flex items-center transition-colors">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
            </path>
        </svg>
        Voltar à Home
    </a>

    <div class="glass w-full max-w-4xl p-8 rounded-xl shadow-lg mt-10">
        <h1 class="text-3xl font-bold mb-2 text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-orange-500">
            Documentador de Bugs 🐞
        </h1>
        <p class="text-gray-400 mb-8">Gere tickets de defeito padronizados para o Jira.</p>

        <form id="bugForm" class="space-y-6">
            <div>
                <label class="block text-sm font-medium mb-2 text-gray-300">Descrição da Atividade (Contexto)</label>
                <textarea name="activity_description" rows="3"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg p-4 text-gray-200 focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none transition-all placeholder-gray-600"
                    placeholder="Ex: Estava cadastrando um novo usuário no módulo administrativo..."></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 text-gray-300">Detalhe o Bug Ocorrido</label>
                <textarea name="bug_detail" rows="4"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg p-4 text-gray-200 focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none transition-all placeholder-gray-600"
                    placeholder="Ex: Ao clicar em 'Salvar', o sistema retornou erro 500 e a página ficou branca. O usuário não foi criado."></textarea>
            </div>

            <button type="submit"
                class="w-full bg-gradient-to-r from-red-600 to-orange-600 hover:from-red-700 hover:to-orange-700 text-white font-bold py-3 px-6 rounded-lg transition-transform transform hover:scale-[1.01] flex items-center justify-center shadow-lg">
                <span id="btnText">Gerar Ticket Jira</span>
                <div id="loader" class="loader ml-3 hidden"></div>
            </button>
        </form>

        <div id="resultArea" class="mt-8 hidden">
            <h2 class="text-xl font-semibold mb-4 text-red-400">Ticket Gerado</h2>
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 shadow-inner">
                <pre id="outputContent" class="whitespace-pre-wrap text-sm text-gray-300 font-mono"></pre>
            </div>
            <div class="flex gap-4 mt-4">
                <button onclick="copyToClipboard()"
                    class="text-sm text-gray-400 hover:text-white transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3">
                        </path>
                    </svg>
                    Copiar
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-2"></div>

    <script>
        function showToast(message, type = 'error') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-600' : 'bg-red-600';
            toast.className = `${bgColor} text-white px-6 py-4 rounded-lg shadow-xl flex items-center transform transition-all duration-300 translate-x-full opacity-0 min-w-[300px] border border-white/10`;
            toast.innerHTML = `<div><p class="text-sm font-bold">${type === 'success' ? 'Sucesso' : 'Erro'}</p><p class="text-sm">${message}</p></div>`;
            container.appendChild(toast);
            requestAnimationFrame(() => toast.classList.remove('translate-x-full', 'opacity-0'));
            setTimeout(() => {
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        async function copyToClipboard() {
            const text = document.getElementById('outputContent').innerText;
            if (!text) return;
            await navigator.clipboard.writeText(text);
            showToast('Copiado para a área de transferência!', 'success');
        }

        document.getElementById('bugForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('button[type="submit"]');
            const btnText = document.getElementById('btnText');
            const loader = document.getElementById('loader');
            const resultArea = document.getElementById('resultArea');
            const output = document.getElementById('outputContent');

            const activity = form.activity_description.value.trim();
            const bug = form.bug_detail.value.trim();

            if (!activity || !bug) {
                showToast('Preencha ambos os campos para gerar o ticket.', 'error');
                return;
            }

            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            btnText.innerText = "Gerando Ticket...";
            loader.classList.remove('hidden');
            resultArea.classList.add('hidden');

            try {
                const response = await fetch('/api/bug-reporter/generate', {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'Accept': 'application/json' }
                });

                const data = await response.json();
                if (!response.ok) throw new Error(data.error || 'Erro desconhecido.');

                output.innerText = data.ticket;
                resultArea.classList.remove('hidden');
                resultArea.scrollIntoView({ behavior: 'smooth' });
                showToast('Ticket gerado com sucesso!', 'success');
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
                btnText.innerText = "Gerar Ticket Jira";
                loader.classList.add('hidden');
            }
        });
    </script>
</body>

</html>