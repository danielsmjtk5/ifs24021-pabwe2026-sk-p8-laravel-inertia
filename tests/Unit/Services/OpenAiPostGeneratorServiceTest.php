<?php

namespace Tests\Unit\Services;

use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Ai\Services\OpenAiPostGeneratorService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiPostGeneratorServiceTest extends TestCase
{
    private function service(): OpenAiPostGeneratorService
    {
        return new OpenAiPostGeneratorService('https://ai.test/v1', 'secret', 'test-model');
    }

    public function test_empty_prompt_throws(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service()->generatePosts('   ');
    }

    public function test_successful_generation_sends_bearer_and_clamps_count(): void
    {
        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode(['posts' => [
                        ['title' => 'Artikel AI', 'category' => 'Teknologi', 'excerpt' => 'Ringkas', 'content' => '## Isi'],
                    ]])],
                ]],
            ]),
        ]);

        $items = $this->service()->generatePosts('Tulis tentang Laravel', 99);

        $this->assertCount(1, $items);
        $this->assertInstanceOf(GeneratedPostItem::class, $items[0]);
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://ai.test/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer secret')
                && $request['model'] === 'test-model'
                && str_contains($request['messages'][0]['content'], 'exactly 5');
        });
    }

    public function test_api_failure_throws_with_message(): void
    {
        Http::fake(['ai.test/*' => Http::response(['error' => ['message' => 'Invalid API key provided.']], 401)]);

        $this->expectExceptionMessage('OpenAI API Error: Invalid API key provided.');
        $this->service()->generatePosts('Topik');
    }

    public function test_api_failure_without_message_uses_status_code(): void
    {
        Http::fake(['ai.test/*' => Http::response('oops', 500)]);

        $this->expectExceptionMessage('Request failed with status code 500');
        $this->service()->generatePosts('Topik');
    }

    public function test_parse_content_cleans_fences_and_bad_items(): void
    {
        $raw = "```json\n".json_encode([
            ['title' => '  Judul  ', 'category' => 'olahraga', 'excerpt' => '', 'content' => ' Isi '],
            ['title' => '', 'content' => 'x'],
            ['title' => 'Tanpa isi', 'content' => ''],
            'bukan-array',
        ])."\n```";

        $items = $this->service()->parseContent($raw);

        $this->assertCount(1, $items);
        $this->assertSame('Judul', $items[0]->title);
        $this->assertSame('Umum', $items[0]->category);
        $this->assertNull($items[0]->excerpt);
        $this->assertSame('Isi', $items[0]->content);
    }

    public function test_parse_content_invalid_json(): void
    {
        $this->expectExceptionMessage('format respon yang tidak valid');
        $this->service()->parseContent('bukan json');
    }

    public function test_parse_content_empty_list(): void
    {
        $this->expectExceptionMessage('Tidak ada artikel');
        $this->service()->parseContent('{"other": 1}');
    }

    public function test_parse_content_no_valid_items(): void
    {
        $this->expectExceptionMessage('tidak sesuai spesifikasi');
        $this->service()->parseContent('{"posts": [{"title": ""}]}');
    }

    public function test_constructor_swaps_endpoint_and_key_and_adds_https(): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => '[{"title":"A","content":"B"}]']]]])]);

        (new OpenAiPostGeneratorService('my-key', 'https://swap.test/v1/', 'm'))->generatePosts('x');
        Http::assertSent(fn (Request $r) => $r->url() === 'https://swap.test/v1/chat/completions' && $r->hasHeader('Authorization', 'Bearer my-key'));

        (new OpenAiPostGeneratorService('noscheme.test/v1', 'k', 'm'))->generatePosts('x');
        Http::assertSent(fn (Request $r) => $r->url() === 'https://noscheme.test/v1/chat/completions');
    }

    public function test_constructor_reads_config_defaults(): void
    {
        config(['openai.endpoint' => 'https://cfg.test/v1', 'openai.key' => 'cfg-key', 'openai.model' => 'cfg-model']);
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => '[{"title":"A","content":"B"}]']]]])]);

        (new OpenAiPostGeneratorService())->generatePosts('x');

        Http::assertSent(fn (Request $r) => $r->url() === 'https://cfg.test/v1/chat/completions' && $r['model'] === 'cfg-model');
    }
}
