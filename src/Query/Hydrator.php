<?php
/**
 * Created by PhpStorm.
 * User: doria
 * Date: 19/09/2018
 * Time: 10:13
 */

namespace Dorian\ORM\Query;


use Cake\Utility\Inflector;
use Dorian\ORM\Association\AssociationManager;
use Dorian\ORM\Entity\Entity;
use Dorian\ORM\Environment;
use Dorian\ORM\Exception\DatabaseException;
use Dorian\ORM\Exception\EntityException;
use Dorian\ORM\Repository;
use Psr\Container\ContainerInterface;

class Hydrator
{
    /**
     * @var ContainerInterface
     */
    private $_container;

    private $_from;

    private $_data = [];

    private $_contains = [];

    private $_collections = [];
    /**
     * @var Entity
     */
    private $_entity;

    public function __construct(array $data, array $contains, string $from, ContainerInterface $container)
    {
        $this->_data = $data;
        $this->_from = $from;
        $this->_contains = $contains;
        $this->_container = $container;
    }

    private function _getEntitiesToHydrate()
    {
        $list = [];
        foreach ($this->_contains as $table => $tables) {
            if (!in_array($table, $list)) {
                $list[] = $table;
            }
            foreach ($tables as $single) {
                if (!in_array($single, $list)) {
                    $list[] = $single;
                }
            }
        }
        return $list;
    }


    private function _getEntity($table)
    {
        $namespace = $this->_container->get(Environment::ENTITY_NAMESPACE);
        $className = Inflector::singularize($table);
        $class = $namespace . $className;

        if (!class_exists($class)) {
            throw new EntityException("Missing entity $class");
        }
        $class = $namespace . $className;
        return new $class();
        //return $this->_container->get($namespace . $className);
    }

    /**
     * @throws DatabaseException
     */
    private function _matchingCollections()
    {

        foreach ($this->_contains as $from => $joineds) {
            foreach ($joineds as $join) {
                if (!isset($this->_collections[$from])) {
                    continue;
                }
                foreach ($this->_collections[$from] as $table) {
                    if ($this->_getAssociation($from, $join) == AssociationManager::SINGLE) {
                        $paramName = mb_strtolower(Inflector::singularize($join));
                        $fieldName = $this->_getFieldJoined($join);
                        $table->$paramName = $this->_findCollectionFromTableAndId($join, $table->$fieldName);
                    } else {
                        $id = $this->_getIdFromTable($from);
                        $paramName = mb_strtolower(Inflector::pluralize($join));
                        $table->$paramName = $this->_findEntitiesById($from, $join, $table->$id);
                    }
                }
            }
        }
    }

    private function _findEntitiesById($table, $to, $id)
    {
        $fieldName = $this->_getFieldJoined($table);
        $entities = [];
        if (isset($this->_collections[$to])) {
            foreach ($this->_collections[$to] as $entity) {
                if ($entity->$fieldName === $id) {
                    $entities[] = $entity;
                }
            }
        }
        return $entities;
    }

    private function _getFieldJoined($to, $type = "singularize")
    {
        return mb_strtolower(Inflector::$type($to)) . '_id';
    }

    /**
     * @param $table
     * @param $id
     * @return mixed
     * @throws DatabaseException
     */
    private function _findCollectionFromTableAndId($table, $id)
    {
        if (isset($this->_collections[$table]) && isset($this->_collections[$table][$id])) {
            return $this->_collections[$table][$id];
        }
        throw new DatabaseException('Not selected table ' . $table);
    }

    /**
     * @throws DatabaseException
     */
    public function hydrate()
    {
        $entitiesToHydrate = $this->_getEntitiesToHydrate();
        $entities = [];
        foreach ($this->_data as $line) {
            foreach ($entitiesToHydrate as $entityToHydrate) {
                $entity = $this->_getHydratedEntity($entityToHydrate, $line);
                $entities[] = $entity;
            }
        }
        $this->_createCollection($entities);
        $this->_matchingCollections();
        return array_values($this->_collections[$this->_from] ?? []);
    }

    private function _createCollection($entities)
    {
        foreach ($entities as $entity) {
            $path = explode('\\', get_class($entity));
            $tableName = Inflector::pluralize(array_pop($path));
            $id = $this->_getIdFromTable($tableName);
            $this->_collections[$tableName][$entity->$id] = $entity;
        }
    }

    private function _getIdFromTable($table)
    {
        return $this->_getRepository($table)->getId();
    }

    private function _getRepository($table): Repository
    {
        $repository = $this->_container->get(Environment::REPOSITORY_NAMSPACE) . $table . 'Repository';
        return $this->_container->get($repository);
    }

    private function _getAssociation($table, $to)
    {
        $repository = $this->_getRepository($table);
        return $repository->getAssociation($to)->getTypeOfEntityContainer();
    }

    private function _getHydratedEntity($entityName, $line)
    {
        $entity = $this->_getEntity($entityName);
        foreach ($line as $field => $value) {
            if (strpos($field, '_' . $entityName) !== false) {
                $realFieldName = str_replace('_' . $entityName, '', $field);
                $entity->$realFieldName = $value;
            }
        }
        return $entity;
    }

}
