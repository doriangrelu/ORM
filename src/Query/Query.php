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
use Psr\Container\ContainerInterface;

class Query
{

    private $_table = null;
    private $_id = null;
    private $_contains = [];
    private $_conditions = [];
    private $_fields = [];
    private $_reposiotryNamespace;
    private $_entityNamespace;
    /**
     * \PDO
     */
    private $_connexion;

    /**
     * @var ContainerInterface
     */
    private $_container;
    /**
     * @var ReflexionTable|mixed
     */
    private $_reflexion;

    /**
     * Query constructor.
     * @param Repository $repository
     * @param ContainerInterface $container
     * @internal param $table
     * @internal param null $id
     * @internal param null $_table
     */
    public function __construct(Repository $repository, ContainerInterface $container)
    {
        $this->_table = $repository->getTable();
        $this->_id = $repository->getId();
        $this->_entityNamespace = $repository->getEntityNamespace();
        $this->_reposiotryNamespace = $repository->getRepositoryNamespace();
        $this->_container = $container;
        $this->_connexion = $container->get(\PDO::class);
        $this->_reflexion = $container->get(ReflexionTable::class);
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
            $select = [];
            $reflexion = $this->_reflexion;
            foreach (array_merge($this->_contains, [$this->_table]) as $contain) {
                $select = array_merge($select, array_map(function ($value) use ($reflexion) {
                    return $reflexion->showColumns($value);
                }, explode('.', $contain)));
            }
            return implode(', ', $select);
        }

        $fields = array_map(function ($value) {
            if ($this->_haveAlreadyAlias($value)) {
                return $value;
            }
            return $this->_table . '.' . mb_strtolower($value);
        }, $this->_fields);

        return implode(', ', $fields);
    }

    private function _getRealTableName($table = null)
    {
        if (is_null($table)) {
            $table = $this->_table;
        }
        return mb_strtolower(Inflector::underscore($table));
    }

    private function _join($contain)
    {
        $statment = "";
        $from = $this->_table;
        $tableList = array_reverse(explode('.', $contain));

        while (!empty($tableList)) {
            $to = array_pop($tableList);

            $toTableName = $this->_getRealTableName($to);
            $statment .= " LEFT JOIN $toTableName AS $to ON {$this->_getJoinsIdConditions($from, $to)}";
            $from = $to;
        }
        return $statment;
    }

    private function _getJoinsIdConditions(string $from, string $to)
    {
        $fromInstance = $this->_container->get($this->_reposiotryNamespace . $from . 'Repository');
        return $fromInstance->getAssociation($to)->getJoinCondition();
    }

    public function __toString()
    {
        $statment = "SELECT {$this->_getSelect()} FROM {$this->_getRealTableName()} AS {$this->_table}";

        $joins = [];
        foreach ($this->_contains as $contain) {
            $joins[] = $this->_join($contain);

        }

        $statment .= implode(' ', $joins);
        return $statment;
    }

}