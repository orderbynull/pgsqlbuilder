<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Action;

use Orderbynull\PgSqlBuilder\Exceptions\AttributeException;
use Orderbynull\PgSqlBuilder\Exceptions\FiltrationException;
use Orderbynull\PgSqlBuilder\Exceptions\InputTypeException;
use Orderbynull\PgSqlBuilder\Exceptions\TypeCastException;
use Orderbynull\PgSqlBuilder\Input\DataInput;
use Orderbynull\PgSqlBuilder\Input\InputInterface;
use Orderbynull\PgSqlBuilder\Input\UserInput;
use Orderbynull\PgSqlBuilder\Utils\Type;

/**
 * Class Action
 * @package Orderbynull\PgSqlBuilder\Action
 */
abstract class AbstractAction
{
    /**
     * Base entity for action.
     *
     * @var int
     */
    protected int $baseEntityId;

    /**
     * Rules to apply to WHERE operator.
     * Structure:
     * [
     *    [Condition, AND|OR, condition, ...],
     *    AND|OR,
     *    [Condition, AND|OR, condition, ...],
     *    ...
     * ]
     *
     * @var array
     */
    protected array $conditions = [];

    /**
     * @var array
     */
    protected array $groupOfRules = [];

    /**
     * Holds an array of rowIds for each nodeId which data must be limited to these rowIds.
     * It works like SELECT * FROM nodeId WHERE row_id IN(rowId, rowId, ...).
     * Structure:
     * [
     *    nodeId::int => [rowId::int, ..., rowId::int],
     *    ...
     * ]
     *
     * @var array
     */
    protected array $dataInputLimits = [];

    /**
     * @var array
     */
    protected array $userInputBindings = [];

    /**
     * @var array
     */
    protected array $filterAttributeValues = [];

    /**
     * AbstractAction constructor.
     * @param int $baseEntityId
     */
    public function __construct(int $baseEntityId)
    {
        $this->baseEntityId = $baseEntityId;
    }

    /**
     * @return array
     */
    public function getQueryBindings(): array
    {
        return $this->userInputBindings;
    }

    /**
     * @param string|null $operator
     */
    public function openConditionsGroup(?string $operator = null): void
    {
        if (empty($this->groupOfRules)) {
            return;
        }

        $this->conditions[] = $this->groupOfRules;

        if ($operator) {
            $this->conditions[] = $operator;
        }

        $this->groupOfRules = [];
    }

    public function closeConditionsGroup(): void
    {
        !empty($this->groupOfRules) && $this->conditions[] = $this->groupOfRules;
    }

    /**
     * @param Condition $condition
     * @param string|null $operator
     * @throws FiltrationException
     */
    public function addCondition(Condition $condition, ?string $operator = null): void
    {
        // AND/OR не может быть первым в группе условий
        if ($operator && !end($this->groupOfRules) instanceof Condition) {
            throw new FiltrationException('AND\OR can be added after condition only');
        }

        if (!empty($operator)) {
            $this->groupOfRules[] = $operator;
        }

        // Правила фильтрации не могут идти подряд без разделения AND/OR
        if (end($this->groupOfRules) instanceof Condition) {
            throw new FiltrationException('Cannot join two conditions without AND\OR');
        }

        $this->groupOfRules[] = $condition;
    }

    /**
     * @param EntityAttribute $attribute
     * @param InputInterface $input
     */
    public function setConditionsAttributeValue(EntityAttribute $attribute, InputInterface $input): void
    {
        if ($input instanceof UserInput) {
            $this->registerUserInputAsBinding($attribute, $input);
        }

        $this->filterAttributeValues[$attribute->getPlaceholder(true)] = $input;
    }

    /**
     * @param EntityAttribute $attribute
     * @param UserInput $userInput
     */
    protected function registerUserInputAsBinding(EntityAttribute $attribute, UserInput $userInput): void
    {
        $this->userInputBindings[$attribute->getPlaceholder(true)] = $userInput->value;
    }

    /**
     * @param int $nodeId
     * @param array $rowIds
     */
    public function limitDataInputTo(int $nodeId, array $rowIds): void
    {
        $this->dataInputLimits[$nodeId] = $rowIds;
    }

    /**
     * @return string
     */
    abstract public function getQuery(): string;

    /**
     * @return string
     * @throws AttributeException
     * @throws InputTypeException
     * @throws TypeCastException
     */
    protected function buildWhere(): string
    {
        $chunks = [];

        foreach ($this->conditions as $value) {
            if (is_array($value)) {
                $chunks[] = '(';
                foreach ($value as $v) {
                    if ($v instanceof Condition) {
                        $chunks[] = sprintf(
                            "%s %s %s",
                            Type::cast(
                                $v->attribute->getPath(),
                                $v->attribute->attributeType
                            ),
                            $v->comprasionOperator,
                            $this->buildConditionInput($v)
                        );
                    } else {
                        $chunks[] = $v;
                    }
                }
                $chunks[] = ')';
            } else {
                $chunks[] = $value;
            }
        }

        return sizeof($chunks) ? sprintf('WHERE %s', join(' ', $chunks)) : '';
    }

    /**
     * @param Condition $condition
     * @return string
     * @throws AttributeException
     * @throws InputTypeException
     * @throws TypeCastException
     */
    private function buildConditionInput(Condition $condition): string
    {
        $placeholder = $condition->attribute->getPlaceholder(true);

        if (empty($this->filterAttributeValues[$placeholder])) {
            throw new AttributeException(
                sprintf('Missing value for attribute `%s` in %s', $condition->attribute, __METHOD__)
            );
        }

        $input = $this->filterAttributeValues[$placeholder];

        if ($input instanceof DataInput) {
            return sprintf(
                'ANY(SELECT %s FROM node_%d %s)',
                Type::cast($input->getSourceNodeColumn(), $condition->attribute->attributeType),
                $input->getSourceNodeId(),
                isset($this->dataInputLimits[$input->getSourceNodeId()]) ? sprintf('WHERE row_id IN (%s)', join(',', $this->dataInputLimits[$input->getSourceNodeId()])) : ''
            );
        }

        if ($input instanceof UserInput) {
            return Type::cast(
                sprintf("'%s'", $condition->attribute->getPlaceholder(true)),
                $condition->attribute->attributeType
            );
        }

        throw new InputTypeException(sprintf('Unknown input source `%s`', get_class($input)));
    }
}