<?php

namespace App\Features\Ai\Contracts;

use App\Features\Ai\DTO\GeneratedPostItem;

interface AiClientInterface
{
    /**
     * Generate multiple structured article drafts based on a prompt.
     *
     * @return array<int, GeneratedPostItem>
     */
    public function generatePosts(string $prompt, int $count = 1): array;
}
