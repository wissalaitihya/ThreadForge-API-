# ThreadForge API

AI-powered content repurposing API for tech creators on X (Twitter). Submit raw content, have it processed by Groq AI (LLaMA 3.3-70B) into structured social media posts, and iterate via an AI chat agent with tool-calling capabilities.

## Features

- **Blueprint System** — Define reusable campaign rules (tone, hashtags, character limits)
- **AI Content Repurposing** — Async queue job sends content to Groq AI and generates structured posts (hook, body points, hashtags, readability score)
- **Ghostwriter Chat Agent** — Conversational AI agent that can reference blueprint rules and post history to help refine posts
- **Post Lifecycle** — Manage posts through draft → posted → archived stages
- **Sanctum Auth** — Token-based authentication for API access

## Requirements

- PHP ^8.3
- Composer
- MySQL (or SQLite for testing)
- Node.js & npm
- [Groq API key](https://console.groq.com) (free tier available)

## Installation

```bash
# Automated setup
composer run-script setup

# Or manually:
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install && npm run build
```

Configure your `.env` file:
```
DB_DATABASE=threadforge
GROQ_API_KEY=gsk_your_key_here
QUEUE_CONNECTION=database
```

## Running

```bash
composer run dev
```

This launches the Laravel dev server, queue worker, log viewer, and Vite dev server concurrently.

## API Endpoints

### Public
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/register` | Register a new user |
| `POST` | `/api/login` | Login and get a token |

### Protected (Bearer Token)
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/logout` | Revoke current token |
| `GET/POST` | `/api/blueprints` | List / Create blueprints |
| `GET/PUT/DELETE` | `/api/blueprints/{id}` | View / Update / Delete a blueprint |
| `POST` | `/api/content/repurpose` | Submit raw content for AI processing |
| `GET` | `/api/content` | List submitted content |
| `GET` | `/api/content/{id}` | View content with its generated post |
| `GET` | `/api/posts` | List generated posts |
| `GET` | `/api/posts/{id}` | View a post |
| `PATCH` | `/api/posts/{id}/status` | Update post status |
| `POST` | `/api/posts/{id}/chat` | Chat with Ghostwriter about a post |
| `GET` | `/api/posts/{id}/chat` | Get conversation history |

## Testing

```bash
composer test
```

Uses SQLite in-memory database. No external API calls — the Groq API is not called during tests.

## Architecture

```
User → API Routes → Controllers → FormRequests (validation)
                                    → Policies (authorization)
                                    → Queue Job → Groq API → Post (draft)
                                    → Chat Agent → Groq API + Tool Calling
```

Content repurposing runs asynchronously via Laravel's database queue. The Ghostwriter chat agent uses Groq's function-calling API with tools: `GetCampaignRulesTool` and `GetPostHistoryTool`.

## CI

GitHub Actions workflow runs tests on push to `main`/`develop` and PRs to `main`.
