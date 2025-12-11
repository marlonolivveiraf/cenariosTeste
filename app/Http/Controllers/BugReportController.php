<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;

class BugReportController extends Controller
{
    public function generate(Request $request, OpenAIService $openai): JsonResponse
    {
        $request->validate([
            'activity_description' => 'required|string',
            'bug_detail' => 'required|string',
        ]);

        try {
            $ticket = $openai->generateBugReport(
                $request->input('activity_description'),
                $request->input('bug_detail')
            );

            return response()->json(['ticket' => $ticket]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error generating bug ticket: ' . $e->getMessage()], 500);
        }
    }
}
