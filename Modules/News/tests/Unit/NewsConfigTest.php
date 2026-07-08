<?php

namespace Modules\News\Tests\Unit;

use Tests\TestCase;

class NewsConfigTest extends TestCase
{
    public function test_default_topics_and_keywords_are_generic(): void
    {
        $config = $this->loadConfig();

        $this->assertSame([], $config['keywords']);
        $topics = $config['topics'];
        $this->assertIsArray($topics);
        $this->assertArrayHasKey('technology', $topics);
        $this->assertStringNotContainsStringIgnoringCase('moestuin', implode(' ', $topics));
        $this->assertNotContains('Bambu', $config['keywords']);
    }

    public function test_keywords_come_from_a_comma_separated_env(): void
    {
        $this->setEnv('NEWS_KEYWORDS', 'Alpha, Beta ,, Gamma');

        try {
            $config = $this->loadConfig();
        } finally {
            $this->clearEnv('NEWS_KEYWORDS');
        }

        $this->assertSame(['Alpha', 'Beta', 'Gamma'], $config['keywords']);
    }

    public function test_a_feeds_file_overrides_the_default_topics_and_feeds(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'news-feeds').'.json';
        file_put_contents($path, (string) json_encode([
            'topics' => ['home' => 'Home lab'],
            'feeds' => [['key' => 'mine', 'topic' => 'home', 'label' => 'Mine', 'url' => 'https://example.test/rss']],
        ]));
        $this->setEnv('NEWS_FEEDS_FILE', $path);

        try {
            $config = $this->loadConfig();
        } finally {
            $this->clearEnv('NEWS_FEEDS_FILE');
            @unlink($path);
        }

        $this->assertSame(['home' => 'Home lab'], $config['topics']);
        $feeds = $config['feeds'];
        $this->assertIsArray($feeds);
        $this->assertSame('mine', $feeds[0]['key']);
    }

    public function test_an_invalid_feeds_file_falls_back_to_the_defaults(): void
    {
        $this->setEnv('NEWS_FEEDS_FILE', '/does/not/exist.json');

        try {
            $config = $this->loadConfig();
        } finally {
            $this->clearEnv('NEWS_FEEDS_FILE');
        }

        $topics = $config['topics'];
        $this->assertIsArray($topics);
        $this->assertArrayHasKey('technology', $topics);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadConfig(): array
    {
        $config = require base_path('Modules/News/config/config.php');
        $this->assertIsArray($config);

        return $config;
    }

    /**
     * Set an env var across all channels env() reads, so the test behaves the
     * same whether or not putenv is disabled (config is cached in CI).
     */
    private function setEnv(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    private function clearEnv(string $key): void
    {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }
}
