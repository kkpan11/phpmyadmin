<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\ExportController;
use PhpMyAdmin\Controllers\Table\ExportRowsController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\UrlParams;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ExportRowsController::class)]
class ExportRowsControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        Current::$server = 2;
        Export::$singleTable = false;
        Current::$whereClause = null;
        $_POST = [];
    }

    public function testExportRowsController(): void
    {
        $_POST['rows_to_delete'] = 'row';
        UrlParams::$goto = '';

        $controller = $this->createMock(ExportController::class);
        $controller->expects(self::once())->method('__invoke')
            ->willReturn(ResponseFactory::create()->createResponse());

        (new ExportRowsController(
            new ResponseRenderer(),
            $controller,
        ))(self::createStub(ServerRequest::class));

        self::assertTrue(Export::$singleTable);
        self::assertSame([], Current::$whereClause);
    }

    public function testWithoutRowsToDelete(): void
    {
        UrlParams::$goto = 'goto';

        $controller = $this->createMock(ExportController::class);
        $controller->expects(self::never())->method('__invoke');

        $response = new ResponseRenderer();
        (new ExportRowsController($response, $controller))(self::createStub(ServerRequest::class));

        self::assertSame(['message' => 'No row selected.'], $response->getJSONResult());
        self::assertFalse($response->hasSuccessState());
        self::assertFalse(Export::$singleTable);
        self::assertNull(Current::$whereClause);
    }

    public function testWithRowsToDelete(): void
    {
        UrlParams::$goto = 'goto';
        $_POST['rows_to_delete'] = ['key1' => 'row1', 'key2' => 'row2'];

        $controller = $this->createMock(ExportController::class);
        $controller->expects(self::once())->method('__invoke')
            ->willReturn(ResponseFactory::create()->createResponse());

        (new ExportRowsController(
            new ResponseRenderer(),
            $controller,
        ))(self::createStub(ServerRequest::class));

        self::assertTrue(Export::$singleTable);
        self::assertSame(['row1', 'row2'], Current::$whereClause);
    }
}
