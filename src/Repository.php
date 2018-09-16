<?php

namespace Dorian\ORM;

use Dorian\ORM\Association\AssociationManager;
use Dorian\ORM\Association\BelongsTo;
use Dorian\ORM\Association\HasMany;
use Dorian\ORM\Entity\Entity;
use Dorian\ORM\Exception\AssociationException;
use Psr\Container\ContainerInterface;

/**
 * Created by PhpStorm.
 * User: doria
 * Date: 14/09/2018
 * Time: 14:14
 */
abstract class Repository
{
    protected $_table;
    protected $_fields;
    protected $_id = [];

    private $_associations = [];

    private $_container;

    public function __construct(ContainerInterface $container)
    {
        $this->_container = $container;
        $className = get_class($this);
        $classNameExploded = explode('\\', $className);
        $this->_table = str_replace('Repository', '', end($classNameExploded));

        if (empty($this->_id)) {
            $this->_id = 'id';
        }
    }

    /**
     * @param $tableName
     * @return AssociationManager
     * @throws AssociationException
     */
    public function getAssociation($tableName): AssociationManager
    {
        if(!isset($this->_associations[$tableName])){
            throw new AssociationException("$tableName not associate with $this->_table");
        }
        return $this->_associations[$tableName];
    }

    /**
     * @param $to
     */
    protected function hasMany($to)
    {
        $this->_associations[$to] = new HasMany($to, $this->_table, $this->_id, $this->_container);
    }

    /**
     * @param $to
     */
    protected function belongsTo($to)
    {
        $this->_associations[$to] = new BelongsTo($to, $this->_table, $this->_id, $this->_container);
    }

    /**
     * @return string
     */
    public function getEntityNamespace():string
    {
        return $this->_container->get(Environment::ENTITY_NAMESPACE);
    }

    /**
     * @return string
     */
    public function getRepositoryNamespace():string
    {
        return $this->_container->get(Environment::REPOSITORY_NAMSPACE);
    }

    public function save(Entity $entity): bool
    {

    }

    public function getTable()
    {
        return $this->_table;
    }

    public function getId()
    {
        return $this->_id;
    }

    public function find()
    {
        return new Query\Query($this, $this->_container);
    }

}