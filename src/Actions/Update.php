<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Actions;

/**
 * Class Update
 * @package Orderbynull\PgSqlBuilder\Actions
 */
class Update extends AbstractAction
{
    public function getQuery(): string
    {
        return '';
    }

    public function getUserInputBindings(): array
    {
        return [];
    }
}