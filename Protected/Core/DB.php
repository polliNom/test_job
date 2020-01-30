<?php
namespace TreeDataManager\Core;

/**
 * Не классический синглтон для подключения к БД.
 * Просто храним одно подключение к БД и сразу возвращаем объект pdo, подключенный к нужной базе
 */
class DB {
    private static ?DB $instance = null;

    /** Свойство, хранящее подключение к БД */
    private \PDO $connection;

    /**
     * Подклчение к БД
     */
    public static function connection(): \PDO
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance->connection;
    }

    /**
     * В конструкторе создаём собственно подключение к БД
     */
    private function __construct()
    {
        $conf = require('Protected/Config/db.php'); // @todo Прямое указание путей в коде - плохо, нужно доработать
        $this->connection = new \PDO($conf['dsn'], $conf['user'], $conf['password']);
    }
}