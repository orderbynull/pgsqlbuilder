<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Traits;

use Orderbynull\PgSqlBuilder\Actions\Blocks\Condition;
use Orderbynull\PgSqlBuilder\Actions\Blocks\EntityAttribute;
use Orderbynull\PgSqlBuilder\Exceptions\AttributeException;
use Orderbynull\PgSqlBuilder\Exceptions\FiltrationException;
use Orderbynull\PgSqlBuilder\Exceptions\InputTypeException;
use Orderbynull\PgSqlBuilder\Exceptions\TypeCastException;
use Orderbynull\PgSqlBuilder\Input\DataInput;
use Orderbynull\PgSqlBuilder\Input\InputInterface;
use Orderbynull\PgSqlBuilder\Input\UserInput;
use Orderbynull\PgSqlBuilder\Utils\Type;

/**
 * Trait WhereAwareTrait
 * @package Orderbynull\PgSqlBuilder\Traits
 */
trait WhereAwareTrait
{
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
    private array $conditions = [];

    /**
     * @var array
     */
    private array $groupOfRules = [];

    /**
     * @var array
     */
    private array $attributesValues = [];

    /**
     * @var array
     */
    protected array $conditionsUserInputs = [];

    /**
     * @param EntityAttribute $attribute
     * @param UserInput $userInput
     */
    private function registerUserInput(EntityAttribute $attribute, UserInput $userInput): void
    {
        $this->conditionsUserInputs[$attribute->getPlaceholder(true)] = $userInput->value;
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
    public function setConditionAttributeValue(EntityAttribute $attribute, InputInterface $input): void
    {
        if ($input instanceof UserInput) {
            $this->registerUserInput($attribute, $input);
        }

        $this->attributesValues[$attribute->getPlaceholder(true)] = $input;
    }

    /**
     * @param int $baseEntityId
     * @return string
     * @throws AttributeException
     * @throws InputTypeException
     * @throws TypeCastException
     */
    protected function buildWhere(int $baseEntityId): string
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
                            $this->conditionToSql($v)
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

        if (count($chunks)) {
            return sprintf('WHERE _%d.entity_id = %d AND (%s)', $baseEntityId, $baseEntityId, join(' ', $chunks));
        }

        return sprintf('WHERE _%d.entity_id = %d', $baseEntityId, $baseEntityId);
    }

    /**
     * @param Condition $condition
     * @return string
     * @throws AttributeException
     * @throws InputTypeException
     * @throws TypeCastException
     */
    private function conditionToSql(Condition $condition): string
    {
        $placeholder = $condition->attribute->getPlaceholder(true);

        if (empty($this->attributesValues[$placeholder])) {
            throw new AttributeException(
                sprintf('Missing value for attribute `%s` in %s', $condition->attribute, __METHOD__)
            );
        }

        $input = $this->attributesValues[$placeholder];

        if ($input instanceof DataInput) {
            return sprintf(
                'ANY(SELECT %s FROM node_%d %s)',
                Type::cast($input->sourceNodeColumn, $condition->attribute->attributeType),
                $input->sourceNodeId,
                isset($this->dataInputLimits[$input->sourceNodeId]) ? sprintf('WHERE row_id IN (%s)', join(',', $this->dataInputLimits[$input->sourceNodeId])) : ''
            );
        }

        if ($input instanceof UserInput) {
            return Type::cast(
                sprintf("'%s'", $condition->attribute->getPlaceholder(true)),
                $condition->attribute->attributeType
            );
        }

        throw new InputTypeException(sprintf('Unknown input source `%s` in %s', get_class($input), __METHOD__));
    }
}