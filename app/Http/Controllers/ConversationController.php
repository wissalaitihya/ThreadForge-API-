<?php

namespace App\Http\Controllers;

use App\AI\Tools\GetCampaignRulesTool;
use App\AI\Tools\GetPostHistoryTool;
use App\Http\Requests\ChatMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ConversationController extends Controller
{
    /**
     * @group Agent
     * Send a message to the Ghostwriter Agent about a specific post.
     * The agent has memory and can call real tools to avoid hallucinations.
     */
    public function chat(ChatMessageRequest $request, Post $post): JsonResponse
    {
        // Step 1 — Check ownership via policy
        $this->authorize('view', $post);

        // Step 2 — Get or create a conversation for this post + user
        $conversation = Conversation::firstOrCreate([
            'user_id' => $request->user()->id,
            'post_id' => $post->id,
        ]);

        // Step 3 — Save the user message in DB (memory)
        Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $request->message,
        ]);

        // Step 4 — Load full conversation history for context
        $history = $conversation->messages()
            ->get()
            ->map(fn($msg) => [
                'role'    => $msg->role,
                'content' => $msg->content,
            ])
            ->toArray();

        // Step 5 — Define tools the agent can call
        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'getCampaignRules',
                    'description' => 'Get the style rules of a blueprint. Call this when the user asks about blueprint rules or constraints.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'blueprint_id' => [
                                'type'        => 'integer',
                                'description' => 'The ID of the blueprint',
                            ],
                        ],
                        'required' => ['blueprint_id'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'getPostHistory',
                    'description' => 'Get the full content of a generated post. Call this when the user asks about the post content.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'post_id' => [
                                'type'        => 'integer',
                                'description' => 'The ID of the post',
                            ],
                        ],
                        'required' => ['post_id'],
                    ],
                ],
            ],
        ];

        // Step 6 — Build system prompt with context
        $post->load('text.blueprint');
        $systemPrompt = "You are a Ghostwriter Assistant for a tech creator on X (Twitter).
You are helping them refine a generated post.
Post ID: {$post->id}
Blueprint ID: {$post->text->blueprint_id}
Current hook: {$post->hook_propose}
Current status: {$post->status}

Always use the available tools to fetch real data instead of guessing.
Never invent blueprint rules or post content.";

        // Step 7 — First API call to Groq
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.groq.key'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'    => 'llama-3.3-70b-versatile',
            'messages' => array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $history
            ),
            'tools'       => $tools,
            'tool_choice' => 'auto',
        ]);

        $responseData  = $response->json();
        $assistantMsg  = $responseData['choices'][0]['message'];
        $finalReply    = '';

        // Step 8 — Check if agent wants to call a tool
        if (!empty($assistantMsg['tool_calls'])) {

            $toolCall   = $assistantMsg['tool_calls'][0];
            $toolName   = $toolCall['function']['name'];
            $toolArgs   = json_decode($toolCall['function']['arguments'], true);

            // Step 9 — Execute the real PHP tool
            if ($toolName === 'getCampaignRules') {
                $toolResult = (new GetCampaignRulesTool())->getCampaignRules($toolArgs['blueprint_id']);
            } elseif ($toolName === 'getPostHistory') {
                $toolResult = (new GetPostHistoryTool())->getPostHistory($toolArgs['post_id']);
            } else {
                $toolResult = ['error' => 'Unknown tool'];
            }

            // Step 10 — Send tool result back to Groq for final answer
            $secondResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.groq.key'),
                'Content-Type'  => 'application/json',
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'    => 'llama-3.3-70b-versatile',
                'messages' => array_merge(
                    [['role' => 'system', 'content' => $systemPrompt]],
                    $history,
                    [
                        ['role' => 'assistant', 'content' => null, 'tool_calls' => $assistantMsg['tool_calls']],
                        ['role' => 'tool', 'tool_call_id' => $toolCall['id'], 'content' => json_encode($toolResult)],
                    ]
                ),
                'tools'       => $tools,
                'tool_choice' => 'auto',
            ]);

            $finalReply = $secondResponse->json('choices.0.message.content');

        } else {
            // No tool call — use direct response
            $finalReply = $assistantMsg['content'];
        }

        // Step 11 — Save assistant reply in DB (memory)
        Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'assistant',
            'content'         => $finalReply,
        ]);

        return response()->json([
            'message'         => $finalReply,
            'conversation_id' => $conversation->id,
        ]);
    }

    /**
     * @group Agent
     * Get the full conversation history for a post.
     */
    public function history(Request $request, Post $post): JsonResponse
    {
        $this->authorize('view', $post);

        $conversation = Conversation::where('user_id', $request->user()->id)
            ->where('post_id', $post->id)
            ->with('messages')
            ->first();

        if (!$conversation) {
            return response()->json(['messages' => []]);
        }

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages'        => $conversation->messages->map(fn($msg) => [
                'role'       => $msg->role,
                'content'    => $msg->content,
                'created_at' => $msg->created_at->toDateTimeString(),
            ]),
        ]);
    }
}