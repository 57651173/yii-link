<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * 业务异常
 *
 * 用于表示业务规则违反等可预期的业务错误，
 * 与系统异常（如数据库连接失败）区分开来。
 */
class BusinessException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 400,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
