<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\Text;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * This Job runs in the background (queue).
 * It takes a raw text, sends it to OpenAI, and saves the result as a Post.
 * It implements ShouldQueue so Laravel knows to run it asynchronously.
 */
class ProcessRawContentJob implements ShouldQueue
{
    // Queueable gives this class all the queue superpowers (delay, chain, etc.)
    use Queueable;

    /**
     * We receive the Text model when the job is created.
     * Laravel will serialize it and pass it to handle() when the worker picks it up.
     */
    public function __construct(public Text $text) {}

    /**
     * This is the main function that runs when the queue worker processes the job.
     */
    public function handle(): void
    {
        // Step 1 — Tell the database we started processing this text
        $this->text->update(['status' => 'processing']);

        // Step 2 — Load the Blueprint linked to this text
        // The Blueprint contains the style rules (tone, max hashtags, etc.)
        $blueprint = $this->text->blueprint;

        // Step 3 — Build the prompt we will send to OpenAI
        // We inject the blueprint rules and the raw text content directly into the prompt
        $prompt = "You are an expert ghostwriter for tech creators on X (Twitter).

Analyze the following raw content and extract a structured output following these EXACT rules:
- Tone: {$blueprint->tone}
- Max hashtags allowed: {$blueprint->max_hashtags}
- Max characters for the hook: {$blueprint->max_characters}
- Extra rules: {$blueprint->regle_supp}

Raw content to analyze:
{$this->text->content}

Respond ONLY with a valid JSON object. No markdown, no explanation. Use exactly this structure:
{
  \"hook_propose\": \"a catchy hook under {$blueprint->max_characters} characters\",
  \"body_points\": [\"point 1\", \"point 2\"],
  \"technical_readability_score\": 85,
  \"suggested_hashtags\": [\"#Laravel\"],
  \"tone_compliance_justification\": \"explain why the tone matches the rules\"
}";

        // Step 4 — Wrap everything in a try/catch
        // If anything goes wrong (API down, bad response, missing key), we catch the error
        try {

            // Step 5 — Send the HTTP request to GROQ API
           $response = Http::withHeaders([
    'Authorization' => 'Bearer ' . config('services.groq.key'), // Groq key
    'Content-Type'  => 'application/json',
])->post('https://api.groq.com/openai/v1/chat/completions', [ // Groq endpoint
    'model'    => 'llama-3.3-70b-versatile', // Groq model
    'messages' => [
        [
            'role'    => 'system',
            'content' => 'You are a structured JSON output generator. Never add markdown or explanation.',
        ],
        [
            'role'    => 'user',
            'content' => $prompt,
        ],
    ],
    'temperature' => 0.7,
]);

            // Step 6 — Extract the text content from the API response
            // OpenAI returns: choices[0].message.content
            $rawJson = $response->json('choices.0.message.content');

            // Step 7 — Convert the JSON string into a PHP array
            $data = json_decode($rawJson, true);

            // Step 8 — Validate that all required keys exist in the AI response
            // We never trust the AI blindly — we check the contract
            $requiredKeys = [
                'hook_propose',
                'body_points',
                'technical_readability_score',
                'suggested_hashtags',
                'tone_compliance_justification',
            ];

            foreach ($requiredKeys as $key) {
                if (!array_key_exists($key, $data)) {
                    // If a key is missing, throw an error to jump to the catch block
                    throw new \Exception("AI response is missing the key: {$key}");
                }
            }

            // Step 9 — Save the generated post in the database
            // body_points and suggested_hashtags are arrays — Eloquent casts handle the JSON encoding automatically
            Post::create([
                'text_id'                       => $this->text->id,
                'hook_propose'                  => $data['hook_propose'],
                'body_points'                   => $data['body_points'],             // array → stored as JSON automatically
                'technical_readability_score'   => $data['technical_readability_score'],
                'suggested_hashtags'            => $data['suggested_hashtags'],      // array → stored as JSON automatically
                'tone_compliance_justification' => $data['tone_compliance_justification'],
                'payload_brut'                  => $this->text->content,             // keep original content for the agent
                'status'                        => 'draft',
            ]);

            // Step 10 — Mark the text as done
            $this->text->update(['status' => 'done']);

        } catch (\Exception $e) {

            // If anything failed — mark the text as failed so the user knows
            $this->text->update(['status' => 'failed']);

            // Log the error so we can debug it later in storage/logs/laravel.log
            Log::error('ProcessRawContentJob failed: ' . $e->getMessage());
        }
    }
}