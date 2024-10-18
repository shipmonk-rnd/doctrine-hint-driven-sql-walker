<?php declare(strict_types = 1);

namespace ShipMonk\Doctrine\Walker\Handlers;

use LogicException;
use ShipMonk\Doctrine\Walker\HintHandler;
use ShipMonk\Doctrine\Walker\SqlNode;
use function is_string;
use function preg_replace;

class CommentWholeSqlHintHandler extends HintHandler
{

    public function getNodes(): array
    {
        return [SqlNode::SelectStatement, SqlNode::UpdateStatement, SqlNode::DeleteStatement];
    }

    public function processNode(SqlNode $sqlNode, string $sql): string
    {
        $hintValue = $this->getHintValue();

        if (!is_string($hintValue)) {
            throw new LogicException('Hint value must be a string');
        }

        $result = preg_replace('~$~', ' -- ' . $hintValue, $sql);

        if ($result === null) {
            throw new LogicException('Regex failure');
        }

        return $result;
    }

}
