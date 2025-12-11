<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class OpenAIService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.openai.key');
    }

    /**
     * Gera cenários de teste baseados em documentação
     * 
     * @param string $documentation Documentação da funcionalidade
     * @param string|null $modelInstruction Template customizado (opcional)
     * @param array $context Contexto adicional do sistema (módulo, usuários, etc)
     * @param string|null $customInstruction Instruções adicionais do usuário
     * @param bool $useCache Usar cache para economizar tokens
     * @return string Cenários formatados para Confluence
     */
    public function generateTestScenarios(
        $documentation,
        $modelInstruction = null,
        array $context = [],
        $customInstruction = null,
        bool $useCache = true
    ) {
        if (!$this->apiKey) {
            throw new Exception("OpenAI API Key not configured.");
        }

        // Verificar cache
        if ($useCache) {
            $cacheKey = 'test_scenarios_' . md5($documentation . json_encode($context) . $customInstruction);
            $cached = Cache::get($cacheKey);
            if ($cached) {
                Log::info('Cenários de teste recuperados do cache');
                return $cached;
            }
        }

        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = $this->buildUserPrompt($documentation, $modelInstruction, $context, $customInstruction);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(180) // Timeout maior para documentações extensas
                ->retry(3, 100) // 3 tentativas com delay de 100ms
                ->post($this->baseUrl, [
                    'model' => 'gpt-4o', // Modelo mais robusto que gpt-4o-mini
                    'temperature' => 0.1, // Mais determinístico (era 0.2)
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                ]);

            if ($response->failed()) {
                Log::error('OpenAI API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception("OpenAI API Error: " . $response->body());
            }

            $content = $response->json('choices.0.message.content');
            $processedContent = $this->postProcessScenarios($content);

            // Validar qualidade dos cenários gerados
            $validationIssues = $this->validateScenarios($processedContent);
            if (!empty($validationIssues)) {
                Log::warning('Cenários gerados com possíveis problemas', [
                    'issues' => $validationIssues
                ]);
            }

            // Salvar em cache
            if ($useCache) {
                Cache::put($cacheKey, $processedContent, now()->addHours(24));
            }

            return $processedContent;

        } catch (Exception $e) {
            Log::error('Erro ao gerar cenários de teste', [
                'message' => $e->getMessage(),
                'documentation_length' => strlen($documentation)
            ]);
            throw $e;
        }
    }

    /**
     * Constrói o prompt de sistema com expertise em QA e ERP
     */
    private function buildSystemPrompt(): string
    {
        return "Você é um QA Senior especializado em sistemas ERP, com expertise em:
- Testes funcionais e de integração
- Casos de teste no formato Gherkin/BDD (Given-When-Then)
- Validações de regras de negócio complexas
- Testes de campos numéricos, cálculos financeiros e validações tributárias
- Nomenclatura técnica de sistemas web empresariais

Sua missão é criar cenários de teste COMPLETOS, EXECUTÁVEIS e que sigam o padrão de documentação já estabelecido pela equipe.";
    }

    /**
     * Constrói o prompt completo do usuário
     */
    private function buildUserPrompt($documentation, $modelInstruction, array $context, $customInstruction = null): string
    {
        $defaultModel = $this->getDefaultModel();
        $modelToUse = $modelInstruction ?: $defaultModel;
        $systemContext = $this->buildSystemContext($context);
        $fewShotExamples = $this->getFewShotExamples();

        $customInstructionText = $customInstruction ? "\n# INSTRUÇÕES ADICIONAIS DO USUÁRIO\n$customInstruction\n" : "";

        return <<<EOT
# MISSÃO
Gerar cenários de teste COMPLETOS e EXECUTÁVEIS no formato estabelecido pela equipe, baseados EXCLUSIVAMENTE na documentação fornecida.

# CONTEXTO DO SISTEMA
$systemContext
$customInstructionText
# PADRÃO DE ESCRITA (Estrutura a Seguir)
$modelToUse

# EXEMPLOS DE REFERÊNCIA (Few-Shot Learning)
$fewShotExamples

# DOCUMENTAÇÃO (Fonte Única de Verdade)
"""
$documentation
"""

# DIRETRIZES DE GERAÇÃO

## 📋 Estrutura Obrigatória:
1. **Título da Funcionalidade** com descrição resumida
2. **Descrição Gherkin**: "Como [usuário], Quero [ação], Para [objetivo]"
3. **Background (Contexto Inicial)**: Pré-condições compartilhadas iniciando com "Dado que"
4. **Cenários numerados** (CN-001, CN-002, ...) seguindo formato Gherkin

## ✅ Cobertura de Testes (DIVERSIFIQUE OS CENÁRIOS):

### 1. 🔄 Cenários de Funcionalidade (End-to-End)
- **Fluxos completos de negócio**: "Do cadastro até a aprovação"
- **Caminhos felizes**: O uso padrão esperado pelo usuário
- **Variações de estado**: Diferentes status (Aberto, Pendente, Concluído)

### 2. 🧩 Cenários de Integração (Modular)
- **Interação entre módulos**: Ex: "Venda impactando Estoque e Financeiro"
- **Fluxo de dados**: Verificar se dados salvos em uma tela aparecem corretamente em outra
- **Dependências**: Tentar excluir registro vinculado a outro módulo (Integridade Referencial)

### 3. 🧮 Cenários de Cálculos e Regras de Negócio
- **Cálculos Complexos**: Impostos, totais, descontos, parcelamento
- **Valores de Borda**: 0, valores negativos (se permitido), valores muito altos
- **Arredondamentos**: Verificar precisão decimal (2 vs 4 casas)
- **Regras Condicionais**: "Se cliente VIP, então desconto X"

### 4. 📱 Cenários de Responsividade e UI
- **Mobile/Tablet**: Verificar layout em telas pequenas (quebra de linha, menu)
- **Elementos Visuais**: Comportamento de modais, tooltips e mensagens flutuantes
- **Acessibilidade básica**: Navegação via teclado (Tab) se aplicável

### 5. ⚡ Cenários Não-Funcionais
- **Performance**: "Carregar lista com 1000 registros" (tempo de resposta aceitável)
- **Segurança**: Tentar acessar URL sem permissão, Injeção de caracteres maliciosos
- **Usabilidade**: Clareza de mensagens de erro, facilidade de desfazer ações

### 6. ⌨️ Validação de Campos (Entradas)
- **Obrigatoriedade**: Campos vazios
- **Tipagem**: Texto em campo numérico, data inválida
- **Limites**: Quantidade de caracteres maior que o permitido

## 🎯 Regras de Nomenclatura:
- IDs sequenciais: CN-001, CN-002, CN-003...
- Nomes descritivos e concisos: "Cadastro de custo adicional", "Exclusão bloqueada por origem"
- Use verbos de ação: "Cadastro", "Edição", "Exclusão", "Validação", "Cálculo"

## 📝 Padrão de Escrita Gherkin:
- **Quando** = ação do usuário
- **E** = ações/condições adicionais
- **Então** = resultado esperado (comportamento observável)
- Use linguagem clara, objetiva e no infinitivo
- Cada "Então" deve ser uma verificação específica e mensurável

## 🚫 Restrições:
- NÃO invente funcionalidades não mencionadas na documentação
- NÃO agrupe cenários com títulos de seção (ex: "Cenários Principais")
- NÃO use dados genéricos (ex: "campo X", "botão Y") - seja específico
- NÃO crie cenários ambíguos ou difíceis de executar
- NÃO repita validações já cobertas em outros cenários

## ✨ Boas Práticas:
- Priorize cenários críticos primeiro (fluxos principais, cálculos financeiros)
- Mantenha cenários atômicos (1 cenário = 1 validação principal)
- Use dados realistas quando mencionar exemplos
- Inclua validações de mensagens de erro/sucesso quando aplicável
- Considere diferentes perfis de usuário se mencionado na documentação

# FORMATO DE ENTREGA
Retorne APENAS os cenários de teste formatados para Confluence (formato Wiki Markup), sem introdução, conclusão ou comentários adicionais.

Estrutura final esperada:
```
Título da Funcionalidade
Descrição: Como [usuário]...
Background: Dado que...
CN-001 | Nome do cenário
Quando...
E...
Então...
E...
CN-002 | Nome do cenário
...

```
EOT;
    }

    /**
     * Template padrão no formato Gherkin/BDD usado pela equipe
     */
    private function getDefaultModel(): string
    {
        return "Título da Funcionalidade
Descrição: Como [tipo de usuário], Quero [ação/funcionalidade], Para [objetivo/benefício]

Background (Contexto Inicial):
Dado que [pré-condição compartilhada]
E [outra pré-condição se necessário]

CN-001 | Nome descritivo do cenário
Quando [ação do usuário]
E [ação/condição adicional se necessário]
Então [resultado esperado observável]
E [verificação adicional específica]
E [mais verificações se necessário]

CN-002 | Outro cenário
Quando [outra ação]
Então [resultado esperado]
";
    }

    /**
     * Exemplos reais (Few-Shot Learning) para treinar a IA
     */
    private function getFewShotExamples(): string
    {
        return "## Exemplo Real do Padrão da Equipe:
        Tela de Formação de Preço no Cadastro de Produto
Descrição: Como usuário do sistema ERP Web, Quero cadastrar e editar a formação de preço de venda, Para garantir que os preços estejam corretos e alinhados com as incidências e custos.

Background (Contexto Inicial):
Dado que estou logado no sistema
E acessei o cadastro de um produto na seção de formação de preço

CN-001 | Acesso à seção de Formação de Preço
Quando acessar a seção de formação de preço no cadastro de produto
Então a tela deve exibir o bloco \"Custos e Preços\"
E deve exibir o bloco \"Custo Médio\"
E deve exibir o bloco \"Custo de Reposição\"
E deve exibir o bloco \"Incidências\"
E deve exibir o bloco \"Markup\"
E deve exibir o bloco \"Lucro\"
E deve exibir o botão \"Compor preço\"

CN-002 | Cadastro de custo adicional
Quando preencher os campos para adicionar um custo adicional
E clicar no botão \"Salvar\"
Então o custo adicional deve ser salvo corretamente
E deve ser exibido na lista de custos adicionais

CN-003 | Exclusão de custo adicional bloqueada por origem
Quando tentar excluir um custo adicional que possui origem
Então o sistema deve bloquear a exclusão
E deve exibir uma mensagem informando que a exclusão deve ser feita na origem


👆 Use este exemplo como referência de formatação, nomenclatura e nível de detalhe.";
    }

    /**
     * Constrói contexto adicional do sistema
     */
    private function buildSystemContext(array $context): string
    {
        // Se nenhum contexto foi fornecido, retorna contexto padrão mínimo
        if (empty($context)) {
            return "Sistema: ERP Web
Módulo: Não especificado
Tipo: Aplicação Web";
        }

        $contextLines = [];

        // Adiciona apenas os campos que foram preenchidos
        if (!empty($context['sistema'])) {
            $contextLines[] = "Sistema: {$context['sistema']}";
        }

        if (!empty($context['modulo'])) {
            $contextLines[] = "Módulo: {$context['modulo']}";
        }

        if (!empty($context['tipo'])) {
            $contextLines[] = "Tipo: {$context['tipo']}";
        }

        if (!empty($context['tecnologia'])) {
            $contextLines[] = "Tecnologia: {$context['tecnologia']}";
        }

        if (!empty($context['usuarios'])) {
            $contextLines[] = "Perfis de Usuário: {$context['usuarios']}";
        }

        // Se nenhum campo específico foi preenchido, retorna contexto padrão
        if (empty($contextLines)) {
            return "Sistema: ERP Web
Tipo: Aplicação Web";
        }

        return implode("\n", $contextLines);
    }

    /**
     * Pós-processa os cenários removendo formatação desnecessária
     */
    private function postProcessScenarios(string $content): string
    {
        // Remove markdown code blocks se existirem
        $content = preg_replace('/^```[a-z]*\n/m', '', $content);
        $content = preg_replace('/\n```$/m', '', $content);

        // Remove possíveis prefixos explicativos da IA
        $content = preg_replace('/^(Aqui está|Segue|Abaixo).+:\n*/i', '', $content);

        // Normaliza quebras de linha múltiplas
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        return trim($content);
    }

    /**
     * Valida a qualidade dos cenários gerados
     */
    private function validateScenarios(string $scenarios): array
    {
        $issues = [];

        // Verifica estrutura básica Gherkin
        if (!preg_match('/CN-\d{3}/', $scenarios)) {
            $issues[] = 'Cenários sem identificadores no formato CN-XXX';
        }

        if (!str_contains($scenarios, 'Quando')) {
            $issues[] = 'Cenários sem cláusula "Quando" (ação do usuário)';
        }

        if (!str_contains($scenarios, 'Então')) {
            $issues[] = 'Cenários sem cláusula "Então" (resultado esperado)';
        }

        if (!str_contains($scenarios, 'Descrição:')) {
            $issues[] = 'Falta descrição no formato "Como/Quero/Para"';
        }

        if (!str_contains($scenarios, 'Background')) {
            $issues[] = 'Falta seção Background com pré-condições';
        }

        // Conta quantidade de cenários
        preg_match_all('/CN-\d{3}/', $scenarios, $matches);
        $scenarioCount = count($matches[0]);

        if ($scenarioCount < 3) {
            $issues[] = "Poucos cenários gerados ({$scenarioCount}). Esperado: pelo menos 5-10";
        }

        return $issues;
    }

    /**
     * Gera métricas de cobertura para adicionar ao final do documento
     */
    public function generateCoverageMetrics(string $scenarios): string
    {
        preg_match_all('/CN-\d{3}/', $scenarios, $matches);
        $totalScenarios = count($matches[0]);

        // Estimativa de cenários positivos
        $positiveScenarios = substr_count(strtolower($scenarios), 'cadastro') +
            substr_count(strtolower($scenarios), 'sucesso') +
            substr_count(strtolower($scenarios), 'exibir corretamente');

        // Estimativa de cenários negativos
        $negativeScenarios = substr_count(strtolower($scenarios), 'inválido') +
            substr_count(strtolower($scenarios), 'erro') +
            substr_count(strtolower($scenarios), 'bloqueado');

        return "\n\n---\n## 📊 Métricas de Cobertura Geradas\n" .
            "- *Total de cenários:* {$totalScenarios}\n" .
            "- *Cenários positivos (estimativa):* {$positiveScenarios}\n" .
            "- *Cenários negativos (estimativa):* {$negativeScenarios}\n" .
            "- *Data de geração:* " . date('d/m/Y H:i') . "\n";
    }

    /**
     * Gera um relatório de bug formatado para Jira
     */
    public function generateBugReport(string $activity, string $bugDetail): string
    {
        if (!$this->apiKey) {
            throw new Exception("OpenAI API Key not configured.");
        }

        $prompt = <<<EOT
Você atuará como um QA Sênior especializado em análise e documentação de defeitos.

Com base exclusivamente nas informações fornecidas pelo usuário, gere um Ticket de Bug para o Jira seguindo rigorosamente todas as regras e o template abaixo.

# DADOS FORNECIDOS PELO USUÁRIO
Atividade executada (apenas contexto, não é o bug):
"$activity"

Detalhe do erro encontrado (este é o bug real):
"$bugDetail"

# REGRAS OBRIGATÓRIAS

1. **O único erro válido está em "Detalhe do erro encontrado"**.  
   - A seção "Atividade" serve apenas para deduzir os passos e compreender o contexto.  
   - Não invente problemas adicionais, não presuma múltiplos bugs.

2. **Passos para reproduzir devem ser deduzidos a partir da Atividade**, organizados de forma lógica e direta.

3. **Siga estritamente o template abaixo**, sem alterar títulos, formatação, ordem, estilo ou estrutura.

4. **O texto deve estar em Markdown**, mantendo negritos, blocos e separadores exatamente como fornecido.

# TEMPLATE OBRIGATÓRIO

### **Descrição**

*Erro ao tentar [ação realizada], o sistema [comportamento inesperado]*.

---

### **Passos para reproduzir**

1. Acesse `[módulo/tela onde o erro ocorreu]`
2. Clique/selecione/preencha `[ação feita]`
3. Execute `[evento que desencadeia o erro]`
4. Observe que `[comportamento observado]`

---

### **Comportamento Esperado**

`[Descreva claramente o que o sistema deveria fazer após os passos acima.]`

*O sistema deveria [ação esperada]*.

---

### **Comportamento Obtido**

`[Descreva exatamente o que o sistema fez de errado.]`

*O sistema [comportamento incorreto]*.

---

### **Evidências**

- Screenshot: `[insira link ou nome do arquivo]`
- Vídeo: `[insira link ou nome do arquivo]`
- Logs: `[anexar trecho do log, erro HTTP, etc.]`
- Observações adicionais: `[comportamento intermitente, relacionado a outro bug, etc.]`

EOT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, [
                        'model' => 'gpt-4o',
                        'messages' => [
                            ['role' => 'user', 'content' => $prompt],
                        ],
                        'temperature' => 0.2,
                    ]);

            if ($response->failed()) {
                throw new Exception("OpenAI API Error: " . $response->body());
            }

            return $response->json('choices.0.message.content');
        } catch (Exception $e) {
            Log::error('Erro ao gerar bug report', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Limpa o cache de cenários
     */
    public function clearCache(?string $documentation = null): bool
    {
        if ($documentation) {
            $cacheKey = 'test_scenarios_' . md5($documentation);
            return Cache::forget($cacheKey);
        }

        // Limpa todos os caches de cenários de teste
        return Cache::flush();
    }
}