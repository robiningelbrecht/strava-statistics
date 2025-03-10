<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Ollama;

final readonly class Ollama
{
    public function __construct(
        private OllamaConfig $config,
    ) {
    }

    public function isEnabled(): bool
    {
        return null !== $this->config->getUrl() && null !== $this->config->getModel();
    }

    public function getConfig(): OllamaConfig
    {
        return $this->config;
    }
}
