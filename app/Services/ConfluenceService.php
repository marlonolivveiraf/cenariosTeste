<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class ConfluenceService
{
    protected $baseUrl;
    protected $email;
    protected $apiToken;

    public function __construct()
    {
        $this->baseUrl = config('services.confluence.url');
        $this->email = config('services.confluence.email');
        $this->apiToken = config('services.confluence.token');
    }

    public function createPage($title, $spaceKey, $content, $parentId = null)
    {
        if (!$this->baseUrl || !$this->email || !$this->apiToken) {
            throw new Exception("Confluence credentials not configured.");
        }

        $formattedContent = $this->formatContent($content, $title);

        $data = [
            'title' => $title,
            'type' => 'page',
            'space' => [
                'key' => $spaceKey,
            ],
            'body' => [
                'storage' => [
                    'value' => $formattedContent,
                    'representation' => 'storage',
                ],
            ],
        ];

        if ($parentId) {
            $data['ancestors'] = [
                ['id' => $parentId],
            ];
        }

        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->baseUrl . '/wiki/rest/api/content', $data);

        if ($response->failed()) {
            throw new Exception("Confluence API Error: " . $response->body());
        }

        return $response->json();
    }

    private function formatContent($text, $title)
    {
        $lines = explode("\n", $text);
        $htmlBuffer = ''; // Remove the redundant Title line at the top
        
        $htmlBuffer .= '<ac:structured-macro ac:name="info" ac:schema-version="1">
            <ac:rich-text-body>
                <p>Cenários gerados via IA com base na documentação fornecida.</p>
            </ac:rich-text-body>
        </ac:structured-macro>';

        $inExpand = false;
        $currentBlock = [];
        $isFirstLine = true;

        $flushBlock = function() use (&$currentBlock, &$htmlBuffer) {
            if (!empty($currentBlock)) {
                $htmlBuffer .= '<p>' . implode('<br/>', $currentBlock) . '</p>';
                $currentBlock = [];
            }
        };
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Treat the very first line as the H1 Title (Feature Name)
            if ($isFirstLine) {
                $htmlBuffer .= '<h1>' . htmlspecialchars($line) . '</h1>';
                $isFirstLine = false;
                continue;
            }

            // Check for Scenario Header
            if (preg_match('/^(CN-|CEN-|CT-)\d+\s*\|/i', $line) || preg_match('/^Cenário \d+:/i', $line)) {
                $flushBlock(); 
                
                if ($inExpand) {
                    $htmlBuffer .= '</ac:rich-text-body></ac:structured-macro>';
                }
                
                $titleMatch = htmlspecialchars($line);
                $htmlBuffer .= '<ac:structured-macro ac:name="expand" ac:schema-version="1">
                    <ac:parameter ac:name="title">' . $titleMatch . '</ac:parameter>
                    <ac:rich-text-body>';
                $inExpand = true;
                continue;
            }

            // Formatting Logic
            $formattedLine = htmlspecialchars($line);

            // Bold specific keywords and ensure Colon
            // Regex to match "Descrição" optionally followed by colon, replaced by bold+colon
            $formattedLine = preg_replace('/^Descrição:?$/u', '<strong>Descrição:</strong>', $formattedLine);
            $formattedLine = preg_replace('/^Background \(Contexto Inicial\):?$/u', '<strong>Background (Contexto Inicial):</strong>', $formattedLine);

            // Bold Gherkin Keywords at start
            $formattedLine = preg_replace('/^(Quando|Então|E|Dado)\b/u', '<strong>$1</strong>', $formattedLine);

            $currentBlock[] = $formattedLine;
        }

        $flushBlock();

        if ($inExpand) {
            $htmlBuffer .= '</ac:rich-text-body></ac:structured-macro>';
        }

        return $htmlBuffer;
    }
}
