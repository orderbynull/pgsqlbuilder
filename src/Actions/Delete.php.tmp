<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Actions;

use Orderbynull\PgSqlBuilder\Traits\ReturningAwareTrait;
use Orderbynull\PgSqlBuilder\Traits\WhereAwareTrait;

/**
 * Class Delete
 * @package Orderbynull\PgSqlBuilder\Actions
 */
class Delete extends AbstractAction
{
    use WhereAwareTrait;
    use ReturningAwareTrait;

    /**
     * @return array
     */
    public function getUserInputBindings(): array
    {
        return $this->conditionsUserInputs;
    }

    /**
     * @return string
     * @throws \Orderbynull\PgSqlBuilder\Exceptions\AttributeException
     * @throws \Orderbynull\PgSqlBuilder\Exceptions\InputTypeException
     * @throws \Orderbynull\PgSqlBuilder\Exceptions\TypeCastException
     */
    public function getSqlQuery(): string
    {
        $queryChunks = [
            'UPDATE',
            sprintf('entity_values AS _%d', $this->baseEntityId),
            'SET',
            'deleted_at = NOW()',
            $this->buildWhere($this->baseEntityId),
            $this->buildReturning()
        ];

        return trim(join(' ', $queryChunks));
    }

    /**
     * @inheritDoc
     */
    public function getResultColumnsMeta(): array
    {
        return [];
    }
}