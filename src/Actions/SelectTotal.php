<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Actions;

use Orderbynull\PgSqlBuilder\Actions\Blocks\EntityAttribute;
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
class SelectTotal extends Select
{
    /**
     * @return string
     * @throws AttributeException
     * @throws InputTypeException
     * @throws TypeCastException
     */
    public function getSqlQuery(): string
    {
        $returning = $this->buildReturning();
        $where = $this->buildWhere($this->baseEntityId);

        if (!empty($this->searchString)) {
            $condition = $this->buildSearchCondition($this->getResultColumnsMeta(), $this->searchString);

            if ($condition) {
                $where .= ' AND ' . $condition;
            }
        }

        $sql = $this->createSqlQuery(
            [
                'SELECT',
                sprintf('COUNT(_%d.id) as total', $this->baseEntityId),
                'FROM',
                sprintf('entity_values AS _%d', $this->baseEntityId),
                $this->buildJoins(),
                $where,
                $this->buildGroupBy(),
            ]
        );

        return $this->prepareSqlQuery($sql);
    }

}
