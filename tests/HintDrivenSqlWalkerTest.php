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
use function sprintf;

class HintDrivenSqlWalkerTest extends TestCase
{

    /**
     * @param callable(EntityManager):Query $queryCallback
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
        $selectDql = sprintf('SELECT w FROM %s w', DummyEntity::class);

        yield 'Lowercase select' => [
            static fn(EntityManager $entityManager): Query => $entityManager->createQuery($selectDql),
            LowercaseSelectHintHandler::class,
            null,
            'select d0_.id AS id_0 FROM dummy_entity d0_',
        ];

        yield 'Comment whole sql - select' => [
            static fn(EntityManager $entityManager): Query => $entityManager->createQuery($selectDql),
            CommentWholeSqlHintHandler::class,
            'custom comment',
            'SELECT d0_.id AS id_0 FROM dummy_entity d0_ -- custom comment',
        ];

        yield 'Comment whole sql - update' => [
            static fn(EntityManager $entityManager): Query => $entityManager->createQuery(sprintf('UPDATE %s w SET w.id = 1', DummyEntity::class)),
            CommentWholeSqlHintHandler::class,
            'custom comment',
            'UPDATE dummy_entity SET id = 1 -- custom comment',
        ];

        yield 'Comment whole sql - delete' => [
            static fn(EntityManager $entityManager): Query => $entityManager->createQuery(sprintf('DELETE FROM %s w', DummyEntity::class)),
            CommentWholeSqlHintHandler::class,
            'custom comment',
            'DELETE FROM dummy_entity -- custom comment',
        ];

        yield 'Comment whole sql with LIMIT' => [
            static function (EntityManager $entityManager): Query {
                return $entityManager->createQueryBuilder()
                    ->select('w')
                    ->from(DummyEntity::class, 'w')
                    ->setMaxResults(1)
                    ->getQuery();
            },
            CommentWholeSqlHintHandler::class,
            'custom comment',
            'SELECT d0_.id AS id_0 FROM dummy_entity d0_ -- custom comment LIMIT 1', // see readme limitations
        ];
    }

    private function createEntityManagerMock(): EntityManager
    {
        $config = new Configuration();
        $config->setProxyNamespace('Tmp\Doctrine\Tests\Proxies');
        $config->setProxyDir('/tmp/doctrine');
        $config->setQueryCache(new ArrayAdapter());
        $config->setAutoGenerateProxyClasses(false);
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
