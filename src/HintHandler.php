<?php declare(strict_types = 1);

namespace ShipMonk\Doctrine\Walker;

use Doctrine\ORM\Query\SqlWalker;

abstract class HintHandler
{

    /**
     * @var SqlWalker
     */
    private $sqlWalker;

    final public function __construct(SqlWalker $sqlWalker)
    {
        $this->sqlWalker = $sqlWalker;
    }

    protected function getDoctrineSqlWalker(): SqlWalker
    {
        return $this->sqlWalker;
    }

    protected function getHintValue(): mixed
    {
        return $this->sqlWalker->getQuery()->getHint(static::class);
    }

    /**
     * @return list<SqlNode::*>
     */
    abstract public function getNodes(): array;

    /**
     * @param SqlNode::* $sqlNode
     */
    abstract public function processNode(string $sqlNode, string $sql): string;

}
