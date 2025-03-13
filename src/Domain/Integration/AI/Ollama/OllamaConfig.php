<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Ollama;

use App\Infrastructure\ValueObject\String\Url;

final readonly class OllamaConfig
{
    private function __construct(
        private ?string $model,
        private ?Url $url,
    ) {
    }

    public static function create(?string $model, ?Url $url): self
    {
        return new self(
            model: $model,
            url: $url
        );
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getUrl(): ?Url
    {
        return $this->url;
    }

    public function toLLPhant(): \LLPhant\OllamaConfig
    {
        $config = new \LLPhant\OllamaConfig();
        $config->model = $this->getModel();
        $config->url = (string) $this->getUrl();
        $config->modelOptions = [
            'options' => [
                // Increasing the temperature will make the model answer more creatively. (Default: 0.8)
                'temperature' => 0,
            ],
        ];

        return $config;
    }
}
