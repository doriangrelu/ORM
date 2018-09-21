<?php
/**
 * Created by PhpStorm.
 * User: doria
 * Date: 14/09/2018
 * Time: 14:43
 */

namespace Dorian\ORM\Query;


use Cake\Utility\Inflector;
use Dorian\ORM\Exception\BadConditionException;
use Dorian\ORM\Exception\RecordNotFoundException;
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
    private $_entityContains = [];
    private $_bindedParams = [];
    private $_joinedTables = [];
    private $_order = [];
    private $_operators = ['<', '>', '<>', 'LIKE', 'IN', 'NOT IN', 'BETWEEN', '!=', '<=', '>=',];
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

    public function firstOrFail()
    {
        $data = $this->first();
        if (empty($data)) {
            throw new RecordNotFoundException("Record not found in table $this->_table");
        }
        return $data;
    }

    /**
     * @return mixed
     * @throws \Dorian\ORM\Exception\DatabaseException
     */
    public function first()
    {
        return $this->_getHydratedData()[0] ?? [];
    }

    private function _getHydratedData()
    {
        $data = $this->_execute();
        return $this->_getHydrator($data)->hydrate();
    }

    private function _execute()
    {
        $query = $this->_connexion->prepare($this->__toString());
        $this->_makeParams($query);
        $query->execute();
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function _makeParams(\PDOStatement &$statement): void
    {
        foreach ($this->_bindedParams as $field => $value) {
            $paramType = (is_numeric($value)) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $statement->bindParam(':' . $field, $value, $paramType);
        }
    }

    private function _getHydrator($data)
    {
        return new Hydrator($data, $this->_entityContains, $this->_table, $this->_container);
    }

    public function toArray()
    {
        return $this->_getHydratedData();
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

    public function orderBy($field, $type = 'ASC')
    {
        $this->_order[$field] = $type;
        return $this;
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
            if (in_array($to, $this->_joinedTables)) {
                $from = $to;
                continue;
            } else {
                $this->_joinedTables[] = $to;
            }

            if (!isset($this->_entityContains[$from]) || !in_array($to, $this->_entityContains[$from])) {
                $this->_entityContains[$from][] = $to;
            }
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

    private function _getConditionLogic(string $operator)
    {
        if (strpos($operator, 'OR')) {
            return ' OR ';
        }
        return ' AND ';
    }

    private function _getCondition()
    {
        $conditions = [];
        $i = 0;
        $max = count($this->_conditions);
        foreach ($this->_conditions as $field => $condition) {
            if ($i === 0) {
                $conditions[] = 'WHERE';
            }
            $conditionLogic = $this->_getConditionLogic($condition);
            $operator = $this->_getConditionOperator($field);

            $formatedField = $this->_getFormatedField($field);
            $bindedField = $this->_getBindedParamName($field);
            $conditionValue = $condition;
            $this->_cleanCondition($conditionValue);
            $this->_bindedParams[$bindedField] = $conditionValue;
            $conditions[] = $formatedField . ' ' . $operator . ' :' . $bindedField . (($i + 1) < $max ? $conditionLogic : '');
            $i++;
        }
        return $conditions;
    }

    private function _cleanCondition(&$condition)
    {
        foreach (['AND', 'OR'] as $operator) {
            $condition = str_replace($operator, '', $condition);
        }
        $condition = trim($condition);
    }

    private function _isComposateField(&$field)
    {
        $matches = [];
        $this->_cleanField($field);
        preg_match_all('/\./', $field, $matches);
        return (strpos($field, '.') !== false && count($matches) === 1);
    }

    private function _getFormatedField($field)
    {
        if ($this->_isComposateField($field)) {
            list($table, $fieldTable) = explode('.', $field);
            return $table . '.' . '`' . mb_strtolower($fieldTable) . '`';
        }
        return '`' . mb_strtolower($field) . '`';
    }

    private function _getBindedParamName($field)
    {
        $this->_cleanField($field);
        $ret = $field;
        if ($this->_isComposateField($field)) {
            list($table, $fieldName) = explode('.', $field);
            $ret = $table . '_' . $fieldName;
        }
        return Inflector::underscore(mb_strtolower($ret));
    }

    private function _cleanField(&$field)
    {
        $field = str_replace('AND', '', $field);
        $field = str_replace('OR', '', $field);
        foreach ($this->_operators as $operator) {
            $field = str_replace($operator, '', $field);
        }
        $field = trim($field);
    }

    private function _getConditionOperator($field)
    {
        $operators = $this->_operators;
        foreach ($operators as $operator) {
            if (strpos($field, $operator) !== false) {
                return $operator;
            }
        }
        return '=';
    }

    private function _getOrderByClause()
    {
        $clause = [];
        foreach ($this->_order as $field => $type) {
            $clause[] = $field . ' ' . $type;
        }
        if (empty($clause)) {
            return '';
        }
        return ' ORDER ' . implode(', ', $clause);

    }

    public function __toString()
    {
        $statment = "SELECT {$this->_getSelect()} FROM {$this->_getRealTableName()} AS {$this->_table}";
        $joins = [];
        foreach ($this->_contains as $contain) {
            $joins[] = $this->_join($contain);
        }
        $statment .= implode(' ', $joins);
        $statment .= ' ' . implode(' ', $this->_getCondition());
        $statment .= $this->_getOrderByClause();
        $statment .=';';
        return $statment;
    }

}