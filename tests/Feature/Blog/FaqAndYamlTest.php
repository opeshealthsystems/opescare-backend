<?php

namespace Tests\Feature\Blog;

use App\Services\Blog\MiniYaml;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FaqAndYamlTest extends TestCase
{
    use RefreshDatabase;

    public function test_mini_yaml_reads_the_contract_subset(): void
    {
        $data = MiniYaml::parse(<<<'YAML'
        title: "Quoted: with colon"
        plain: hello world # comment
        single: 'it''s'
        tags: [a, "b, c", 3]
        list:
          - one
          - two
        faq:
          - q: "Q1"
            a: |
              line one
              line two
        empty:
        YAML);

        $this->assertSame('Quoted: with colon', $data['title']);
        $this->assertSame('hello world', $data['plain']);
        $this->assertSame("it's", $data['single']);
        $this->assertSame(['a', 'b, c', 3], $data['tags']);
        $this->assertSame(['one', 'two'], $data['list']);
        $this->assertSame("line one\nline two\n", $data['faq'][0]['a']);
        $this->assertNull($data['empty']);
    }

    public function test_mini_yaml_rejects_unsupported_constructs(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MiniYaml::parse("a: &anchor x\nb: *anchor\n");
    }

    public function test_faq_renders_yaml_with_faqpage_schema(): void
    {
        config(['blog.faq_path' => __DIR__ . '/fixtures/faq']);
        @mkdir(__DIR__ . '/fixtures/faq', 0775, true);
        file_put_contents(__DIR__ . '/fixtures/faq/en.yaml', "- category: \"Blood\"\n  q: \"Can I find blood?\"\n  a: \"Yes, reported by blood banks.\"\n  link: /network/blood-finder\n");
        Cache::flush();

        $html = $this->get('/faq')->assertOk()
            ->assertSee('Can I find blood?')
            ->assertSee('href="' . url('/network/blood-finder') . '"', false)
            ->getContent();

        preg_match_all('#<script type="application/ld\+json">\s*(.*?)\s*</script>#s', $html, $m);
        $faq = collect($m[1])->map(fn ($b) => json_decode($b, true))->firstWhere('@type', 'FAQPage');
        $this->assertNotNull($faq);
        $this->assertSame('https://schema.org', $faq['@context']);
        $this->assertSame('Can I find blood?', $faq['mainEntity'][0]['name']);

        unlink(__DIR__ . '/fixtures/faq/en.yaml');
    }
}
