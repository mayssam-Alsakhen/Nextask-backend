<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AIController extends Controller
{

    public function generateProjectTasks(Request $request)
{
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
    ])->post(
        'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=' . env('GEMINI_API_KEY'),
        [
            "contents" => [
                [
                    "parts" => [
                        [
                           "text" => "
                                        Generate exactly 3 project tasks for this project:
                                        
                                        {$request->description}
                                        
                                        Return ONLY valid JSON.
                                        
                                        Format:
                                        
                                        {
                                          \"tasks\": [
                                            {
                                              \"title\": \"...\",
                                              \"description\": \"...\"
                                            }
                                          ]
                                        }
                                        
                                        Rules:
                                        - No markdown.
                                        - No explanation.
                                        - No ```json.
                                        - Description should contain multiple bullet points separated by \\n.
                                        - Only output JSON.
                                        "
                        ]
                    ]
                ]
            ]
        ]
    );

    // return response()->json($response->json());
    $geminiText = $response->json()['candidates'][0]['content']['parts'][0]['text'];
    $tasks = json_decode($geminiText, true);
    return response()->json($tasks);
}
}
