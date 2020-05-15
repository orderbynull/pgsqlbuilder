<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder;

use Orderbynull\PgSqlBuilder\Input\DataInput;
use Orderbynull\PgSqlBuilder\Input\InputInterface;
use Orderbynull\PgSqlBuilder\Input\UserInput;

/**
 * Class Select
 * @package Orderbynull\PgSqlBuilder
 */
class Select
{
    /**
     * @var int
     */
    private int $baseEntityId;

    /**
     * @var array
     */
    private array $filtrationRules = [];

    /**
     * @var array
     */
    private array $groupOfRules = [];

    /**
     * @var array
     */
    private array $summarization = [];

    /**
     * @var array
     */
    private array $withNodeResults = [];

    /**
     * @var array
     */
    private array $userInputBindings = [];

    /**
     * @var array
     */
    private array $returningAttributes = [];

    /**
     * @param int $baseEntityId
     * @return $this
     */
    public function fromEntity(int $baseEntityId): self
    {
        $this->baseEntityId = $baseEntityId;
        return $this;
    }

    /**
     * @param EntityAttribute $attribute
     * @return $this
     */
    public function addReturningAttribute(EntityAttribute $attribute): self
    {
        $this->returningAttributes[] = $attribute;

        return $this;
    }

    /**
     * @param Summary $summary
     * @return $this
     */
    public function addSummary(Summary $summary): self
    {
        $this->summarization[] = $summary;

        return $this;
    }

    /**
     * @param int $nodeId
     * @return $this
     */
    public function withNodeResults(int $nodeId): self
    {
        $this->withNodeResults[] = $nodeId;

        return $this;
    }

    private function placeholder(int $entityId, string $attributeId): string
    {
        return str_replace('-', '_', sprintf('ent_%d_attr_%s', $entityId, $attributeId));
    }

    /**
     * @param InputInterface $input
     * @return mixed|string
     * @throws \Exception
     */
    private function buildInput(FiltrationRule $rule)
    {
        $input = $rule->getInputSource();

        if ($input instanceof DataInput) {
            return sprintf(
                'ANY(SELECT %s FROM node_%d)',
                $this->cast($input->getSourceNodeColId(), $rule->getAttributeType()),
                $input->getSourceNodeId()
            );
        }

        if ($input instanceof UserInput) {
            return $this->cast(
                ':' . $this->placeholder($rule->getEntityId(), $rule->getAttributeId()),
                $rule->getAttributeType()
            );
        }

        throw new \Exception('Unknown input type');
    }

    private function cast(string $value, string $type): string
    {
        switch ($type) {
            case 'string':
                return sprintf('(%s)::text', $value);
            case 'integer':
                return sprintf('(%s)::int', $value);
        }
    }

    public function bindings(): array
    {
        return $this->userInputBindings;
    }

    /**
     * @return string
     */
    private function buildWhere(): string
    {
        $chunks = [];

        foreach ($this->filtrationRules as $value) {
            if (is_array($value)) {
                $chunks[] = '(';
                foreach ($value as $v) {
                    if ($v instanceof FiltrationRule) {
                        $chunks[] = sprintf(
                            "%s %s %s",
                            $this->cast(
                                sprintf("_%d.attributes->'%s'->>'value'", $v->getEntityId(), $v->getAttributeId()),
                                $v->getAttributeType()
                            ),
                            $v->getComprasionOperator(),
                            $this->buildInput($v)
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
     * @return string
     */
    private function buildGroupBy(): string
    {
        return '';
    }

    private function buildJoins(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function query(): string
    {
        !empty($this->groupOfRules) && $this->filtrationRules[] = $this->groupOfRules;

        $chunks = array_filter([
            sprintf('SELECT * FROM entity_values AS _%d', $this->baseEntityId),
            $this->buildWhere(),
            $this->buildJoins(),
            $this->buildGroupBy(),
            'ORDER BY id DESC',
            'LIMIT ALL'
        ]);

        return join(' ', $chunks);
    }

    public function newFiltrationGroup(?string $operator = null): self
    {
        if (empty($this->groupOfRules)) {
            return $this;
        }

        $this->filtrationRules[] = $this->groupOfRules;

        if ($operator) {
            $this->filtrationRules[] = $operator;
        }

        $this->groupOfRules = [];

        return $this;
    }

    public function addFiltrationRule(?string $operator, FiltrationRule $rule): self
    {
        // AND/OR не может быть первым в группе условий
        if ($operator && !end($this->groupOfRules) instanceof FiltrationRule) {
            throw new \Exception('AND\OR могут следовать только после условия');
        }

        $operator && $this->groupOfRules[] = $operator;

        // Правила фильтрации не могут идти подряд без разделения AND/OR
        if (end($this->groupOfRules) instanceof FiltrationRule) {
            throw new \Exception('Правило фильтрации может следовать только после AND\OR');
        }

        $this->groupOfRules[] = $rule;

        if ($rule->getInputSource() instanceof UserInput) {
            $placeholder = $this->placeholder($rule->getEntityId(), $rule->getAttributeId());
            $this->userInputBindings[$placeholder] = $rule->getInputSource()->getValue();
        }

        return $this;
    }
}