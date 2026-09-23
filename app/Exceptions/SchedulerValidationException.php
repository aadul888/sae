<?php

namespace App\Exceptions;

use Exception;

class SchedulerValidationException extends Exception
{
    protected array $unmappedSubjects;
    protected array $unfilledRombels;

    public function __construct(string $message, array $unmappedSubjects = [], array $unfilledRombels = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->unmappedSubjects = $unmappedSubjects;
        $this->unfilledRombels = $unfilledRombels;
    }

    public function getUnmappedSubjects(): array
    {
        return $this->unmappedSubjects;
    }

    public function getUnfilledRombels(): array
    {
        return $this->unfilledRombels;
    }
}
