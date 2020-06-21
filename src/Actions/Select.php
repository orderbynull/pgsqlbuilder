<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Actions;

use Orderbynull\PgSqlBuilder\Actions\Blocks\Join;
use Orderbynull\PgSqlBuilder\Exceptions\AttributeException;
use Orderbynull\PgSqlBuilder\Exceptions\InputTypeException;
use Orderbynull\PgSqlBuilder\Exceptions\TypeCastException;
use Orderbynull\PgSqlBuilder\Traits\ReturningAwareTrait;
use Orderbynull\PgSqlBuilder\Traits\WhereAwareTrait;
use Orderbynull\PgSqlBuilder\Utils\Type;

/**
 * Class Select
 * @package Orderbynull\PgSqlBuilder\Actions
 */
class Select extends AbstractAction
{
    use WhereAwareTrait;
    use ReturningAwareTrait;

    /**
     * @var array
     */
    private array $joins = [];

    /**
     * @param Join $join
     * @return $this|void
     */
    public function addJoin(Join $join): void
    {
        $this->joins[] = $join;
    }

    /**
     * @return string
     * @throws AttributeException
     * @throws InputTypeException
     * @throws TypeCastException
     */
    public function getSqlQuery(): string
    {
        $chunks = array_filter([
            'SELECT',
            $this->buildReturning(),
            'FROM',
            sprintf('entity_values AS _%d', $this->baseEntityId),
            $this->buildJoins(),
            $this->buildWhere($this->baseEntityId),
            $this->buildGroupBy(),
            'LIMIT ALL'
        ]);

        return join(' ', $chunks);
    }

    /**
     * @return string
     */
    private function buildJoins(): string
    {
        $chunks = [];

        /** @var Join $join */
        foreach ($this->joins as $join) {
            $chunks[] = sprintf(
                "LEFT JOIN entity_values AS _%d ON _%d.entity_id = %d AND (_%d.attributes->'%s'->>'value')::int = _%d.id",
                $join->joinedEntityId,
                $join->masterEntityId,
                $join->masterEntityId,
                $join->masterEntityId,
                $join->joinAttributeId,
                $join->joinedEntityId,
            );
        }

        return join(' ', $chunks);
    }

    /**
     * @return string
     * @throws TypeCastException
     */
    private function buildGroupBy(): string
    {
        $chunks = [];

        foreach ($this->getSummary() as $summary) {
            if (!$summary->shouldGroup) {
                continue;
            }

            $chunks[] = Type::cast(
                $summary->attribute->getValue(),
                $summary->attribute->attributeType
            );
        }

        return count($chunks) ? sprintf('GROUP BY %s', join(',', $chunks)) : '';
    }

    /**
     * @inheritDoc
     */
    public function getUserInputBindings(): array
    {
        return $this->conditionsUserInputs;
    }

    /**
     * @return array
     */
    public function getResultColumnsMeta(): array
    {
        return $this->getReturningColumnsMeta();
    }
}