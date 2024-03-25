<?php declare(strict_types = 1);

namespace ShipMonk\Doctrine\Walker;

enum SqlNode: string
{

    case SelectStatement = 'SelectStatement';
    case UpdateStatement = 'UpdateStatement';
    case DeleteStatement = 'DeleteStatement';
    case EntityIdentificationVariable = 'EntityIdentificationVariable';
    case IdentificationVariable = 'IdentificationVariable';
    case PathExpression = 'PathExpression';
    case SelectClause = 'SelectClause';
    case FromClause = 'FromClause';
    case IdentificationVariableDeclaration = 'IdentificationVariableDeclaration';
    case RangeVariableDeclaration = 'RangeVariableDeclaration';
    case JoinAssociationDeclaration = 'JoinAssociationDeclaration';
    case Function = 'Function';
    case OrderByClause = 'OrderByClause';
    case OrderByItem = 'OrderByItem';
    case HavingClause = 'HavingClause';
    case Join = 'Join';
    case CoalesceExpression = 'CoalesceExpression';
    case NullIfExpression = 'NullIfExpression';
    case GeneralCaseExpression = 'GeneralCaseExpression';
    case SimpleCaseExpression = 'SimpleCaseExpression';
    case SelectExpression = 'SelectExpression';
    case QuantifiedExpression = 'QuantifiedExpression';
    case Subselect = 'Subselect';
    case SubselectFromClause = 'SubselectFromClause';
    case SimpleSelectClause = 'SimpleSelectClause';
    case ParenthesisExpression = 'ParenthesisExpression';
    case NewObject = 'NewObject';
    case SimpleSelectExpression = 'SimpleSelectExpression';
    case AggregateExpression = 'AggregateExpression';
    case GroupByClause = 'GroupByClause';
    case GroupByItem = 'GroupByItem';
    case DeleteClause = 'DeleteClause';
    case UpdateClause = 'UpdateClause';
    case UpdateItem = 'UpdateItem';
    case WhereClause = 'WhereClause';
    case ConditionalExpression = 'ConditionalExpression';
    case ConditionalTerm = 'ConditionalTerm';
    case ConditionalFactor = 'ConditionalFactor';
    case ConditionalPrimary = 'ConditionalPrimary';
    case ExistsExpression = 'ExistsExpression';
    case CollectionMemberExpression = 'CollectionMemberExpression';
    case EmptyCollectionComparisonExpression = 'EmptyCollectionComparisonExpression';
    case NullComparisonExpression = 'NullComparisonExpression';
    case InListExpression = 'InListExpression';
    case InSubselectExpression = 'InSubselectExpression';
    case InstanceOfExpression = 'InstanceOfExpression';
    case InParameter = 'InParameter';
    case Literal = 'Literal';
    case BetweenExpression = 'BetweenExpression';
    case LikeExpression = 'LikeExpression';
    case StateFieldPathExpression = 'StateFieldPathExpression';
    case ComparisonExpression = 'ComparisonExpression';
    case InputParameter = 'InputParameter';
    case ArithmeticExpression = 'ArithmeticExpression';
    case SimpleArithmeticExpression = 'SimpleArithmeticExpression';
    case ArithmeticTerm = 'ArithmeticTerm';
    case ArithmeticFactor = 'ArithmeticFactor';
    case ArithmeticPrimary = 'ArithmeticPrimary';
    case StringPrimary = 'StringPrimary';
    case ResultVariable = 'ResultVariable';

}
