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
        return $this->_container->get($namespace . $className);
    }

    private function _matchingCollections()
    {

        foreach ($this->_contains as $from => $joineds) {
            foreach ($joineds as $join) {
                foreach ($this->_collections[$from] as $table) {
                    $table->collection = 'TEST';
                    if ($this->_getAssociation($from, $join) == AssociationManager::SINGLE) {
                        $paramName = mb_strtolower(Inflector::singularize($join));
                        $fieldName = $this->_getFieldJoined($join);
                        $table->$paramName = $this->_findCollectionFromTableAndId($join, $table->$fieldName);
                    } else {
                        $paramName = mb_strtolower($join);
                        $fieldName = $this->_getFieldJoined($from);
                    }

                }
            }

        }
        foreach ($this->_collections as $table => $collections) {

        }
    }

    private function _getFieldJoined($to)
    {
        return mb_strtolower(Inflector::singularize($to)) . '_id';
    }

    private function _findCollectionFromTableAndId($table, $id)
    {
        if (isset($this->_collections[$table]) && isset($this->_collections[$table][$id])) {
            return $this->_collections[$table][$id];
        }
        throw new DatabaseException('Not selected table ' . $table);
    }

    public function hydrate()
    {
        $entitiesToHydrate = $this->_getEntitiesToHydrate();
        foreach ($this->_data as $line) {
            foreach ($entitiesToHydrate as $entityToHydrate) {
                $entity = $this->_getHydratedEntity($entityToHydrate, $line);
                $this->_alereadyHydrate($entityToHydrate, $entity);
            }
        }

        $this->_matchingCollections();

        var_dump($this->_collections);
        die();

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

    private function _alereadyHydrate($entityName, $entity)
    {
        $table = Inflector::pluralize($entityName);
        $id = $this->_getIdFromTable($table);

        if (!isset($this->_collections[$table])) {
            $this->_collections[$table][$entity->$id] = $entity;
            return false;
        }

        foreach ($this->_collections[$table] as $entityCollecion) {
            if ($entityCollecion->$id == $entity->$id) {
                return true;
            }
        }

        return $this->_collections[$table][$entity->$id] = $entity;
    }

    private function _entityExistsInCollection($entityName, $ids)
    {

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