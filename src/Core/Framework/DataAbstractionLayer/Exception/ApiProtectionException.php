<?php declare(strict_types=1);

namespace Shopware\Core\Framework\DataAbstractionLayer\Exception;

use Shopware\Core\Framework\ShopwareHttpException;

/**
 * @package core
 */
class ApiProtectionException extends ShopwareHttpException
{
    public function __construct(string $accessor)
    {
        parent::__construct(
            'Accessor {{ accessor }} is not allowed in this api scope',
            ['accessor' => $accessor]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__ACCESSOR_NOT_ALLOWED';
    }
}
