<?php
declare(strict_types=1);

/** Exception carrying an HTTP status code (403, 404, 405, 419, 422...). */
class HttpException extends RuntimeException
{
    public function __construct(int $status, string $message = '')
    {
        parent::__construct($message, $status);
    }

    public function status(): int
    {
        return $this->getCode();
    }
}
