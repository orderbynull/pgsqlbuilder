<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Traits;

use Orderbynull\PgSqlBuilder\Actions\Blocks\EntityAttribute;
use Orderbynull\PgSqlBuilder\Actions\Blocks\ResultColumnMeta;
use Orderbynull\PgSqlBuilder\Actions\Blocks\Summary;
use Orderbynull\PgSqlBuilder\Exceptions\AttributeException;
use Orderbynull\PgSqlBuilder\Exceptions\TypeCastException;
use Orderbynull\PgSqlBuilder\Utils\Type;

/**
 * Trait ReturningAwareTrait
 * @package Orderbynull\PgSqlBuilder\Traits
 */
trait ReturningAwareTrait
{
    /**
     * @var array
     */
    private array $summarization = [];

    /**
     * @var bool
     */
    private bool $groupingUsed = false;

    /**
     * @var bool
     */
    private bool $aggFunctionsUsed = false;

    /**
     * @var array
     */
    private array $attributesToReturn = [];

    /**
     * @var array
     */
    private array $returningColumnsMeta = [];

    /**
     * @return Summary[]
     */
    private function getSummary(): array
    {
        return $this->summarization;
    }

    /**
     * @param int $index
     * @param int $entityId
     * @param string $attributeId
     * @return array
     */
    private function getAttributeSummaryMeta(int $index, int $entityId, string $attributeId): array
    {
        $data = [];

        /** @var Summary $summary */
        foreach ($this->summarization as $summary) {
            if ($summary->attribute->entityId == $entityId && $summary->attribute->attributeId == $attributeId) {
                $data[] = [$summary->shouldGroup, $summary->aggFuncName ?? null];
            }
        }

        if (count($data) > 0) {
            return $data[$index - 1];
        }

        return [false, null];
    }

    /**
     * @param ResultColumnMeta $columnMeta
     */
    private function registerReturningAttribute(ResultColumnMeta $columnMeta): void
    {
        $this->returningColumnsMeta[(string)$columnMeta] = $columnMeta;
    }

    /**
     * @return string
     * @throws AttributeException
     * @throws \Orderbynull\PgSqlBuilder\Exceptions\TypeCastException
     */
    protected function buildReturning(): string
    {
        $chunks = [];
        $timesSeen = [];

        /** @var EntityAttribute $attribute */
        foreach ($this->attributesToReturn as $attribute) {
            isset($timesSeen[$attribute->attributeId]) ? $timesSeen[$attribute->attributeId]++ : $timesSeen[$attribute->attributeId] = 1;

            $attributeAlias = $attribute->getPlaceholder();
            $attributeAlias = sprintf('%s_%d', $attributeAlias, $timesSeen[$attribute->attributeId]);

            [$attributeUsedInGrouping, $attributeAggFunction] = $this->getAttributeSummaryMeta(
                $timesSeen[$attribute->attributeId],
                $attribute->entityId,
                $attribute->attributeId
            );

            switch (true) {
                case $this->groupingUsed:
                    if (!$attributeUsedInGrouping && !$attributeAggFunction) {
                        throw new AttributeException(
                            sprintf(
                                'Attribute %d.%s must be either used in grouping or have aggregate function',
                                $attribute->entityId,
                                $attribute->attributeId
                            )
                        );
                    }
                    $fieldsDenseRank = [];
                    /** @var Summary $summary */
                    foreach ($this->summarization as $summary) {
                        if ($summary->shouldGroup === true) {
                            $fieldsDenseRank[] = Type::cast(
                                $summary->attribute->getValue(),
                                $summary->attribute->attributeType
                            );
                        }
                    }
                    $chunks[0] = sprintf('dense_rank() over (order by %s) AS row_id', join(', ', $fieldsDenseRank));
                    if ($attributeAggFunction) {
                        $chunks[] = sprintf('%s(%s) AS %s', $attributeAggFunction, $this->columnExpression($attribute), $attributeAlias);
                        $this->registerReturningAttribute(new ResultColumnMeta($attributeAlias, $attribute, $attributeAggFunction));
                    } else {
                        $chunks[] = sprintf('%s AS %s', $this->columnExpression($attribute), $attributeAlias);
                        $this->registerReturningAttribute(new ResultColumnMeta($attributeAlias, $attribute));
                    }
                    break;

                case !$this->groupingUsed && $this->aggFunctionsUsed:
                    if (!$attributeAggFunction) {
                        throw new AttributeException(
                            sprintf(
                                'Aggregate function must be set for attribute %d.%s',
                                $attribute->entityId,
                                $attribute->attributeId
                            )
                        );
                    }
                    $chunks[0] = '1 AS row_id';
                    $chunks[] = sprintf('%s(%s) AS %s', $attributeAggFunction, $this->columnExpression($attribute), $attributeAlias);
                    $this->registerReturningAttribute(new ResultColumnMeta($attributeAlias, $attribute, $attributeAggFunction));
                    break;

                case !$this->groupingUsed && !$this->aggFunctionsUsed:
                    $chunks[0] = sprintf('_%s.id AS row_id', $this->baseEntityId);
                    $chunks[] = sprintf('%s AS %s', $this->columnExpression($attribute), $attributeAlias);
                    $this->registerReturningAttribute(new ResultColumnMeta($attributeAlias, $attribute));
                    break;
            }
        }

        if (empty($chunks)) {
            $chunks[] = sprintf('_%d.id as row_id', $this->baseEntityId);
        }

        return join(', ', $chunks);
    }

    /**
     * @param EntityAttribute $attribute
     * @return string
     * @throws TypeCastException
     */
    private function columnExpression(EntityAttribute $attribute): string
    {
        $dateTimeFormat = 'DD Mon YYYY HH24:MI';

        // Вместо row_id показываем список аттрибутов этого row_id
        if ($attribute->attributeType === Type::FOREIGN_KEY) {
            $rowId = Type::cast($attribute->getValue(), $attribute->attributeType);

            return sprintf(
                <<<RAW
                (
                    WITH row_attributes_meta AS (
                            SELECT 
                                value->>'id' AS id, 
                                value->>'type' AS type 
                            FROM entities, 
                                 jsonb_array_elements(attributes) 
                            WHERE id IN (SELECT entity_id FROM entity_values WHERE id = %s)
                         ),
                         row_attributes_values AS (
                            SELECT 
                                key AS id, 
                                value->>'value' AS value 
                            FROM 
                                entity_values, 
                                jsonb_each(attributes) 
                            WHERE id = %s
                         ),
                         row_attributes_full AS (
                            SELECT id, 
                            type, 
                            CASE WHEN type = 'date_time' THEN to_char(value::timestamptz, '%s') ELSE value END 
                            FROM row_attributes_meta 
                            JOIN row_attributes_values USING (id)
                            WHERE id IN (
                                -- Возвращает строки с id аттрибутов, к которым у FK аттрибута есть доступ
                                SELECT jsonb_array_elements_text((value->>'attributesIds')::jsonb)
                                FROM 
                                    entities, 
                                    jsonb_array_elements(attributes)
                                WHERE id = %d AND value->>'id' = '%s'
                            )
                         )

                    -- Для строки возвращает ее аттрибуты в виде attr1;attr2;attr3
                    SELECT array_to_string(array_agg(value), ', ', '-') FROM row_attributes_full
                )
                RAW,
                $rowId,
                $rowId,
                $dateTimeFormat,
                $attribute->entityId,
                $attribute->attributeId
            );
        }

        // Вместо массива id для аттрибута-файла выбираем мета-информацию о каждом файле и возвращаем результат
        // как аггрегированную json-строку, которая уже может быть десереализована при обработке результата
        if ($attribute->attributeType === Type::FILE) {
            return sprintf(
                <<<RAW
                (
                    WITH attribute_files_ids AS (
                            SELECT jsonb_array_elements_text(coalesce(%s, '[]')::jsonb)::int AS id
                         ),
                        fields_to_aggregate AS (
                            SELECT f.id, 
                                   f.name, 
                                   f.mimetype, 
                                   f.size, 
                                   f.created_at AS "createdAt", 
                                   f.updated_at AS "updatedAt"
                            FROM attribute_files_ids
                            JOIN files f USING (id)
                        )
                    SELECT coalesce(jsonb_agg(fields_to_aggregate), '[]') AS agg FROM fields_to_aggregate
                )
                RAW,
                $attribute->getValue()
            );
        }

        // Форматирование даты силами БД
        if ($attribute->attributeType === Type::DATETIME) {
            return sprintf("to_char((%s)::timestamptz, '%s')", $attribute->getValue(), $dateTimeFormat);
        }

        return Type::cast($attribute->getValue(), $attribute->attributeType);
    }

    /**
     * @return array
     */
    private function getReturningColumnsMeta(): array
    {
        return array_values($this->returningColumnsMeta);
    }

    /**
     * @param EntityAttribute $attribute
     */
    public function addAttributeToReturn(EntityAttribute $attribute): void
    {
        $this->attributesToReturn[] = $attribute;
    }

    /**
     * @param Summary $summary
     */
    public function addSummary(Summary $summary): void
    {
        $this->summarization[] = $summary;

        !$this->groupingUsed && $this->groupingUsed = $summary->shouldGroup;
        !$this->aggFunctionsUsed && $this->aggFunctionsUsed = !empty($summary->aggFuncName);
    }
}