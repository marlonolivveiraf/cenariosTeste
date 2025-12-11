<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\GenerateTestScenarioRequest;
use App\Services\FileExtractor;
use App\Services\OpenAIService;
use App\Services\ConfluenceService;
use App\Models\TestScenario;
use Illuminate\Http\JsonResponse;

class TestScenarioController extends Controller
{
    public function generate(GenerateTestScenarioRequest $request, FileExtractor $extractor, OpenAIService $openai): JsonResponse
    {
        $text = $request->input('documentacao');
        $fileName = null;

        if ($request->hasFile('arquivo')) {
            $file = $request->file('arquivo');
            $fileName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();

            try {
                $extractedText = $extractor->extractText($file->getPathname(), $extension);
                // Concatenate if both exist, or just prefer file if text is empty?
                // User requirement: "Se vier arquivo: ler... Se vier texto sem arquivo: usar texto... Se nada: erro 400"
                // Implies mutually exclusive or prioritizing file. I'll prioritize file or concatenate.
                // Let's use extracted text. If user sent both, maybe they want context? 
                // Let's just use extracted text if file exists, effectively overriding 'documentacao' input or appending.
                // Requirement "Se vier arquivo... Se vier texto sem arquivo". Matches priority: File > Text.
                $text = $extractedText;
            } catch (\Exception $e) {
                return response()->json(['error' => 'Error reading file: ' . $e->getMessage()], 400);
            }
        } elseif (empty($text)) {
            return response()->json(['error' => 'No documentation or file provided.'], 400);
        }

        try {
            // Extract context fields
            $context = [
                'sistema' => $request->input('context_sistema'),
                'modulo' => $request->input('context_modulo'),
                'tipo' => $request->input('context_tipo'),
                'tecnologia' => $request->input('context_tecnologia'),
                'usuarios' => $request->input('context_usuarios'),
            ];

            // Filter empty values
            $context = array_filter($context, fn($value) => !empty($value));

            $scenarios = $openai->generateTestScenarios(
                $text,
                $request->input('modelo'),
                $context,
                $request->input('custom_instruction')
            );

            $record = TestScenario::create([
                'file_name' => $fileName,
                'documentacao' => $text,
                'cenarios' => $scenarios,
            ]);

            return response()->json([
                'id' => $record->id,
                'cenarios' => $scenarios
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error generating scenarios: ' . $e->getMessage()], 500);
        }
    }

    public function publish(Request $request, ConfluenceService $confluence): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'space_key' => 'required|string',
            'content' => 'required|string',
            'parent_id' => 'nullable|string',
        ]);

        try {
            $result = $confluence->createPage(
                $request->input('title'),
                $request->input('space_key'),
                $request->input('content'),
                $request->input('parent_id')
            );

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error publishing to Confluence: ' . $e->getMessage()], 500);
        }
    }


}
