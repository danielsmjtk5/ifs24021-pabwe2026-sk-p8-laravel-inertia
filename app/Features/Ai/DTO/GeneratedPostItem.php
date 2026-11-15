<?php

namespace App\Features\Ai\DTO;

/**
 * Data carrier for one article drafted by the AI.
 */
class GeneratedPostItem
{
    public function __construct(
        public string $title,
        public string $category = 'Umum',
        public ?string $excerpt = null,
        public string $content = '',
    ) {}

    /**
     * @return array{title: string, category: string, excerpt: ?string, content: string}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'category' => $this->category,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
        ];
    }
}
