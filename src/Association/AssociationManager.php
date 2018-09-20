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

    const MULTIPLE = 1;
    const SINGLE = 2;

    protected $_tableName;
    protected $_id;
    protected $_to;
    /**
     * @var ContainerInterface
     */
    protected $_container;

    private function _getName()
    {
        $path = explode('\\', __CLASS__);
        return array_pop($path);
    }

    public function __construct(string $to, string $tableName, string $id, ContainerInterface $container)
    {
        $this->_to = $to;
        $this->_tableName = $tableName;
        $this->_id = $id;
        $this->_container = $container;
    }

    public function getTypeOfEntityContainer(): string
    {
        return $this->_getName() == 'HasMany' ? self::MULTIPLE : self::SINGLE;
    }

    protected function _getRealTableName(string $tableName): string
    {
        return mb_strtolower(Inflector::underscore($tableName));
    }

    public abstract function getJoinCondition();

}