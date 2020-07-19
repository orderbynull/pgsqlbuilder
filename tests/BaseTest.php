<?php

use PHPUnit\Framework\TestCase;

/**
 * Class BaseTest
 */
abstract class BaseTest extends TestCase
{
    /**
     * @param string $queryToTest
     * @return string
     * @throws Throwable
     */
    protected function jsonResult(string $queryToTest): string
    {
        $tmpDbName = $this->randomDbName();

        $mainConn = $this->dbConnect('postgres');
        $mainConn->exec(sprintf('CREATE DATABASE %s', $tmpDbName));

        try {
            $tmpConn = $this->dbConnect($tmpDbName);

            $upQuery = $this->up();
            if (!empty($upQuery) && $tmpConn->exec($upQuery) === false) {
                throw new \Exception(implode(', ', $tmpConn->errorInfo()));
            }

            $query = sprintf(
                "WITH source AS (%s) SELECT coalesce(jsonb_agg(source),'[]'::jsonb) AS fact FROM source",
                $queryToTest
            );

            $statement = $tmpConn->query($query);
            if ($statement === false) {
                throw new \Exception(implode(', ', $tmpConn->errorInfo()));
            }

            return $statement->fetch(PDO::FETCH_OBJ)->fact;
        } catch (\Throwable $throwable) {
            throw $throwable;
        } finally {
            $mainConn->exec(
                sprintf(
                    "SELECT pg_terminate_backend(pg_stat_activity.pid)
                     FROM pg_stat_activity
                     WHERE pg_stat_activity.datname = '%s'",
                    $tmpDbName
                )
            );
            $mainConn->exec(sprintf('DROP DATABASE %s', $tmpDbName));

            $tmpConn = null;
            $mainConn = null;
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    private function randomDbName(): string
    {
        return sprintf('_%d', random_int(1, PHP_INT_MAX));
    }

    /**
     * @param string $dbName
     * @return PDO
     */
    private function dbConnect(string $dbName): PDO
    {
        return new PDO(
            sprintf('pgsql:host=%s;port=%d;dbname=%s;', getenv('DB_HOST'), getenv('DB_PORT'), $dbName),
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
    }

    abstract protected function up(): string;
}