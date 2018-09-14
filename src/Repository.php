<?php

namespace Dorian\ORM;

use Dorian\ORM\Entity\Entity;

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

    public function __construct()
    {
        $className = get_class($this);
        $classNameExploded = explode('\\', $className);
        $this->_table = str_replace('Repository', '', end($classNameExploded));

        if (empty($this->_id)) {
            $this->_id = 'id';
        }
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
        return new Query\Query($this);
    }

}