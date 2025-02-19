<?php declare(strict_types=1);

namespace Shopware\Tests\Unit\Core\Framework\Store\Exception;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Store\Exception\StoreLicenseDomainMissingException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package merchant-services
 *
 * @internal
 * @covers \Shopware\Core\Framework\Store\Exception\StoreLicenseDomainMissingException
 */
class StoreLicenseDomainMissingExceptionTest extends TestCase
{
    public function testGetErrorCode(): void
    {
        static::assertSame(
            'FRAMEWORK__STORE_LICENSE_DOMAIN_IS_MISSING',
            (new StoreLicenseDomainMissingException())->getErrorCode()
        );
    }

    public function testGetStatusCode(): void
    {
        static::assertSame(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            (new StoreLicenseDomainMissingException())->getStatusCode()
        );
    }

    public function testGetMessage(): void
    {
        static::assertSame(
            'Store license domain is missing',
            (new StoreLicenseDomainMissingException())->getMessage()
        );
    }
}
