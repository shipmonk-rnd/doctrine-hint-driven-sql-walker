<?php declare(strict_types = 1);

namespace ShipMonk\Doctrine\Walker\Handlers;

use LogicException;
use ShipMonk\Doctrine\Walker\HintHandler;
use ShipMonk\Doctrine\Walker\SqlNode;
use function preg_replace;

class CommentWholeSqlHintHandler extends HintHandler
{

    public function getNodes(): array
    {
        return [SqlNode::SelectStatement];
    }

    public function processNode(SqlNode $sqlNode, string $sql): string
    {
        $result = preg_replace('~$~', ' -- ' . $this->getHintValue(), $sql);

        if ($result === null) {
            throw new LogicException('Regex failure');
        }

        return $result;
    }

}
