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
            'SELECT d0_.id AS id_0 FROM dummy_entity d0_ LIMIT 1 -- custom comment',
        ];
    }

    private function createEntityManagerMock(): EntityManager
    {
        $config = new Configuration();
        $config->setProxyNamespace('Tmp\Doctrine\Tests\Proxies');
        $config->setProxyDir('/tmp/doctrine');
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
