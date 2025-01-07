<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject;

final readonly class DataTableRow implements \JsonSerializable
{
    private function __construct(
        private string $markup,
        /** @var string[] */
        private array $searchables,
        /** @var array<string, string|bool> */
        private array $filterables,
        /** @var array<string, string|int|float> */
        private array $sortValues,
    ) {
    }

    /**
     * @param string[]                        $searchables
     * @param array<string, string|bool>      $filterables
     * @param array<string, string|int|float> $sortValues
     */
    public static function create(
        string $markup,
        array $searchables,
        array $filterables,
        array $sortValues,
    ): self {
        return new self(
            markup: $markup,
            searchables: $searchables,
            filterables: $filterables,
            sortValues: $sortValues
        );
    }

    public function getMarkup(): string
    {
        return $this->markup;
    }

    /**
     * @return string[]
     */
    public function getSearchables(): array
    {
        return $this->searchables;
    }

    /**
     * @return array<string, string|int|float>
     */
    public function getSortValues(): array
    {
        return $this->sortValues;
    }

    /**
     * @return array<string, string|bool>
     */
    public function getFilterables(): array
    {
        return $this->filterables;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'active' => true,
            'searchables' => implode(' ', $this->getSearchables()),
            'filterables' => $this->getFilterables(),
            'sort' => $this->getSortValues(),
            'markup' => $this->getMarkup(),
        ];
    }
}
