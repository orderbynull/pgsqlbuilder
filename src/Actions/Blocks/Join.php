<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Actions\Blocks;

/**
 * Class Join
 * @package Orderbynull\PgSqlBuilder\Actions\Blocks
 */
class Join
{
    /**
     * @var int
     */
    public int $masterEntityId;

    /**
     * @var int
     */
    public int $joinedEntityId;

    /**
     * @var string
     */
    public string $joinAttributeId;

    /**
     * Join constructor.
     * @param int $masterEntityId
     * @param int $joinedEntityId
     * @param string $joinAttributeId
     */
    public function __construct(int $masterEntityId, int $joinedEntityId, string $joinAttributeId)
    {
        $this->masterEntityId = $masterEntityId;
        $this->joinedEntityId = $joinedEntityId;
        $this->joinAttributeId = $joinAttributeId;
    }
}