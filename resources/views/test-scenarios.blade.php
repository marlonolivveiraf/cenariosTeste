<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gerador de Cenários de Teste</title>
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
        body { font-family: 'Inter', sans-serif; }
        .glass {
            background: rgba(31, 41, 55, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex flex-col items-center justify-center p-6">

    <div class="glass w-full max-w-4xl p-8 rounded-2xl shadow-2xl">
        <h1 class="text-3xl font-bold mb-2 text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-500">
            Gerador de Cenários de Teste 🚀
        </h1>
        <p class="text-gray-400 mb-8">Automação de QA com Inteligência Artificial</p>

        <form id="scenarioForm" class="space-y-6">
            <div>
                <label class="block text-sm font-medium mb-2 text-gray-300">Enviar Documentação (PDF, DOCX, TXT)</label>
                <div class="relative border-2 border-dashed border-gray-600 rounded-lg p-6 hover:border-blue-500 transition-colors cursor-pointer text-center" onclick="document.getElementById('fileInput').click()">
                    <input type="file" id="fileInput" name="arquivo" class="hidden" accept=".pdf,.doc,.docx,.txt" onchange="updateFileName(this)">
                    <p id="fileNameDisplay" class="text-gray-400">Arraste e solte ou clique para enviar</p>
                </div>
            </div>

            <div class="flex items-center text-gray-500 text-sm font-semibold">
                <span class="w-full border-t border-gray-700"></span>
                <span class="px-3">OU</span>
                <span class="w-full border-t border-gray-700"></span>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 text-gray-300">Cole a Documentação / Requisitos</label>
                <textarea id="docInput" name="documentacao" rows="5" class="w-full bg-gray-800 border border-gray-700 rounded-lg p-4 text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all placeholder-gray-600" placeholder="O sistema de login deve bloquear a conta após 3 tentativas falhas..."></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 text-gray-300">Modelo de Cenário Personalizado (Opcional)</label>
                <textarea name="modelo" rows="4" class="w-full bg-gray-800 border border-gray-700 rounded-lg p-4 text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all placeholder-gray-600" placeholder="Defina o modelo de resposta desejado. Exemplo:&#10;1. ID:&#10;2. Título:&#10;3. Pré-condições:&#10;..."></textarea>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-3 px-6 rounded-lg transition-transform transform hover:scale-[1.01] flex items-center justify-center shadow-lg">
                <span id="btnText">Gerar Cenários</span>
                <div id="loader" class="loader ml-3 hidden"></div>
            </button>
        </form>

        <div id="resultArea" class="mt-8 hidden">
            <h2 class="text-xl font-semibold mb-4 text-blue-400">Cenários Gerados</h2>
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <pre id="outputContent" class="whitespace-pre-wrap text-sm text-gray-300 font-mono"></pre>
            </div>
            <div class="flex gap-4 mt-4">
                <button onclick="copyToClipboard()" class="text-sm text-gray-400 hover:text-white transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                    Copiar
                </button>
                <button onclick="downloadFile()" class="text-sm text-gray-400 hover:text-white transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Baixar .txt
                </button>
                <button onclick="openModal()" class="text-sm text-blue-400 hover:text-blue-300 transition-colors flex items-center gap-2 ml-4 border border-blue-500/30 px-3 py-1 rounded-md hover:bg-blue-500/10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                    Publicar no Confluence
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-2"></div>

    <!-- Publish Modal -->
    <div id="publishModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 hidden backdrop-blur-sm">
        <div class="glass w-full max-w-lg p-8 rounded-2xl shadow-2xl relative">
            <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-white">&times;</button>
            <h2 class="text-2xl font-bold mb-4 text-white">Publicar no Confluence</h2>
            
            <form id="publishForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-300">Título da Página</label>
                    <input type="text" name="title" required class="w-full bg-gray-800 border border-gray-700 rounded-lg p-3 text-gray-200 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-300">Space Key (Ex: DS, PROJ)</label>
                    <input type="text" name="space_key" required class="w-full bg-gray-800 border border-gray-700 rounded-lg p-3 text-gray-200 focus:ring-2 focus:ring-blue-500 outline-none">
                    <p class="text-xs text-gray-500 mt-1">Geralmente encontrado na URL: /wiki/spaces/<span class="text-blue-400">CHAVE</span>/...</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-300">Parent Page ID (Opcional - Pasta)</label>
                    <input type="text" name="parent_id" class="w-full bg-gray-800 border border-gray-700 rounded-lg p-3 text-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="12345678">
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition-colors flex justify-center items-center">
                    <span id="pubBtnText">Publicar</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        function showToast(message, type = 'error') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            const bgColor = type === 'success' ? 'bg-green-600' : 'bg-red-600';
            const icon = type === 'success' 
                ? '<svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>'
                : '<svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';

            toast.className = `${bgColor} text-white px-6 py-4 rounded-lg shadow-xl flex items-center transform transition-all duration-300 translate-x-full opacity-0 min-w-[300px] border border-white/10`;
            toast.innerHTML = `
                ${icon}
                <div>
                    <h4 class="font-bold text-sm uppercase mb-1">${type === 'success' ? 'Sucesso' : 'Erro'}</h4>
                    <p class="text-sm opacity-90">${message}</p>
                </div>
            `;

            container.appendChild(toast);

            requestAnimationFrame(() => {
                toast.classList.remove('translate-x-full', 'opacity-0');
            });

            setTimeout(() => {
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        function updateFileName(input) {
            const display = document.getElementById('fileNameDisplay');
            if (input.files.length > 0) {
                display.innerText = input.files[0].name;
                display.classList.add('text-blue-400');
            } else {
                display.innerText = "Arraste e solte ou clique para enviar";
                display.classList.remove('text-blue-400');
            }
        }

        async function copyToClipboard() {
            const text = document.getElementById('outputContent').innerText;
            if (!text) return; 
            await navigator.clipboard.writeText(text);
            showToast('Copiado para a área de transferência!', 'success');
        }

        function downloadFile() {
             const text = document.getElementById('outputContent').innerText;
             if (!text) return;
             const blob = new Blob([text], { type: 'text/plain' });
             const url = window.URL.createObjectURL(blob);
             const a = document.createElement('a');
             a.href = url;
             a.download = 'cenarios-de-teste.txt';
             document.body.appendChild(a);
             a.click();
             window.URL.revokeObjectURL(url);
             document.body.removeChild(a);
        }

        function openModal() {
            document.getElementById('publishModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('publishModal').classList.add('hidden');
        }

        document.getElementById('scenarioForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const fileInput = document.getElementById('fileInput');
            const docInput = document.getElementById('docInput');
            
            if (fileInput.files.length === 0 && !docInput.value.trim()) {
                showToast('Por favor, envie um arquivo ou preencha a documentação.', 'error');
                return;
            }

            const btn = document.querySelector('button[type="submit"]');
            const btnText = document.getElementById('btnText');
            const loader = document.getElementById('loader');
            const resultArea = document.getElementById('resultArea');
            const output = document.getElementById('outputContent');
            
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            btnText.innerText = "Processando...";
            loader.classList.remove('hidden');
            resultArea.classList.add('hidden');

            const formData = new FormData(e.target);

            try {
                const response = await fetch('/api/test-scenarios/generate', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                });

                const data = await response.json();

                if (!response.ok) throw new Error(data.error || 'Ocorreu um erro desconhecido.');

                output.innerText = data.cenarios;
                resultArea.classList.remove('hidden');
                resultArea.scrollIntoView({ behavior: 'smooth' });
                showToast('Cenários gerados com sucesso!', 'success');

            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
                btnText.innerText = "Gerar Cenários";
                loader.classList.add('hidden');
            }
        });

        document.getElementById('publishForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.querySelector('#publishForm button');
            const btnText = document.getElementById('pubBtnText');
            btn.disabled = true;
            btnText.innerText = "Publicando...";

            const formData = new FormData(e.target);
            formData.append('content', document.getElementById('outputContent').innerText);

            try {
                const response = await fetch('/api/test-scenarios/publish', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(Object.fromEntries(formData))
                });

                const data = await response.json();
                
                if (!response.ok) {
                    let msg = data.error || 'Erro ao publicar';
                    
                    // Handle Validation Errors
                    if (data.errors) {
                         msg += '\n' + Object.values(data.errors).map(e => e.join(', ')).join('\n');
                    }

                    // Handle Confluence Duplicate Title
                    if (msg.includes('A page with this title already exists') || msg.includes('title already exists')) {
                        msg = 'Erro: Já existe uma página com este Título neste Espaço. Por favor, escolha outro título.';
                    }

                    throw new Error(msg);
                }

                showToast('Publicado com sucesso no Confluence! 🚀', 'success');
                closeModal();
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                btn.disabled = false;
                btnText.innerText = "Publicar";
            }
        });
    </script>
</body>
</html>
