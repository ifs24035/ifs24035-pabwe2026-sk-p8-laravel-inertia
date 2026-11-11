<?php

namespace Tests\Unit\Services;

use App\Features\Ai\Services\OpenAiPostGeneratorService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiPostGeneratorServiceTest extends TestCase
{
    protected function makeService(): OpenAiPostGeneratorService
    {
        return new OpenAiPostGeneratorService('https://api.openai.com/v1', 'mock-key', 'gpt-4o-mini');
    }

    public function test_empty_prompt_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Prompt tidak boleh kosong.');
        $this->makeService()->generatePosts('   ');
    }

    public function test_successful_generation(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'posts' => [
                                    [
                                        'title' => 'Artikel Satu',
                                        'category' => 'teknologi',
                                        'excerpt' => 'Ringkasan satu',
                                        'content' => '## Isi satu',
                                    ],
                                    [
                                        'title' => 'Artikel Dua',
                                        'category' => 'Kategori Tidak Dikenal',
                                        'excerpt' => '   ',
                                        'content' => 'Isi dua',
                                    ],
                                    [
                                        'title' => 'Artikel Tiga',
                                        'content' => 'Isi tiga',
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $results = $this->makeService()->generatePosts('Buatkan 3 artikel', 3);

        $this->assertCount(3, $results);
        $this->assertEquals('Artikel Satu', $results[0]->title);
        $this->assertEquals('Teknologi', $results[0]->category);
        $this->assertEquals('Ringkasan satu', $results[0]->excerpt);
        $this->assertEquals('## Isi satu', $results[0]->content);

        $this->assertEquals('Umum', $results[1]->category);
        $this->assertNull($results[1]->excerpt);

        $this->assertEquals('Umum', $results[2]->category);
        $this->assertNull($results[2]->excerpt);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer mock-key')
                && $request['model'] === 'gpt-4o-mini'
                && str_contains($request['messages'][0]['content'], 'exactly 3 complete');
        });
    }

    public function test_count_is_never_below_one(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => '{"posts":[{"title":"A","content":"B"}]}']]],
            ]),
        ]);

        $this->makeService()->generatePosts('Topik', 0);

        Http::assertSent(fn ($request) => str_contains($request['messages'][0]['content'], 'exactly 1 complete'));
    }

    public function test_api_failure_throws_exception_with_api_message(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'error' => ['message' => 'Invalid API Key provided'],
            ], 401),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API Error: Invalid API Key provided');
        $this->makeService()->generatePosts('Topik artikel');
    }

    public function test_api_failure_without_error_body_uses_status_code(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response('Server Error', 500),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API Error: Request failed with status code 500');
        $this->makeService()->generatePosts('Topik artikel');
    }

    public function test_parse_content_with_markdown_fences_and_plain_list(): void
    {
        $raw = "```json\n[{\"title\": \"Code Fence\", \"category\": \"Opini\", \"content\": \"Isi\"}]\n```";

        $results = $this->makeService()->parseContent($raw);

        $this->assertCount(1, $results);
        $this->assertEquals('Code Fence', $results[0]->title);
        $this->assertEquals('Opini', $results[0]->category);
    }

    public function test_parse_content_invalid_json(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AI mengembalikan format respon yang tidak valid.');
        $this->makeService()->parseContent('Not valid JSON');
    }

    public function test_parse_content_empty_posts(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tidak ada artikel yang berhasil di-generate dari AI.');
        $this->makeService()->parseContent('{"posts": []}');
    }

    public function test_parse_content_object_without_posts_key(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tidak ada artikel yang berhasil di-generate dari AI.');
        $this->makeService()->parseContent('{"foo": "bar"}');
    }

    public function test_parse_content_no_valid_items(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Format artikel yang dikembalikan AI tidak sesuai spesifikasi.');
        $this->makeService()->parseContent('{"posts": [{"bad_key": "val"}, {"title": "Tanpa konten"}, "string"]}');
    }

    public function test_constructor_handles_swapped_endpoint_and_key(): void
    {
        Http::fake([
            'https://ai.example.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => '{"posts":[{"title":"Judul","content":"Isi"}]}']]],
            ]),
        ]);

        // Key given as URL, endpoint given as secret
        $service = new OpenAiPostGeneratorService('sk-secret-key', 'https://ai.example.com/v1');
        $posts = $service->generatePosts('Topik');

        $this->assertCount(1, $posts);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer sk-secret-key'));
    }

    public function test_constructor_auto_prefixes_https(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => '{"posts":[{"title":"Judul","content":"Isi"}]}']]],
            ]),
        ]);

        $service = new OpenAiPostGeneratorService('api.openai.com/v1', 'mock-key');

        $this->assertCount(1, $service->generatePosts('Topik'));
    }

    public function test_constructor_reads_defaults_from_config(): void
    {
        config([
            'openai.endpoint' => 'https://config.example.com/v1/',
            'openai.key' => 'config-key',
            'openai.model' => 'config-model',
        ]);

        Http::fake([
            'https://config.example.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => '{"posts":[{"title":"Judul","content":"Isi"}]}']]],
            ]),
        ]);

        (new OpenAiPostGeneratorService())->generatePosts('Topik');

        Http::assertSent(fn ($request) => $request['model'] === 'config-model'
            && $request->hasHeader('Authorization', 'Bearer config-key'));
    }
}
