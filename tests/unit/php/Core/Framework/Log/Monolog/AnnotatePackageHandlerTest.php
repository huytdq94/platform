<?php declare(strict_types=1);

namespace Shopware\Tests\Unit\Core\Framework\Log\Monolog;

use Monolog\Handler\AbstractHandler;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Monolog\AnnotatePackageHandler;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \Shopware\Core\Framework\Log\Monolog\AnnotatePackageHandler
 *
 * @internal
 *
 * @phpstan-import-type Record from \Monolog\Logger
 *
 * @package core
 */
#[Package('cause')]
class AnnotatePackageHandlerTest extends TestCase
{
    public function testOnlyController(): void
    {
        $requestStack = new RequestStack();
        $inner = $this->createMock(AbstractHandler::class);
        $handler = new AnnotatePackageHandler($inner, $requestStack);

        $request = new Request();
        $request->attributes->set('_controller', TestController::class . '::load');
        $requestStack->push($request);

        /** @var Record $record */
        $record = ['context' => []];
        $expected = $record;
        $expected['context'][Package::PACKAGE_TRACE_ATTRIBUTE_KEY] = [
            'entrypoint' => 'controller',
        ];

        $inner->expects(static::once())
            ->method('handle')
            ->with($expected);
        $handler->handle($record);
    }

    public function testExceptionInController(): void
    {
        $requestStack = new RequestStack();
        $inner = $this->createMock(AbstractHandler::class);
        $handler = new AnnotatePackageHandler($inner, $requestStack);

        $request = new Request();
        $request->attributes->set('_controller', TestController::class . '::load');
        $requestStack->push($request);

        $exception = null;

        try {
            throw new TestException('test');
        } catch (\Throwable $e) {
            $exception = $e;
        }

        /** @var Record $record */
        $record = ['context' => [
            'exception' => $exception,
        ]];
        $expected = $record;
        $expected['context'][Package::PACKAGE_TRACE_ATTRIBUTE_KEY] = [
            'entrypoint' => 'controller',
            'exception' => 'exception',
            'causingClass' => 'cause',
        ];

        $inner->expects(static::once())
            ->method('handle')
            ->with($expected);
        $handler->handle($record);
    }

    public function testNoPackageAttributes(): void
    {
        $requestStack = new RequestStack();
        $inner = $this->createMock(AbstractHandler::class);
        $handler = new AnnotatePackageHandler($inner, $requestStack);

        $request = new Request();
        $request->attributes->set('_controller', TestControllerNoPackage::class . '::load');
        $requestStack->push($request);

        $exception = null;

        try {
            throw new TestExceptionNoPackage('test');
        } catch (\Throwable $e) {
            $exception = $e;
        }

        /** @var Record $record */
        $record = ['context' => [
            'exception' => $exception,
        ]];
        $expected = $record;
        $expected['context'][Package::PACKAGE_TRACE_ATTRIBUTE_KEY] = [
            'causingClass' => 'cause',
        ];

        $inner->expects(static::once())
            ->method('handle')
            ->with($expected);
        $handler->handle($record);
    }

    public function testAnnotateCommand(): void
    {
        $exception = null;

        try {
            $command = new TestCommand();
            $command->execute($this->createMock(InputInterface::class), $this->createMock(OutputInterface::class));
        } catch (\Throwable $e) {
            $exception = $e;
        }

        $inner = $this->createMock(AbstractHandler::class);
        $handler = new AnnotatePackageHandler($inner, $this->createMock(RequestStack::class));

        /** @var Record $record */
        $record = [
            'context' => [
                'exception' => $exception,
                'dataIsPassedThru' => true,
            ],
            'dataIsPassedThru' => true,
        ];

        $expected = $record;
        $expected['context'][Package::PACKAGE_TRACE_ATTRIBUTE_KEY] = [
            'entrypoint' => 'command',
            'exception' => 'exception',
            'causingClass' => 'command',
        ];

        $inner->expects(static::once())
            ->method('handle')
            ->with($expected);
        $handler->handle($record);
    }
}

/**
 * @internal
 */
#[Package('controller')]
class TestController
{
    public function load(Request $request): Response
    {
        return new Response();
    }
}

/**
 * @internal
 */
class TestControllerNoPackage
{
    public function load(Request $request): Response
    {
        return new Response();
    }
}

/**
 * @internal
 */
#[Package('exception')]
class TestException extends ShopwareHttpException
{
    public function getErrorCode(): string
    {
        return '500';
    }
}

/**
 * @internal
 */
class TestExceptionNoPackage extends ShopwareHttpException
{
    public function getErrorCode(): string
    {
        return '500';
    }
}

/**
 * @internal
 */
#[Package('command')]
class TestCommand extends Command
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $testCause = new TestCause();
        $testCause->throw(new TestException('test'));

        return 0;
    }
}

/**
 * @internal
 */
#[Package('cause')]
class TestCause extends Command
{
    public function throw(\Throwable $exception): int
    {
        throw $exception;
    }
}
