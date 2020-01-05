<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Exception;

final class ExpressionLimitCantBeNegative extends DomainException
{
    public function __construct(int $limit)
    {
        parent::__construct((string) $limit);
    }
}
