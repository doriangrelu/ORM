<?php
/**
 * Created by PhpStorm.
 * User: doria
 * Date: 14/09/2018
 * Time: 14:43
 */

namespace Dorian\ORM\Query;


use Cake\Utility\Inflector;
use Dorian\ORM\Repository;

class Query
{

    private $_table = null;
    private $_id = null;
    private $_contains = [];
    private $_conditions = [];
    private $_fields = [];
    /**
     * \PDO
     */
    private $_connexion;

    /**
     * Query constructor.
     * @param Repository $repository
     * @internal param $table
     * @internal param null $id
     * @internal param null $_table
     */
    public function __construct(Repository $repository)
    {
        $this->_table = $repository->getTable();
        $this->_id = $repository->getId();
    }


    /**
     * @param string[] ...$fields
     * @return Query
     */
    public function select(string ... $fields): self
    {
        $this->_fields += array_map(function ($value) {
            if (!$this->_haveAlreadyAlias($value)) {
                return $this->_table . '.' . mb_strtolower($value);
            }
            return $value;

        }, $fields);
        return $this;
    }

    private function _haveAlreadyAlias($fieldName)
    {
        return strpos($fieldName, '.') !== false;
    }

    /**
     * @param array $conditions
     * @return $this
     */
    public function where(array $conditions)
    {
        $this->_conditions += $conditions;
        return $this;
    }

    /**
     * @param string[] ...$tables
     * @return Query
     */
    public function contain(string ... $tables): self
    {
        $this->_contains += $tables;
        return $this;
    }

    public function first()
    {

    }

    public function firstOrFail()
    {

    }


    public function toArray()
    {

    }

    private function _getSelectedTables()
    {
        $selectedTables = [
            $this->_table
        ];

        foreach ($this->_contains as $tables) {
            $tablesList = explode('.', $tables);
            $tablesList = array_filter($tablesList, function ($value) use ($selectedTables) {
                if (!in_array($value, $selectedTables)) {
                    return true;
                }
                return false;
            });
            $selectedTables = array_merge($selectedTables, $tablesList);
        }
        return $selectedTables;
    }

    /**
     * @return string
     */
    private function _getSelect()
    {
        if (empty($this->_fields)) {
            return '*';
        }

        $fields = array_map(function ($value) {
            if ($this->_haveAlreadyAlias($value)) {
                return $value;
            }
            return $this->_table . '.' . mb_strtolower($value);
        }, $this->_fields);

        return implode(', ', $fields);
    }

    private function _getRealTableName()
    {
        return mb_strtolower(Inflector::underscore($this->_table));
    }

    private function _join($contain)
    {
        $statment = "";
        $from = $this->_table;
        $tableList = array_reverse(explode('.', $contain));

        while (!empty($tableList)) {
            $to = array_pop($tableList);
            $statment .= " LEFT JOIN $to ON $from";
            $from=$to;
        }
        return $statment;
    }

    private function _getJoinsIdConditions(string $from, string $to)
    {

    }

    public function __toString()
    {
        $statment = "SELECT {$this->_getSelect()} FROM {$this->_getRealTableName()} AS {$this->_table}";

        foreach ($this->_contains as $contain) {
            var_dump($this->_join($contain));
            die('here');
        }

        return $statment;
    }

}