<?php

namespace App\Tests\Infrastructure\Repository;

use App\Infrastructure\Repository\Pagination;
use PHPUnit\Framework\TestCase;

class PaginationTest extends TestCase
{
    public function testNext(): void
    {
        $pagination = Pagination::fromOffsetAndLimit(0, 10);

        $this->assertEquals(
            Pagination::fromOffsetAndLimit(10, 10),
            $pagination->next(),
        );
    }

    public function testItShouldThrowWhenInvalidLimit(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid limit: 0'));
        Pagination::fromOffsetAndLimit(0, 0);
    }
}
