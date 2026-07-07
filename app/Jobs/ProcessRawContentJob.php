<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\Text;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessRawContentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Text $text) {}

    public function handle(): void
    {
        $this->text->update(['status' => 'processing']);

        $blueprint = $this->text->blueprint;

        $prompt = "You are an expert ghostwriter for tech creators on X (Twitter).

Analyze the following raw content and extract a structured output following these EXACT rules:
- Tone: {$blueprint->tone}
- Max hashtags allowed: {$blueprint->max_hashtags}
- Max characters for the hook: {$blueprint->max_characters}
- Extra rules: {$blueprint->regle_supp}

Raw content to analyze:
{$this->text->content}

Respond ONLY with a valid JSON object. No markdown, no code blocks, no explanation. Use exactly this structure:
{
  \"hook_propose\": \"a catchy hook under {$blueprint->max_characters} characters\",
  \"body_points\": [\"point 1\", \"point 2\"],
  \"technical_readability_score\": 85,
  \"suggested_hashtags\": [\"#Laravel\"],
  \"tone_compliance_justification\": \"explain why the tone matches\"
}";

        try {
            // Call Groq API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.groq.key'),
                'Content-Type'  => 'application/json',
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'    => 'llama-3.3-70b-versatile',
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => 'You are a structured JSON output generator. Never add markdown, code blocks, or explanation. Return only raw JSON.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.7,
            ]);

            // Log the raw response for debugging
            Log::info('Groq raw response: ' . $response->body());

            // Extract the content from Groq response
            $rawJson = $response->json('choices.0.message.content');

            // Clean any markdown code blocks if Groq added them
            $rawJson = preg_replace('/```json\s*/i', '', $rawJson);
            $rawJson = preg_replace('/```\s*/i', '', $rawJson);
            $rawJson = trim($rawJson);

            // Decode JSON string to PHP array
            $data = json_decode($rawJson, true);

            // Check if decode failed
            if (!is_array($data)) {
                throw new \Exception('Failed to decode Groq JSON response. Raw: ' . $rawJson);
            }

            // Validate all required keys exist
            $requiredKeys = [
                'hook_propose',
                'body_points',
                'technical_readability_score',
                'suggested_hashtags',
                'tone_compliance_justification',
            ];

            foreach ($requiredKeys as $key) {
                if (!array_key_exists($key, $data)) {
                    throw new \Exception("Missing key in AI response: {$key}");
                }
            }

            // Save the generated post
            Post::create([
                'text_id'                       => $this->text->id,
                'hook_propose'                  => $data['hook_propose'],
                'body_points'                   => $data['body_points'],
                'technical_readability_score'   => $data['technical_readability_score'],
                'suggested_hashtags'            => $data['suggested_hashtags'],
                'tone_compliance_justification' => $data['tone_compliance_justification'],
                'payload_brut'                  => $this->text->content,
                'status'                        => 'draft',
            ]);

            $this->text->update(['status' => 'done']);

        } catch (\Exception $e) {
            $this->text->update(['status' => 'failed']);
            Log::error('ProcessRawContentJob failed: ' . $e->getMessage());
        }
    }
}