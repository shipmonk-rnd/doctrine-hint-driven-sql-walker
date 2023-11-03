<?php declare(strict_types = 1);

namespace ShipMonk\Doctrine\Walker;

final class SqlNode
{

    public const SelectStatement = 'SelectStatement';
    public const UpdateStatement = 'UpdateStatement';
    public const DeleteStatement = 'DeleteStatement';
    public const EntityIdentificationVariable = 'EntityIdentificationVariable';
    public const IdentificationVariable = 'IdentificationVariable';
    public const PathExpression = 'PathExpression';
    public const SelectClause = 'SelectClause';
    public const FromClause = 'FromClause';
    public const IdentificationVariableDeclaration = 'IdentificationVariableDeclaration';
    public const RangeVariableDeclaration = 'RangeVariableDeclaration';
    public const JoinAssociationDeclaration = 'JoinAssociationDeclaration';
    public const Function = 'Function';
    public const OrderByClause = 'OrderByClause';
    public const OrderByItem = 'OrderByItem';
    public const HavingClause = 'HavingClause';
    public const Join = 'Join';
    public const CoalesceExpression = 'CoalesceExpression';
    public const NullIfExpression = 'NullIfExpression';
    public const GeneralCaseExpression = 'GeneralCaseExpression';
    public const SimpleCaseExpression = 'SimpleCaseExpression';
    public const SelectExpression = 'SelectExpression';
    public const QuantifiedExpression = 'QuantifiedExpression';
    public const Subselect = 'Subselect';
    public const SubselectFromClause = 'SubselectFromClause';
    public const SimpleSelectClause = 'SimpleSelectClause';
    public const ParenthesisExpression = 'ParenthesisExpression';
    public const NewObject = 'NewObject';
    public const SimpleSelectExpression = 'SimpleSelectExpression';
    public const AggregateExpression = 'AggregateExpression';
    public const GroupByClause = 'GroupByClause';
    public const GroupByItem = 'GroupByItem';
    public const DeleteClause = 'DeleteClause';
    public const UpdateClause = 'UpdateClause';
    public const UpdateItem = 'UpdateItem';
    public const WhereClause = 'WhereClause';
    public const ConditionalExpression = 'ConditionalExpression';
    public const ConditionalTerm = 'ConditionalTerm';
    public const ConditionalFactor = 'ConditionalFactor';
    public const ConditionalPrimary = 'ConditionalPrimary';
    public const ExistsExpression = 'ExistsExpression';
    public const CollectionMemberExpression = 'CollectionMemberExpression';
    public const EmptyCollectionComparisonExpression = 'EmptyCollectionComparisonExpression';
    public const NullComparisonExpression = 'NullComparisonExpression';
    public const InListExpression = 'InListExpression';
    public const InSubselectExpression = 'InSubselectExpression';
    public const InstanceOfExpression = 'InstanceOfExpression';
    public const InParameter = 'InParameter';
    public const Literal = 'Literal';
    public const BetweenExpression = 'BetweenExpression';
    public const LikeExpression = 'LikeExpression';
    public const StateFieldPathExpression = 'StateFieldPathExpression';
    public const ComparisonExpression = 'ComparisonExpression';
    public const InputParameter = 'InputParameter';
    public const ArithmeticExpression = 'ArithmeticExpression';
    public const SimpleArithmeticExpression = 'SimpleArithmeticExpression';
    public const ArithmeticTerm = 'ArithmeticTerm';
    public const ArithmeticFactor = 'ArithmeticFactor';
    public const ArithmeticPrimary = 'ArithmeticPrimary';
    public const StringPrimary = 'StringPrimary';
    public const ResultVariable = 'ResultVariable';

}
