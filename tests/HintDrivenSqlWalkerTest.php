<?php declare(strict_types = 1);

namespace ShipMonk\Doctrine\Walker;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Query;
use Generator;
use PHPUnit\Framework\TestCase;
use ShipMonk\Doctrine\Walker\Handlers\CommentWholeSqlHintHandler;
use ShipMonk\Doctrine\Walker\Handlers\LowercaseSelectHintHandler;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use function method_exists;
use const PHP_VERSION_ID;

class HintDrivenSqlWalkerTest extends TestCase
{

    /**
     * @param callable(EntityManager):Query $queryCallback
     *
     * @dataProvider walksProvider
     */
    public function testWalker(
        callable $queryCallback,
        string $handlerClass,
        mixed $hintValue,
        string $expectedSql,
    ): void
    {
        $entityManagerMock = $this->createEntityManagerMock();

        $query = $queryCallback($entityManagerMock);
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, HintDrivenSqlWalker::class);
        $query->setHint($handlerClass, $hintValue);
        $producedSql = $query->getSQL();

        self::assertSame($expectedSql, $producedSql);
    }

    public function testPagination(): void
    {
        $pageSize = 10;
        $entityManagerMock = $this->createEntityManagerMock();

        self::assertNotNull(
            $entityManagerMock->getConfiguration()->getQueryCache(),
            'QueryCache needed. The purpose of this test is to ensure that we do not break the pagination by using a cache',
        );

        $expectedSqls = [
            'select d0_.id AS id_0 FROM dummy_entity d0_ LIMIT 10',
            'select d0_.id AS id_0 FROM dummy_entity d0_ LIMIT 10 OFFSET 10',
            'select d0_.id AS id_0 FROM dummy_entity d0_ LIMIT 10 OFFSET 20',
        ];

        foreach ([0, 1, 2] as $page) {
            $query = $entityManagerMock->createQueryBuilder()
                ->select('w')
                ->from(DummyEntity::class, 'w')
                ->getQuery()
                ->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, HintDrivenSqlWalker::class)
                ->setHint(LowercaseSelectHintHandler::class, null)
                ->setFirstResult($page * $pageSize)
                ->setMaxResults($pageSize);
            $producedSql = $query->getSQL();

            self::assertSame($expectedSqls[$page], $producedSql, 'Page ' . $page . ' failed:');
        }
    }

    /**
     * @return Generator<string, array{callable(EntityManager):Query, class-string<HintHandler>, mixed, string}>
     */
    public static function walksProvider(): iterable
    {
        $selectQueryCallback = static function (EntityManager $entityManager): Query {
            return $entityManager->createQueryBuilder()
                ->select('w')
                ->from(DummyEntity::class, 'w')
                ->getQuery();
        };

        $selectWithLimitQueryCallback = static function (EntityManager $entityManager): Query {
            return $entityManager->createQueryBuilder()
                ->select('w')
                ->from(DummyEntity::class, 'w')
                ->setMaxResults(1)
                ->getQuery();
        };

        $updateQueryCallback = static function (EntityManager $entityManager): Query {
            return $entityManager->createQueryBuilder()
                ->update(DummyEntity::class, 'w')
                ->set('w.id', 1)
                ->getQuery();
        };

        $deleteQueryCallback = static function (EntityManager $entityManager): Query {
            return $entityManager->createQueryBuilder()
                ->delete(DummyEntity::class, 'w')
                ->getQuery();
        };

        yield 'Lowercase select' => [
            $selectQueryCallback,
            LowercaseSelectHintHandler::class,
            null,
            'select d0_.id AS id_0 FROM dummy_entity d0_',
        ];

        yield 'Lowercase select with LIMIT' => [
            $selectWithLimitQueryCallback,
            LowercaseSelectHintHandler::class,
            null,
            'select d0_.id AS id_0 FROM dummy_entity d0_ LIMIT 1',
        ];

        yield 'Comment whole sql - select' => [
            $selectQueryCallback,
            CommentWholeSqlHintHandler::class,
            'custom comment',
            'SELECT d0_.id AS id_0 FROM dummy_entity d0_ -- custom comment',
        ];

        yield 'Comment whole sql - select with LIMIT' => [
            $selectWithLimitQueryCallback,
            CommentWholeSqlHintHandler::class,
            'custom comment',
            'SELECT d0_.id AS id_0 FROM dummy_entity d0_ -- custom comment LIMIT 1', // see readme limitations
        ];

        yield 'Comment whole sql - update' => [
            $updateQueryCallback,
            CommentWholeSqlHintHandler::class,
            'custom comment',
            'UPDATE dummy_entity SET id = 1 -- custom comment',
        ];

        yield 'Comment whole sql - delete' => [
            $deleteQueryCallback,
            CommentWholeSqlHintHandler::class,
            'custom comment',
            'DELETE FROM dummy_entity -- custom comment',
        ];
    }

    private function createEntityManagerMock(): EntityManager
    {
        $config = new Configuration();

        if (PHP_VERSION_ID >= 8_04_00 && method_exists($config, 'enableNativeLazyObjects')) {
            $config->enableNativeLazyObjects(true);

        } else {
            $config->setProxyNamespace('Tmp\Doctrine\Tests\Proxies');
            $config->setProxyDir('/tmp/doctrine');
            $config->setAutoGenerateProxyClasses(false);
        }

        $config->setQueryCache(new ArrayAdapter());
        $config->setSecondLevelCacheEnabled(false);
        $config->setMetadataDriverImpl(new AttributeDriver([__DIR__]));
        $config->setNamingStrategy(new UnderscoreNamingStrategy());

        $eventManager = $this->createMock(EventManager::class);
        $connectionMock = $this->createMock(Connection::class);

        $connectionMock->method('getDatabasePlatform')
            ->willReturn(new MySQL80Platform());

        return new EntityManager(
            $connectionMock,
            $config,
            $eventManager,
        );
    }

}
