<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class OpenAIService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.openai.key');
    }

    public function generateTestScenarios($documentation, $modelInstruction = null)
    {
        if (!$this->apiKey) {
            throw new Exception("OpenAI API Key not configured.");
        }

        $systemPrompt = "Você é um especialista em QA.";
        
        $defaultModel = "
MODELO DE CENÁRIO:
1. Identificador:
2. Nome do Cenário:
3. Objetivo:
4. Pré-condições:
5. Passos:
6. Resultado Esperado:
7. Observações:
";
        $modelToUse = $modelInstruction ?: $defaultModel;

        $userPrompt = "CONTEXTO: Você é um QA Senior. Sua tarefa é gerar novos cenários de teste baseados APENAS na documentação fornecida abaixo.\n\n" .
                      "REGRA CRÍTICA: O conteúdo da seção '[modelo]' serve APENAS como exemplo de formatação/estrutura. IGNORE qualquer informação de negócio ou regras contidas no '[modelo]'. Use estritamente as regras de negócio encontradas em '[Documentação analisada]'.\n\n" .
                      "[modelo] (USE APENAS PARA ESTRUTURA, IGNORE O CONTEÚDO)\n$modelToUse\n\n" .
                      "Documentação analisada (Extraia os cenários DAQUI):\n\"$documentation\"\n\n" .
                      "Instruções Finais para MAXIMIZAR a quantidade:\n" .
                      "1. DECOMPOINHA cada regra de negócio em pelo menos 1 cenário positivo e 1 negativo.\n" .
                      "2. Para cada campo de formulário, gere testes de: validação obrigatória, tipo de dado inválido, limites (min/max).\n" .
                      "3. Explore combinações de filtros e estados.\n" .
                      "4. NÃO categorize (sem títulos como 'Cenários Principais').\n" .
                      "5. Gere uma lista ÚNICA e CONTÍNUA.\n" .
                      "6. Siga o modelo visualmente.\n" .
                      "7. SEJA EXAUSTIVO: Se houver dúvida se deve criar, CRIE.";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(120)->post($this->baseUrl, [
            'model' => 'gpt-4o-mini',
            'temperature' => 0.2,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);

        if ($response->failed()) {
            throw new Exception("OpenAI API Error: " . $response->body());
        }

        return $response->json('choices.0.message.content');
    }
}
