<?php
/**
 * Created by PhpStorm.
 * User: doria
 * Date: 19/09/2018
 * Time: 10:13
 */

namespace Dorian\ORM\Query;


use Cake\Utility\Inflector;
use Dorian\ORM\Entity\Entity;
use Dorian\ORM\Environment;
use Dorian\ORM\Exception\EntityException;
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

    private $collections = [];
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

    private function _makeTree()
    {

    }

    public function hydrate()
    {
        $entitiesToHydrate = $this->_getEntitiesToHydrate();
            foreach($this->_data as $line)
            {
                foreach($entitiesToHydrate as $entityToHydrate){
                   $entity = $this->_getEntity($entityToHydrate);

                   var_dump($entity);
                   die();
                }
                var_dump($line);
                die();
            }

    }

    private function getHydratedEntity($entityName, $line)
    {
        $entity = $this->_getEntity($entityName);
        foreach($line as $field=>$value){
            if(strpos($field, '_'.$entityName)!==false){
                $realFieldName=str_replace('_'.$entityName, '', $field);
                $entity->$realFieldName = $value;

            }
        }


    }

}