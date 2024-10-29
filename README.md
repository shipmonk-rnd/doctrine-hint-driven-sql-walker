## SqlWalker for Doctrine allowing multiple handlers to modify resulting SQL

Since Doctrine's [SqlWalker](https://www.doctrine-project.org/projects/doctrine-orm/en/3.3/cookbook/dql-custom-walkers.html#modify-the-output-walker-to-generate-vendor-specific-sql) serves as a translator from DQL AST to SQL,
it becomes problematic when you want to alter resulting SQL within multiple libraries by such approach.
There just can be only single SqlWalker.

This library solves this issue, by providing `HintHandler` base class which is designed for SQL modification
and can be used multiple times in `$queryBuilder->setHint()`.

### Installation:

```sh
composer require shipmonk/doctrine-hint-driven-sql-walker
```

### Usage:

```php
$queryBuilder
    ->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, HintDrivenSqlWalker::class)
    ->setHint(MaxExecutionTimeHintHandler::class, 1000)
```

Where `MaxExecutionTimeHintHandler` just extends our `HintHandler` and picks some `SqlNode` to hook to and alters appropriate SQL part:

```php
class MaxExecutionTimeSqlWalker extends HintHandler
{

    public function getNodes(): array
    {
        return [SqlNode::SelectClause];
    }

    public function processNode(
        SqlNode $sqlNode,
        string $sql,
    ): string
    {
        // grab the 1000 passed to ->setHint()
        $milliseconds = $this->getHintValue();

        // edit SQL as needed
        return preg_replace(
            '~^SELECT (.*?)~',
            "SELECT /*+ MAX_EXECUTION_TIME($milliseconds) */ \\1 ",
            $sql
        );
    }
```

SqlNode is an enum of all `walkXxx` methods in Doctrine's SqlWalker, so you are able to intercept any part of AST processing the SqlWalker does.

### Limitations
- Please note that since [doctrine/orm 3.3.0](https://github.com/doctrine/orm/pull/11188), the produced SQL gets finalized with `LIMIT` / `OFFSET` / `FOR UPDATE` after `SqlWalker` processing is done.
  - Thus, implementors should be aware that those SQL parts can be appended to the SQL after `HintHandler` processing.
  - This means that e.g. placing a comment at the end of the SQL breaks LIMIT functionality completely

### Implementors
- [shipmonk/doctrine-mysql-optimizer-hints](https://github.com/shipmonk-rnd/doctrine-mysql-optimizer-hints) (since v2)
- [shipmonk/doctrine-mysql-index-hints](https://github.com/shipmonk-rnd/doctrine-mysql-index-hints) (since v3)

### Compatibility
| Version | PHP Compatibility | doctrine/orm |
|---------| ----------------- |--------------|
| v1      | 7.2 - 8.3         | 2.x          |
| v2      | 8.1 - 8.4         | 3.x          |
