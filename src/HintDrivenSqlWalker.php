<?php declare(strict_types = 1);

namespace ShipMonk\Doctrine\Walker;

use Doctrine\ORM\Query\AST\AggregateExpression;
use Doctrine\ORM\Query\AST\ArithmeticExpression;
use Doctrine\ORM\Query\AST\BetweenExpression;
use Doctrine\ORM\Query\AST\CoalesceExpression;
use Doctrine\ORM\Query\AST\CollectionMemberExpression;
use Doctrine\ORM\Query\AST\ComparisonExpression;
use Doctrine\ORM\Query\AST\ConditionalExpression;
use Doctrine\ORM\Query\AST\ConditionalFactor;
use Doctrine\ORM\Query\AST\ConditionalPrimary;
use Doctrine\ORM\Query\AST\ConditionalTerm;
use Doctrine\ORM\Query\AST\DeleteClause;
use Doctrine\ORM\Query\AST\DeleteStatement;
use Doctrine\ORM\Query\AST\EmptyCollectionComparisonExpression;
use Doctrine\ORM\Query\AST\ExistsExpression;
use Doctrine\ORM\Query\AST\FromClause;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\GeneralCaseExpression;
use Doctrine\ORM\Query\AST\GroupByClause;
use Doctrine\ORM\Query\AST\HavingClause;
use Doctrine\ORM\Query\AST\IdentificationVariableDeclaration;
use Doctrine\ORM\Query\AST\InListExpression;
use Doctrine\ORM\Query\AST\InputParameter;
use Doctrine\ORM\Query\AST\InstanceOfExpression;
use Doctrine\ORM\Query\AST\InSubselectExpression;
use Doctrine\ORM\Query\AST\Join;
use Doctrine\ORM\Query\AST\JoinAssociationDeclaration;
use Doctrine\ORM\Query\AST\LikeExpression;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\AST\NewObjectExpression;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\NullComparisonExpression;
use Doctrine\ORM\Query\AST\NullIfExpression;
use Doctrine\ORM\Query\AST\OrderByClause;
use Doctrine\ORM\Query\AST\OrderByItem;
use Doctrine\ORM\Query\AST\ParenthesisExpression;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\Phase2OptimizableConditional;
use Doctrine\ORM\Query\AST\QuantifiedExpression;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\SelectClause;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\SimpleCaseExpression;
use Doctrine\ORM\Query\AST\SimpleSelectClause;
use Doctrine\ORM\Query\AST\SimpleSelectExpression;
use Doctrine\ORM\Query\AST\Subselect;
use Doctrine\ORM\Query\AST\SubselectFromClause;
use Doctrine\ORM\Query\AST\UpdateClause;
use Doctrine\ORM\Query\AST\UpdateItem;
use Doctrine\ORM\Query\AST\UpdateStatement;
use Doctrine\ORM\Query\AST\WhereClause;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlOutputWalker;
use LogicException;
use function is_a;

/**
 * @psalm-import-type QueryComponent from Parser
 */
class HintDrivenSqlWalker extends SqlOutputWalker
{

    /**
     * @var array<value-of<SqlNode>, list<HintHandler>>
     */
    private array $stringSqlWalkers = [];

    public function __construct(
        $query,
        $parserResult,
        array $queryComponents,
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
                $this->stringSqlWalkers[$sqlNode->value][] = $stringSqlWalker;
            }
        }
    }

    protected function createSqlForFinalizer(SelectStatement $selectStatement): string
    {
        $selectStatementSql = parent::createSqlForFinalizer($selectStatement);
        return $this->callWalkers(SqlNode::SelectStatement, $selectStatementSql);
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

    public function walkEntityIdentificationVariable(string $identVariable): string
    {
        return $this->callWalkers(SqlNode::EntityIdentificationVariable, parent::walkEntityIdentificationVariable($identVariable));
    }

    public function walkIdentificationVariable(string $identificationVariable, string|null $fieldName = null): string
    {
        return $this->callWalkers(SqlNode::IdentificationVariable, parent::walkIdentificationVariable($identificationVariable, $fieldName));
    }

    public function walkPathExpression(PathExpression $pathExpr): string
    {
        return $this->callWalkers(SqlNode::PathExpression, parent::walkPathExpression($pathExpr));
    }

    public function walkSelectClause(SelectClause $selectClause): string
    {
        return $this->callWalkers(SqlNode::SelectClause, parent::walkSelectClause($selectClause));
    }

    public function walkFromClause(FromClause $fromClause): string
    {
        return $this->callWalkers(SqlNode::FromClause, parent::walkFromClause($fromClause));
    }

    public function walkIdentificationVariableDeclaration(IdentificationVariableDeclaration $identificationVariableDecl): string
    {
        return $this->callWalkers(SqlNode::IdentificationVariableDeclaration, parent::walkIdentificationVariableDeclaration($identificationVariableDecl));
    }

    public function walkRangeVariableDeclaration(RangeVariableDeclaration $rangeVariableDeclaration): string
    {
        return $this->callWalkers(SqlNode::RangeVariableDeclaration, parent::walkRangeVariableDeclaration($rangeVariableDeclaration));
    }

    public function walkJoinAssociationDeclaration(
        JoinAssociationDeclaration $joinAssociationDeclaration,
        int $joinType = Join::JOIN_TYPE_INNER,
        ConditionalExpression|Phase2OptimizableConditional|null $condExpr = null,
    ): string
    {
        return $this->callWalkers(SqlNode::JoinAssociationDeclaration, parent::walkJoinAssociationDeclaration($joinAssociationDeclaration, $joinType, $condExpr));
    }

    public function walkFunction(FunctionNode $function): string
    {
        return $this->callWalkers(SqlNode::Function, parent::walkFunction($function));
    }

    public function walkOrderByClause(OrderByClause $orderByClause): string
    {
        return $this->callWalkers(SqlNode::OrderByClause, parent::walkOrderByClause($orderByClause));
    }

    public function walkOrderByItem(OrderByItem $orderByItem): string
    {
        return $this->callWalkers(SqlNode::OrderByItem, parent::walkOrderByItem($orderByItem));
    }

    public function walkHavingClause(HavingClause $havingClause): string
    {
        return $this->callWalkers(SqlNode::HavingClause, parent::walkHavingClause($havingClause));
    }

    public function walkJoin(Join $join): string
    {
        return $this->callWalkers(SqlNode::Join, parent::walkJoin($join));
    }

    public function walkCoalesceExpression(CoalesceExpression $coalesceExpression): string
    {
        return $this->callWalkers(SqlNode::CoalesceExpression, parent::walkCoalesceExpression($coalesceExpression));
    }

    public function walkNullIfExpression(NullIfExpression $nullIfExpression): string
    {
        return $this->callWalkers(SqlNode::NullIfExpression, parent::walkNullIfExpression($nullIfExpression));
    }

    public function walkGeneralCaseExpression(GeneralCaseExpression $generalCaseExpression): string
    {
        return $this->callWalkers(SqlNode::GeneralCaseExpression, parent::walkGeneralCaseExpression($generalCaseExpression));
    }

    public function walkSimpleCaseExpression(SimpleCaseExpression $simpleCaseExpression): string
    {
        return $this->callWalkers(SqlNode::SimpleCaseExpression, parent::walkSimpleCaseExpression($simpleCaseExpression));
    }

    public function walkSelectExpression(SelectExpression $selectExpression): string
    {
        return $this->callWalkers(SqlNode::SelectExpression, parent::walkSelectExpression($selectExpression));
    }

    public function walkQuantifiedExpression(QuantifiedExpression $qExpr): string
    {
        return $this->callWalkers(SqlNode::QuantifiedExpression, parent::walkQuantifiedExpression($qExpr));
    }

    public function walkSubselect(Subselect $subselect): string
    {
        return $this->callWalkers(SqlNode::Subselect, parent::walkSubselect($subselect));
    }

    public function walkSubselectFromClause(SubselectFromClause $subselectFromClause): string
    {
        return $this->callWalkers(SqlNode::SubselectFromClause, parent::walkSubselectFromClause($subselectFromClause));
    }

    public function walkSimpleSelectClause(SimpleSelectClause $simpleSelectClause): string
    {
        return $this->callWalkers(SqlNode::SimpleSelectClause, parent::walkSimpleSelectClause($simpleSelectClause));
    }

    public function walkParenthesisExpression(ParenthesisExpression $parenthesisExpression): string
    {
        return $this->callWalkers(SqlNode::ParenthesisExpression, parent::walkParenthesisExpression($parenthesisExpression));
    }

    public function walkNewObject(NewObjectExpression $newObjectExpression, string|null $newObjectResultAlias = null): string
    {
        return $this->callWalkers(SqlNode::NewObject, parent::walkNewObject($newObjectExpression, $newObjectResultAlias));
    }

    public function walkSimpleSelectExpression(SimpleSelectExpression $simpleSelectExpression): string
    {
        return $this->callWalkers(SqlNode::SimpleSelectExpression, parent::walkSimpleSelectExpression($simpleSelectExpression));
    }

    public function walkAggregateExpression(AggregateExpression $aggExpression): string
    {
        return $this->callWalkers(SqlNode::AggregateExpression, parent::walkAggregateExpression($aggExpression));
    }

    public function walkGroupByClause(GroupByClause $groupByClause): string
    {
        return $this->callWalkers(SqlNode::GroupByClause, parent::walkGroupByClause($groupByClause));
    }

    public function walkGroupByItem(PathExpression|string $groupByItem): string
    {
        return $this->callWalkers(SqlNode::GroupByItem, parent::walkGroupByItem($groupByItem));
    }

    public function walkDeleteClause(DeleteClause $deleteClause): string
    {
        return $this->callWalkers(SqlNode::DeleteClause, parent::walkDeleteClause($deleteClause));
    }

    public function walkUpdateClause(UpdateClause $updateClause): string
    {
        return $this->callWalkers(SqlNode::UpdateClause, parent::walkUpdateClause($updateClause));
    }

    public function walkUpdateItem(UpdateItem $updateItem): string
    {
        return $this->callWalkers(SqlNode::UpdateItem, parent::walkUpdateItem($updateItem));
    }

    public function walkWhereClause(WhereClause|null $whereClause): string
    {
        return $this->callWalkers(SqlNode::WhereClause, parent::walkWhereClause($whereClause));
    }

    public function walkConditionalExpression(ConditionalExpression|Phase2OptimizableConditional $condExpr): string
    {
        return $this->callWalkers(SqlNode::ConditionalExpression, parent::walkConditionalExpression($condExpr));
    }

    public function walkConditionalTerm(ConditionalTerm|ConditionalPrimary|ConditionalFactor $condTerm): string
    {
        return $this->callWalkers(SqlNode::ConditionalTerm, parent::walkConditionalTerm($condTerm));
    }

    public function walkConditionalFactor(ConditionalFactor|ConditionalPrimary $factor): string
    {
        return $this->callWalkers(SqlNode::ConditionalFactor, parent::walkConditionalFactor($factor));
    }

    public function walkConditionalPrimary(ConditionalPrimary $primary): string
    {
        return $this->callWalkers(SqlNode::ConditionalPrimary, parent::walkConditionalPrimary($primary));
    }

    public function walkExistsExpression(ExistsExpression $existsExpr): string
    {
        return $this->callWalkers(SqlNode::ExistsExpression, parent::walkExistsExpression($existsExpr));
    }

    public function walkCollectionMemberExpression(CollectionMemberExpression $collMemberExpr): string
    {
        return $this->callWalkers(SqlNode::CollectionMemberExpression, parent::walkCollectionMemberExpression($collMemberExpr));
    }

    public function walkEmptyCollectionComparisonExpression(EmptyCollectionComparisonExpression $emptyCollCompExpr): string
    {
        return $this->callWalkers(SqlNode::EmptyCollectionComparisonExpression, parent::walkEmptyCollectionComparisonExpression($emptyCollCompExpr));
    }

    public function walkNullComparisonExpression(NullComparisonExpression $nullCompExpr): string
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

    public function walkInstanceOfExpression(InstanceOfExpression $instanceOfExpr): string
    {
        return $this->callWalkers(SqlNode::InstanceOfExpression, parent::walkInstanceOfExpression($instanceOfExpr));
    }

    public function walkInParameter(mixed $inParam): string
    {
        return $this->callWalkers(SqlNode::InParameter, parent::walkInParameter($inParam));
    }

    public function walkLiteral(Literal $literal): string
    {
        return $this->callWalkers(SqlNode::Literal, parent::walkLiteral($literal));
    }

    public function walkBetweenExpression(BetweenExpression $betweenExpr): string
    {
        return $this->callWalkers(SqlNode::BetweenExpression, parent::walkBetweenExpression($betweenExpr));
    }

    public function walkLikeExpression(LikeExpression $likeExpr): string
    {
        return $this->callWalkers(SqlNode::LikeExpression, parent::walkLikeExpression($likeExpr));
    }

    public function walkStateFieldPathExpression(PathExpression $stateFieldPathExpression): string
    {
        return $this->callWalkers(SqlNode::StateFieldPathExpression, parent::walkStateFieldPathExpression($stateFieldPathExpression));
    }

    public function walkComparisonExpression(ComparisonExpression $compExpr): string
    {
        return $this->callWalkers(SqlNode::ComparisonExpression, parent::walkComparisonExpression($compExpr));
    }

    public function walkInputParameter(InputParameter $inputParam): string
    {
        return $this->callWalkers(SqlNode::InputParameter, parent::walkInputParameter($inputParam));
    }

    public function walkArithmeticExpression(ArithmeticExpression $arithmeticExpr): string
    {
        return $this->callWalkers(SqlNode::ArithmeticExpression, parent::walkArithmeticExpression($arithmeticExpr));
    }

    public function walkSimpleArithmeticExpression(Node|string $simpleArithmeticExpr): string
    {
        return $this->callWalkers(SqlNode::SimpleArithmeticExpression, parent::walkSimpleArithmeticExpression($simpleArithmeticExpr));
    }

    public function walkArithmeticTerm(Node|string $term): string
    {
        return $this->callWalkers(SqlNode::ArithmeticTerm, parent::walkArithmeticTerm($term));
    }

    public function walkArithmeticFactor(Node|string $factor): string
    {
        return $this->callWalkers(SqlNode::ArithmeticFactor, parent::walkArithmeticFactor($factor));
    }

    public function walkArithmeticPrimary(Node|string $primary): string
    {
        return $this->callWalkers(SqlNode::ArithmeticPrimary, parent::walkArithmeticPrimary($primary));
    }

    public function walkStringPrimary(Node|string $stringPrimary): string
    {
        return $this->callWalkers(SqlNode::StringPrimary, parent::walkStringPrimary($stringPrimary));
    }

    public function walkResultVariable(string $resultVariable): string
    {
        return $this->callWalkers(SqlNode::ResultVariable, parent::walkResultVariable($resultVariable));
    }

    private function callWalkers(SqlNode $sqlNode, string $sql): string
    {
        foreach ($this->stringSqlWalkers[$sqlNode->value] ?? [] as $stringSqlWalker) {
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
