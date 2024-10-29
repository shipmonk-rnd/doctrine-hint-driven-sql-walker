<?php declare(strict_types = 1);

namespace ShipMonk\Doctrine\Walker;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Exec\AbstractSqlExecutor;
use Doctrine\ORM\Query\Exec\FinalizedSelectExecutor;
use Doctrine\ORM\Query\Exec\SqlFinalizer;

class NoopSqlFinalizer implements SqlFinalizer
{

    public function __construct(readonly private string $sql)
    {
    }

    public function createExecutor(Query $query): AbstractSqlExecutor
    {
        return new FinalizedSelectExecutor($this->sql);
    }

}
