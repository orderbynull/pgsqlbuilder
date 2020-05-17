<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Action;

use Orderbynull\PgSqlBuilder\Action\Pieces\EntityAttribute;
use Orderbynull\PgSqlBuilder\Action\Pieces\Join;
use Orderbynull\PgSqlBuilder\Action\Pieces\Summary;
use Orderbynull\PgSqlBuilder\Exceptions\AttributeException;
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
     * @var bool
     */
    private bool $groupingUsed = false;

    /**
     * @var array
     */
    private array $summarization = [];

    /**
     * @var bool
     */
    private bool $aggFunctionsUsed = false;

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

        !$this->groupingUsed && $this->groupingUsed = $summary->shouldGroup();
        !$this->aggFunctionsUsed && $this->aggFunctionsUsed = !empty($summary->getAggFuncName());

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
     * @throws AttributeException
     * @throws InputTypeException
     * @throws TypeCastException
     */
    public function getQuery(): string
    {
        $chunks = array_filter([
            'SELECT',
            $this->buildFields(),
            'FROM',
            'entity_values',
            sprintf('AS _%d', $this->baseEntityId),
            $this->buildJoins(),
            $this->buildWhere(),
            $this->buildGroupBy(),
            'LIMIT ALL'
        ]);

        return join(' ', $chunks);
    }

    /**
     * @return string
     * @throws AttributeException
     * @throws TypeCastException
     */
    private function buildFields(): string
    {
        $chunks = [];
        $timesSeen = [];

        /** @var EntityAttribute $attribute */
        foreach ($this->returningAttributes as $attribute) {
            isset($timesSeen[$attribute->getAttributeId()]) ? $timesSeen[$attribute->getAttributeId()]++ : $timesSeen[$attribute->getAttributeId()] = 1;

            $attributePath = Attribute::path($attribute->getEntityId(), $attribute->getAttributeId());
            $attributeAlias = Attribute::placeholder($attribute->getEntityId(), $attribute->getAttributeId());
            $attributeAlias = sprintf('%s_%d', $attributeAlias, $timesSeen[$attribute->getAttributeId()]);

            [$attributeUsedInGrouping, $attributeAggFunction] = $this->attributeSummaryMeta(
                $timesSeen[$attribute->getAttributeId()],
                $attribute->getEntityId(),
                $attribute->getAttributeId()
            );

            switch (true) {
                case $this->groupingUsed:
                    if (!$attributeUsedInGrouping && !$attributeAggFunction) {
                        throw new AttributeException(
                            sprintf(
                                'Attribute %d.%s must be either used in grouping or have aggregate function',
                                $attribute->getEntityId(),
                                $attribute->getAttributeId()
                            )
                        );
                    }
                    $fieldsDenseRank = [];
                    /** @var Summary $summary */
                    foreach ($this->summarization as $summary) {
                        if ($summary->shouldGroup() === true) {
                            $fieldsDenseRank[] = Type::cast(
                                Attribute::path($summary->getEntityId(), $summary->getAttributeId()),
                                $summary->getAttributeType()
                            );
                        }
                    }
                    $chunks[0] = sprintf('dense_rank() over (order by %s) AS row_id', join(', ', $fieldsDenseRank));
                    if ($attributeAggFunction) {
                        $chunks[] = sprintf('%s(%s) AS %s', $attributeAggFunction, Type::cast($attributePath, $attribute->getAttributeType()), $attributeAlias);
                    } else {
                        $chunks[] = sprintf('%s AS %s', Type::cast($attributePath, $attribute->getAttributeType()), $attributeAlias);
                    }
                    break;

                case !$this->groupingUsed && $this->aggFunctionsUsed:
                    if (!$attributeAggFunction) {
                        throw new AttributeException(
                            sprintf(
                                'Aggregate function must be set for attribute %d.%s',
                                $attribute->getEntityId(),
                                $attribute->getAttributeId()
                            )
                        );
                    }
                    $chunks[0] = '1 AS row_id';
                    $chunks[] = sprintf('%s(%s) AS %s', $attributeAggFunction, Type::cast($attributePath, $attribute->getAttributeType()), $attributeAlias);
                    break;

                case !$this->groupingUsed && !$this->aggFunctionsUsed:
                    $chunks[0] = sprintf('_%s.id AS row_id', $this->baseEntityId);
                    $chunks[] = sprintf('%s AS %s', Type::cast($attributePath, $attribute->getAttributeType()), $attributeAlias);
                    break;
            }
        }

        return join(', ', $chunks);
    }

    /**
     * @param int $index
     * @param int $entityId
     * @param string $attributeId
     * @return array
     */
    private function attributeSummaryMeta(int $index, int $entityId, string $attributeId): array
    {
        $data = [];

        /** @var Summary $summary */
        foreach ($this->summarization as $summary) {
            if ($summary->getEntityId() == $entityId && $summary->getAttributeId() == $attributeId) {
                $data[] = [$summary->shouldGroup(), $summary->getAggFuncName() ?? null];
            }
        }

        if (count($data) > 0) {
            return $data[$index - 1];
        }

        return [false, null];
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
                "JOIN entity_values AS _%d ON _%d.entity_id = %d AND (_%d.attributes->'%s'->>'value')::int = _%d.id",
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