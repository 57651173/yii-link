<?php

declare(strict_types=1);

namespace Application\User\DTO;

/**
 * 创建用户的数据传输对象
 *
 * 用于在 Controller 和 Service 之间传递结构化数据，
 * 替代原始数组，提供类型安全和 IDE 自动补全支持。
 */
final class CreateUserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {
    }
}
