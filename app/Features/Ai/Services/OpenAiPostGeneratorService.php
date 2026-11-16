<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Post\Enums\PostCategory;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiPostGeneratorService implements AiClientInterface
{
    protected string $endpoint;

    protected string $apiKey;

    protected string $model;

    public function __construct(
        ?string $endpoint = null,
        ?string $apiKey = null,
        ?string $model = null,
    ) {
        $resolvedEndpoint = (string) ($endpoint ?? config('openai.endpoint', 'https://api.openai.com/v1'));
        $resolvedKey = (string) ($apiKey ?? config('openai.key', ''));

        // Tolerate OPENAI_ENDPOINT and OPENAI_KEY being swapped in .env
        if ($this->isUrl($resolvedKey) && ! $this->isUrl($resolvedEndpoint)) {
            [$resolvedEndpoint, $resolvedKey] = [$resolvedKey, $resolvedEndpoint];
        }

        if ($resolvedEndpoint !== '' && ! $this->isUrl($resolvedEndpoint)) {
            $resolvedEndpoint = 'https://'.$resolvedEndpoint;
        }

        $this->endpoint = rtrim($resolvedEndpoint, '/');
        $this->apiKey = trim($resolvedKey);
        $this->model = $model ?? (string) config('openai.model', 'gpt-4o-mini');
    }

    /**
     * @return array<int, GeneratedPostItem>
     *
     * @throws RuntimeException
     */
    public function generatePosts(string $prompt, int $count = 1): array
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            throw new RuntimeException('Prompt tidak boleh kosong.');
        }

        if ($this->apiKey === '') {
            throw new RuntimeException('API key AI belum dikonfigurasi. Isi OPENAI_KEY di .env, lalu jalankan php artisan config:clear.');
        }

        $count = max(1, min(5, $count));
        $categories = implode(', ', PostCategory::values());

        $systemPrompt = <<<SYS
You are an experienced Indonesian blog writer. Write exactly {$count} original blog article draft(s) in Bahasa Indonesia based on the user's topic.
Each article must have an engaging title, a 1-2 sentence excerpt, and Markdown content of 300-500 words with at least two "##" subheadings.
Pick the category from this list only: {$categories}.

Respond strictly with a valid JSON object matching this schema and nothing else:
{
  "posts": [
    { "title": "string", "category": "one of the list", "excerpt": "string", "content": "markdown string" }
  ]
}
SYS;

        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->timeout(120)->post("{$this->endpoint}/chat/completions", [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
            ]);

        if (! $response->successful()) {
            $errorMessage = $response->json('error.message') ?? "Request failed with status code {$response->status()}";
            throw new RuntimeException("OpenAI API Error: {$errorMessage}");
        }

        return $this->parseContent((string) $response->json('choices.0.message.content'));
    }

    /**
     * Turn the raw AI reply into clean DTOs. Code fences are removed, items without
     * a title or content are dropped, and unknown categories become "Umum".
     *
     * @return array<int, GeneratedPostItem>
     */
    public function parseContent(string $rawContent): array
    {
        $clean = trim((string) preg_replace('/<think>.*?<\/think>/is', '', $rawContent));

        // Ambil JSON di dalam ```json ... ```, di mana pun posisinya.
        if (preg_match('/```(?:json)?\s*(.*?)```/is', $clean, $fence)) {
            $clean = trim($fence[1]);
        }

        // Buang kalimat di sekitar JSON: ambil dari { atau [ pertama sampai } atau ] terakhir.
        $start = strcspn($clean, '{[');
        $end = max((int) strrpos($clean, '}'), (int) strrpos($clean, ']'));
        if ($start < strlen($clean) && $end > $start) {
            $clean = substr($clean, $start, $end - $start + 1);
        }

        $decoded = json_decode($clean, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('AI mengembalikan format respon yang tidak valid.');
        }

        $items = isset($decoded['posts']) && is_array($decoded['posts'])
            ? $decoded['posts']
            : (array_is_list($decoded) ? $decoded : []);

        if (empty($items)) {
            throw new RuntimeException('Tidak ada artikel yang berhasil dibuat oleh AI.');
        }

        $results = [];
        foreach ($items as $item) {
            if (! is_array($item) || trim((string) ($item['title'] ?? '')) === '' || trim((string) ($item['content'] ?? '')) === '') {
                continue;
            }

            $excerpt = trim((string) ($item['excerpt'] ?? ''));

            $results[] = new GeneratedPostItem(
                title: mb_substr(trim((string) $item['title']), 0, 255),
                category: PostCategory::fromLoose($item['category'] ?? null)->value,
                excerpt: $excerpt === '' ? null : $excerpt,
                content: trim((string) $item['content']),
            );
        }

        if (empty($results)) {
            throw new RuntimeException('Format artikel yang dikembalikan AI tidak sesuai spesifikasi.');
        }

        return $results;
    }

    protected function isUrl(string $value): bool
    {
        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
    }
}
