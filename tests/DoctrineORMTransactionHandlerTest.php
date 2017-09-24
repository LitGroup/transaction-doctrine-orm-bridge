<?php
/**
 * Copyright 2017 LitGroup, LLC
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

declare(strict_types=1);

namespace Test\LitGroup\Transaction\Bridge\DoctrineORM;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use LitGroup\Transaction\Bridge\DoctrineORM\DoctrineORMTransactionHandler;
use LitGroup\Transaction\TransactionHandler;
use PHPUnit\Framework\TestCase;

class DoctrineORMTransactionHandlerTest extends TestCase
{
    private const CALL_EM_FLUSH = 'EntityManager::flush()';
    private const CALL_EM_CLOSE = 'EntityManager::close()';

    private const CALL_CONN_BEGIN = 'Connection::beginTransaction()';
    private const CALL_CONN_COMMIT = 'Connection::commit()';
    private const CALL_CONN_ROLLBACK = 'Connection::rollBack()';

     /** @var DoctrineORMTransactionHandler */
    private $handler;

    /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $entityManager;

    /** @var Connection|\PHPUnit_Framework_MockObject_MockObject */
    private $connection;

    /** @var string[] */
    private $calls;

    protected function setUp()
    {
        parent::setUp();

        $this->calls = [];

        $this->connection = $this->createMock(Connection::class);
        $this->connection
            ->method('beginTransaction')
            ->willReturnCallback(function () {
                $this->calls[] = self::CALL_CONN_BEGIN;
            });
        $this->connection
            ->method('commit')
            ->willReturnCallback(function () {
                $this->calls[] = self::CALL_CONN_COMMIT;
            });
        $this->connection
            ->method('rollBack')
            ->willReturnCallback(function () {
                $this->calls[] = self::CALL_CONN_ROLLBACK;
            });

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager
            ->method('getConnection')
            ->willReturn($this->connection);
        $this->entityManager
            ->method('beginTransaction')
            ->willReturnCallback(function () {
                $this->connection->beginTransaction();
            });
        $this->entityManager
            ->method('commit')
            ->willReturnCallback(function () {
                $this->connection->commit();
            });
        $this->entityManager
            ->method('rollback')
            ->willReturnCallback(function () {
                $this->connection->rollBack();
            });
        $this->entityManager
            ->method('flush')
            ->willReturnCallback(function () {
                $this->calls[] = self::CALL_EM_FLUSH;
            });
        $this->entityManager
            ->method('close')
            ->willReturnCallback(function () {
                $this->calls[] = self::CALL_EM_CLOSE;
            });

        $this->handler = new DoctrineORMTransactionHandler($this->entityManager);
    }

    function testInstance(): void
    {
        self::assertInstanceOf(TransactionHandler::class, $this->handler);
    }

    function testBegin(): void
    {
        $this->handler->begin();
        self::assertSame(
            [
                self::CALL_CONN_BEGIN
            ],
            $this->calls
        );
    }

    function testCommit(): void
    {

        $this->handler->commit();
        self::assertSame(
            [
                self::CALL_EM_FLUSH,
                self::CALL_CONN_COMMIT
            ],
            $this->calls
        );
    }

    function testRollback(): void
    {
        $this->handler->rollBack();
        self::assertSame(
            [
                self::CALL_EM_CLOSE,
                self::CALL_CONN_ROLLBACK
            ],
            $this->calls
        );
    }
}
