<?php

declare(strict_types=1);

namespace Orderbynull\PgSqlBuilder\Action\Pieces;

/**
 * Class Join
 * @package Orderbynull\PgSqlBuilder\Action\Pieces
 */
class Join
{
    /**
     * @var int
     */
    private int $masterEntityId;

    /**
     * @var int
     */
    private int $joinedEntityId;

    /**
     * @var string
     */
    private string $joinAttributeId;

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

    /**
     * @return int
     */
    public function getMasterEntityId(): int
    {
        return $this->masterEntityId;
    }

    /**
     * @return int
     */
    public function getJoinedEntityId(): int
    {
        return $this->joinedEntityId;
    }

    /**
     * @return string
     */
    public function getJoinAttributeId(): string
    {
        return $this->joinAttributeId;
    }
}