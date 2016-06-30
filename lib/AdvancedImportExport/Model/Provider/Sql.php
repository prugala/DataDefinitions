<?php

namespace AdvancedImportExport\Model\Provider;

use AdvancedImportExport\Model\AbstractProvider;
use AdvancedImportExport\Model\Definition;
use AdvancedImportExport\Model\Mapping\FromColumn;
use Pimcore\Model\Object\Concrete;

/**
 * CSV Import/Export Provider
 *
 * Class CSV
 * @package AdvancedImportExport\Provider
 */
class Sql extends AbstractProvider {

    /**
     * @var string
     */
    public $host;

    /**
     * @var string
     */
    public $username;

    /**
     * @var string
     */
    public $password;

    /**
     * @var string
     */
    public $database;

    /**
     * @var string
     */
    public $adapter;

    /**
     * @var string
     */
    public $port;

    /**
     * @var string
     */
    public $query;

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @param string $database
     */
    public function setDatabase($database)
    {
        $this->database = $database;
    }

    /**
     * @return string
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @param string $adapter
     */
    public function setAdapter($adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param string $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param string $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * Test Data provided for this Provider
     *
     * @return boolean
     * @throws \Exception
     */
    public function testData() {
        return is_object($this->getDb());
    }

    /**
     * @return \Zend_Db_Adapter_Abstract
     * @throws \Exception
     * @returns \Zend_Db
     */
    protected function getDb() {
        $config = [
            "username" => $this->getUsername(),
            "password" => $this->getPassword(),
            "dbname" => $this->getDatabase(),
            "host" => $this->getHost(),
            "port" => $this->getPort()
        ];
        try {
            $db = \Zend_Db::factory($this->getAdapter(), $config);
            $db->query("SET NAMES UTF8");

            return $db;
        } catch (\Exception $e) {
            \Logger::emerg($e);

            throw $e;
        }
    }

    /**
     * Get Columns from data
     *
     * @return FromColumn[]
     */
    public function getColumns()
    {
        $db = $this->getDb();
        $query = $db->query($this->getQuery());
        $data = $query->fetchAll(\Zend_Db::FETCH_CLASS);
        $columns = [];
        $returnColumns = [];

        if (isset($data[0])) {
            // there is at least one row - we can grab columns from it
            $columns = array_keys((array)$data[0]);
        }
        else {
            // there are no results - no need to use PDO functions
            $nr = $query->columnCount();
            for ($i = 0; $i < $nr; ++$i) {
                $columns[] = $query->getColumnMeta($i)['name'];
            }
        }

        foreach($columns as $col) {
            $returnCol = new FromColumn();
            $returnCol->setIdentifier($col);
            $returnCol->setLabel($col);

            $returnColumns[] = $returnCol;
        }

        return $returnColumns;
    }

    /**
     * @param Definition $definition
     * @param $params
     * @return Concrete[]
     */
    protected function runImport($definition, $params)
    {
        $db = $this->getDb();

        $data = $db->fetchAll($this->getQuery());

        $objects = [];

        foreach($data as $row) {
            $objects[] = $this->importRow($definition, $row);
        }

        return $objects;
    }

    /**
     * @param Definition $definition
     * @param $data
     *
     * @return Concrete
     */
    private function importRow($definition, $data) {
        $object = $this->getObjectForPrimaryKey($definition, $data);

        foreach($definition->getMapping() as $map) {
            $value = $data[$map->getFromColumn()];

            $this->setObjectValue($object, $map, $value, $data);
        }

        $object->save();

        return $object;
    }
}