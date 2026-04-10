<?php

declare(strict_types=1);

namespace Application\User\DTO;

/**
 * 更新用户的数据传输对象
 */
final class UpdateUserDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $password = null,
    ) {
    }
}
