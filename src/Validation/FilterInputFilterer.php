<?php

declare(strict_types=1);

namespace Compose\Web\Validation;

/**
 * Simple wrapper around PHP's filter_var so it can plug into Processor chains.
 */
class FilterInputFilterer
{
    public function __construct(
        protected readonly int $filterType,
        protected readonly int|array $options = 0
    ) {
    }

    public function __invoke(mixed $value): mixed
    {
        return filter_var($value, $this->filterType, $this->options);
    }
}
