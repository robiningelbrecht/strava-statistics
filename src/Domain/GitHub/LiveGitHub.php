<?php

namespace App\Domain\GitHub;

use App\Infrastructure\Serialization\Json;
use GuzzleHttp\Client;

final readonly class LiveGitHub implements GitHub
{
    public function __construct(
        private Client $client,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     * @return array<mixed>
     */
    private function request(
        string $path,
        string $method = 'GET',
        array $options = []): array
    {
        $options = array_merge([
            'base_uri' => 'https://api.github.com/',
        ], $options);
        $response = $this->client->request($method, $path, $options);

        return Json::decode($response->getBody()->getContents());
    }

    public function getRepoLatestRelease(
        string $fullRepoName,
    ): string {
        return $this->request(sprintf('repos/%s/releases/latest', $fullRepoName))['name'];
    }
}
