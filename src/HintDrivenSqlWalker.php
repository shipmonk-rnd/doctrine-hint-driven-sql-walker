<?php declare(strict_types = 1);

namespace ShipMonk\Doctrine\Walker;

use Doctrine\ORM\Query\AST\DeleteClause;
use Doctrine\ORM\Query\AST\DeleteStatement;
use Doctrine\ORM\Query\AST\GeneralCaseExpression;
use Doctrine\ORM\Query\AST\InListExpression;
use Doctrine\ORM\Query\AST\InSubselectExpression;
use Doctrine\ORM\Query\AST\Join;
use Doctrine\ORM\Query\AST\ParenthesisExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\UpdateStatement;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use LogicException;
use function is_a;

/**
 * @psalm-import-type QueryComponent from Parser
 */
class HintDrivenSqlWalker extends SqlWalker
{

    /**
     * @var array<SqlNode::*, list<HintHandler>>
     */
    private $stringSqlWalkers = [];

    public function __construct(
        $query,
        $parserResult,
        array $queryComponents
    )
    {
        parent::__construct($query, $parserResult, $queryComponents);

        $stringSqlWalkers = $this->createStringSqlWalkersFromHints();

        if ($stringSqlWalkers === []) {
            $selfClass = self::class;
            throw new LogicException("{$selfClass} was used, but no HintHandler child was added as hint. Use e.g. ->setHint(SomeHintHandler::class, ...)");
        }

        foreach ($stringSqlWalkers as $stringSqlWalker) {
            foreach ($stringSqlWalker->getNodes() as $sqlNode) {
                $this->stringSqlWalkers[$sqlNode][] = $stringSqlWalker;
            }
        }
    }

    public function walkSelectStatement(SelectStatement $AST): string
    {
        return $this->callWalkers(SqlNode::SelectStatement, parent::walkSelectStatement($AST));
    }

    public function walkUpdateStatement(UpdateStatement $AST): string
    {
        return $this->callWalkers(SqlNode::UpdateStatement, parent::walkUpdateStatement($AST));
    }

    public function walkDeleteStatement(DeleteStatement $AST): string
    {
        return $this->callWalkers(SqlNode::DeleteStatement, parent::walkDeleteStatement($AST));
    }

    public function walkEntityIdentificationVariable($identVariable): string
    {
        return $this->callWalkers(SqlNode::EntityIdentificationVariable, parent::walkEntityIdentificationVariable($identVariable));
    }

    public function walkIdentificationVariable($identificationVariable, $fieldName = null): string
    {
        return $this->callWalkers(SqlNode::IdentificationVariable, parent::walkIdentificationVariable($identificationVariable, $fieldName));
    }

    public function walkPathExpression($pathExpr): string
    {
        return $this->callWalkers(SqlNode::PathExpression, parent::walkPathExpression($pathExpr));
    }

    public function walkSelectClause($selectClause): string
    {
        return $this->callWalkers(SqlNode::SelectClause, parent::walkSelectClause($selectClause));
    }

    public function walkFromClause($fromClause): string
    {
        return $this->callWalkers(SqlNode::FromClause, parent::walkFromClause($fromClause));
    }

    public function walkIdentificationVariableDeclaration($identificationVariableDecl): string
    {
        return $this->callWalkers(SqlNode::IdentificationVariableDeclaration, parent::walkIdentificationVariableDeclaration($identificationVariableDecl));
    }

    public function walkRangeVariableDeclaration($rangeVariableDeclaration): string
    {
        return $this->callWalkers(SqlNode::RangeVariableDeclaration, parent::walkRangeVariableDeclaration($rangeVariableDeclaration));
    }

    public function walkJoinAssociationDeclaration($joinAssociationDeclaration, $joinType = Join::JOIN_TYPE_INNER, $condExpr = null): string
    {
        return $this->callWalkers(SqlNode::JoinAssociationDeclaration, parent::walkJoinAssociationDeclaration($joinAssociationDeclaration, $joinType, $condExpr));
    }

    public function walkFunction($function): string
    {
        return $this->callWalkers(SqlNode::Function, parent::walkFunction($function));
    }

    public function walkOrderByClause($orderByClause): string
    {
        return $this->callWalkers(SqlNode::OrderByClause, parent::walkOrderByClause($orderByClause));
    }

    public function walkOrderByItem($orderByItem): string
    {
        return $this->callWalkers(SqlNode::OrderByItem, parent::walkOrderByItem($orderByItem));
    }

    public function walkHavingClause($havingClause): string
    {
        return $this->callWalkers(SqlNode::HavingClause, parent::walkHavingClause($havingClause));
    }

    public function walkJoin($join): string
    {
        return $this->callWalkers(SqlNode::Join, parent::walkJoin($join));
    }

    public function walkCoalesceExpression($coalesceExpression): string
    {
        return $this->callWalkers(SqlNode::CoalesceExpression, parent::walkCoalesceExpression($coalesceExpression));
    }

    public function walkNullIfExpression($nullIfExpression): string
    {
        return $this->callWalkers(SqlNode::NullIfExpression, parent::walkNullIfExpression($nullIfExpression));
    }

    public function walkGeneralCaseExpression(GeneralCaseExpression $generalCaseExpression): string
    {
        return $this->callWalkers(SqlNode::GeneralCaseExpression, parent::walkGeneralCaseExpression($generalCaseExpression));
    }

    public function walkSimpleCaseExpression($simpleCaseExpression): string
    {
        return $this->callWalkers(SqlNode::SimpleCaseExpression, parent::walkSimpleCaseExpression($simpleCaseExpression));
    }

    public function walkSelectExpression($selectExpression): string
    {
        return $this->callWalkers(SqlNode::SelectExpression, parent::walkSelectExpression($selectExpression));
    }

    public function walkQuantifiedExpression($qExpr): string
    {
        return $this->callWalkers(SqlNode::QuantifiedExpression, parent::walkQuantifiedExpression($qExpr));
    }

    public function walkSubselect($subselect): string
    {
        return $this->callWalkers(SqlNode::Subselect, parent::walkSubselect($subselect));
    }

    public function walkSubselectFromClause($subselectFromClause): string
    {
        return $this->callWalkers(SqlNode::SubselectFromClause, parent::walkSubselectFromClause($subselectFromClause));
    }

    public function walkSimpleSelectClause($simpleSelectClause): string
    {
        return $this->callWalkers(SqlNode::SimpleSelectClause, parent::walkSimpleSelectClause($simpleSelectClause));
    }

    public function walkParenthesisExpression(ParenthesisExpression $parenthesisExpression): string
    {
        return $this->callWalkers(SqlNode::ParenthesisExpression, parent::walkParenthesisExpression($parenthesisExpression));
    }

    public function walkNewObject($newObjectExpression, $newObjectResultAlias = null): string
    {
        return $this->callWalkers(SqlNode::NewObject, parent::walkNewObject($newObjectExpression, $newObjectResultAlias));
    }

    public function walkSimpleSelectExpression($simpleSelectExpression): string
    {
        return $this->callWalkers(SqlNode::SimpleSelectExpression, parent::walkSimpleSelectExpression($simpleSelectExpression));
    }

    public function walkAggregateExpression($aggExpression): string
    {
        return $this->callWalkers(SqlNode::AggregateExpression, parent::walkAggregateExpression($aggExpression));
    }

    public function walkGroupByClause($groupByClause): string
    {
        return $this->callWalkers(SqlNode::GroupByClause, parent::walkGroupByClause($groupByClause));
    }

    public function walkGroupByItem($groupByItem): string
    {
        return $this->callWalkers(SqlNode::GroupByItem, parent::walkGroupByItem($groupByItem));
    }

    public function walkDeleteClause(DeleteClause $deleteClause): string
    {
        return $this->callWalkers(SqlNode::DeleteClause, parent::walkDeleteClause($deleteClause));
    }

    public function walkUpdateClause($updateClause): string
    {
        return $this->callWalkers(SqlNode::UpdateClause, parent::walkUpdateClause($updateClause));
    }

    public function walkUpdateItem($updateItem): string
    {
        return $this->callWalkers(SqlNode::UpdateItem, parent::walkUpdateItem($updateItem));
    }

    public function walkWhereClause($whereClause): string
    {
        return $this->callWalkers(SqlNode::WhereClause, parent::walkWhereClause($whereClause));
    }

    public function walkConditionalExpression($condExpr): string
    {
        return $this->callWalkers(SqlNode::ConditionalExpression, parent::walkConditionalExpression($condExpr));
    }

    public function walkConditionalTerm($condTerm): string
    {
        return $this->callWalkers(SqlNode::ConditionalTerm, parent::walkConditionalTerm($condTerm));
    }

    public function walkConditionalFactor($factor): string
    {
        return $this->callWalkers(SqlNode::ConditionalFactor, parent::walkConditionalFactor($factor));
    }

    public function walkConditionalPrimary($primary): string
    {
        return $this->callWalkers(SqlNode::ConditionalPrimary, parent::walkConditionalPrimary($primary));
    }

    public function walkExistsExpression($existsExpr): string
    {
        return $this->callWalkers(SqlNode::ExistsExpression, parent::walkExistsExpression($existsExpr));
    }

    public function walkCollectionMemberExpression($collMemberExpr): string
    {
        return $this->callWalkers(SqlNode::CollectionMemberExpression, parent::walkCollectionMemberExpression($collMemberExpr));
    }

    public function walkEmptyCollectionComparisonExpression($emptyCollCompExpr): string
    {
        return $this->callWalkers(SqlNode::EmptyCollectionComparisonExpression, parent::walkEmptyCollectionComparisonExpression($emptyCollCompExpr));
    }

    public function walkNullComparisonExpression($nullCompExpr): string
    {
        return $this->callWalkers(SqlNode::NullComparisonExpression, parent::walkNullComparisonExpression($nullCompExpr));
    }

    public function walkInListExpression(InListExpression $inExpr): string
    {
        return $this->callWalkers(SqlNode::InListExpression, parent::walkInListExpression($inExpr));
    }

    public function walkInSubselectExpression(InSubselectExpression $inExpr): string
    {
        return $this->callWalkers(SqlNode::InSubselectExpression, parent::walkInSubselectExpression($inExpr));
    }

    public function walkInstanceOfExpression($instanceOfExpr): string
    {
        return $this->callWalkers(SqlNode::InstanceOfExpression, parent::walkInstanceOfExpression($instanceOfExpr));
    }

    public function walkInParameter($inParam): string
    {
        return $this->callWalkers(SqlNode::InParameter, parent::walkInParameter($inParam));
    }

    public function walkLiteral($literal): string
    {
        return $this->callWalkers(SqlNode::Literal, parent::walkLiteral($literal));
    }

    public function walkBetweenExpression($betweenExpr): string
    {
        return $this->callWalkers(SqlNode::BetweenExpression, parent::walkBetweenExpression($betweenExpr));
    }

    public function walkLikeExpression($likeExpr): string
    {
        return $this->callWalkers(SqlNode::LikeExpression, parent::walkLikeExpression($likeExpr));
    }

    public function walkStateFieldPathExpression($stateFieldPathExpression): string
    {
        return $this->callWalkers(SqlNode::StateFieldPathExpression, parent::walkStateFieldPathExpression($stateFieldPathExpression));
    }

    public function walkComparisonExpression($compExpr): string
    {
        return $this->callWalkers(SqlNode::ComparisonExpression, parent::walkComparisonExpression($compExpr));
    }

    public function walkInputParameter($inputParam): string
    {
        return $this->callWalkers(SqlNode::InputParameter, parent::walkInputParameter($inputParam));
    }

    public function walkArithmeticExpression($arithmeticExpr): string
    {
        return $this->callWalkers(SqlNode::ArithmeticExpression, parent::walkArithmeticExpression($arithmeticExpr));
    }

    public function walkSimpleArithmeticExpression($simpleArithmeticExpr): string
    {
        return $this->callWalkers(SqlNode::SimpleArithmeticExpression, parent::walkSimpleArithmeticExpression($simpleArithmeticExpr));
    }

    public function walkArithmeticTerm($term): string
    {
        return $this->callWalkers(SqlNode::ArithmeticTerm, parent::walkArithmeticTerm($term));
    }

    public function walkArithmeticFactor($factor): string
    {
        return $this->callWalkers(SqlNode::ArithmeticFactor, parent::walkArithmeticFactor($factor));
    }

    public function walkArithmeticPrimary($primary): string
    {
        return $this->callWalkers(SqlNode::ArithmeticPrimary, parent::walkArithmeticPrimary($primary));
    }

    public function walkStringPrimary($stringPrimary): string
    {
        return $this->callWalkers(SqlNode::StringPrimary, parent::walkStringPrimary($stringPrimary));
    }

    public function walkResultVariable($resultVariable): string
    {
        return $this->callWalkers(SqlNode::ResultVariable, parent::walkResultVariable($resultVariable));
    }

    /**
     * @param SqlNode::* $sqlNode
     */
    private function callWalkers(string $sqlNode, string $sql): string
    {
        foreach ($this->stringSqlWalkers[$sqlNode] ?? [] as $stringSqlWalker) {
            $sql = $stringSqlWalker->processNode($sqlNode, $sql);
        }

        return $sql;
    }

    /**
     * @return list<HintHandler>
     */
    private function createStringSqlWalkersFromHints(): array
    {
        $result = [];
        $hints = $this->getQuery()->getHints();

        foreach ($hints as $hintKey => $hintValue) {
            if (is_a($hintKey, HintHandler::class, true)) {
                $result[] = new $hintKey($this);
            }
        }

        return $result;
    }

}
