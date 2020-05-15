<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Action;

use Orderbynull\PgSqlBuilder\Action\Pieces\EntityAttribute;
use Orderbynull\PgSqlBuilder\Action\Pieces\Join;
use Orderbynull\PgSqlBuilder\Action\Pieces\Summary;
use Orderbynull\PgSqlBuilder\Exceptions\InputTypeException;
use Orderbynull\PgSqlBuilder\Exceptions\TypeCastException;
use Orderbynull\PgSqlBuilder\Utils\Attribute;
use Orderbynull\PgSqlBuilder\Utils\Type;

/**
 * Class Select
 * @package Orderbynull\PgSqlBuilder
 */
class Select extends Action
{
    /**
     * @var array
     */
    private array $joins = [];

    /**
     * @var array
     */
    private array $summarization = [];

    /**
     * @var array
     */
    private array $returningAttributes = [];

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
     * @param Join $join
     * @return $this
     */
    public function addJoin(Join $join): self
    {
        $this->joins[] = $join;

        return $this;
    }

    /**
     * @return string
     * @throws InputTypeException
     * @throws TypeCastException
     */
    public function getQuery(): string
    {
        $chunks = array_filter([
            sprintf('SELECT * FROM entity_values AS _%d', $this->baseEntityId),
            $this->buildJoins(),
            $this->buildWhere(),
            $this->buildGroupBy(),
            'ORDER BY id DESC',
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
                "JOIN entity_values AS _%d ON _%d.entity_id = %d AND (_%d.attributes->'%s'->>'value')::int = %s.id",
                $join->getJoinedEntityId(),
                $join->getMasterEntityId(),
                $join->getMasterEntityId(),
                $join->getMasterEntityId(),
                $join->getJoinAttributeId(),
                $join->getJoinedEntityId(),
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

        /** @var Summary $summary */
        foreach ($this->summarization as $summary) {
            if (!$summary->shouldGroup()) {
                continue;
            }

            $chunks[] = Type::cast(
                Attribute::path(
                    $summary->getEntityId(), $summary->getAttributeId()
                ),
                $summary->getAttributeType()
            );
        }

        return count($chunks) ? sprintf('GROUP BY %s', join(',', $chunks)) : '';
    }
}