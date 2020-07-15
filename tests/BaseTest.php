<?php

use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    /**
     * @param string $dbName
     * @return PDO
     */
    private function dbConnect(string $dbName): PDO
    {
        return new PDO(sprintf('pgsql:host=%s;port=%d;dbname=%s;', '127.0.0.1', 5432, $dbName), 'pgsql', 'pgsql');
    }

    /**
     * @return string
     * @throws Exception
     */
    private function randomDbName(): string
    {
        return sprintf('_%d', random_int(1, PHP_INT_MAX));
    }

    abstract protected function up(): void;

    /**
     * @param string $queryToTest
     * @param string $expectedJson
     * @return bool
     * @throws Throwable
     */
    protected function equal(string $queryToTest, string $expectedJson): bool
    {
        $tmpDbName = $this->randomDbName();

        $mainConn = $this->dbConnect('postgres');
        $mainConn->exec(sprintf('CREATE DATABASE %s', $tmpDbName));

        try {
            $tmpConn = $this->dbConnect($tmpDbName);

            $query = sprintf(
                "WITH source AS (%s) SELECT jsonb_agg(source) = '%s'::jsonb FROM source",
                $queryToTest,
                $expectedJson
            );

            return $tmpConn->query($query)->fetchColumn();
        } catch (\Throwable $throwable) {
            throw $throwable;
        } finally {
            $tmpConn = null;

            $mainConn->exec(sprintf('DROP DATABASE %s', $tmpDbName));

            $mainConn = null;
        }
    }
}