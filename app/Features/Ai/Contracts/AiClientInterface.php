<?php

namespace App\Features\Ai\Contracts;

use App\Features\Ai\DTO\GeneratedPostItem;

interface AiClientInterface
{
    /**
     * Generate draft blog articles from a prompt.
     *
     * @return array<int, GeneratedPostItem>
     */
    public function generatePosts(string $prompt, int $count = 1): array;
}
