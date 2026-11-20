<?php
declare(strict_types=1);

/** Erreur métier ou HTTP : code adapté + message sans détail interne. */
final class HttpException extends RuntimeException
{
    public function __construct(public readonly int $codeHttp, string $message)
    {
        parent::__construct($message);
    }
}
