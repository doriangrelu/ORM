<?php
/**
 * Created by PhpStorm.
 * User: doria
 * Date: 16/09/2018
 * Time: 16:57
 */

namespace Dorian\ORM\Association;


use Cake\Utility\Inflector;
use Psr\Container\ContainerInterface;

abstract class AssociationManager
{
    protected $_tableName;
    protected $_id;
    protected $_to;
    /**
     * @var ContainerInterface
     */
    protected $_container;

    public function __construct(string $to, string $tableName, string $id, ContainerInterface $container)
    {
        $this->_to = $to;
        $this->_tableName = $tableName;
        $this->_id = $id;
        $this->_container = $container;
    }

    protected function _getRealTableName(string $tableName):string
    {
        return mb_strtolower(Inflector::underscore($tableName));
    }

    public abstract function getJoinCondition();

}