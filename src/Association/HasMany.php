<?php
/**
 * Created by PhpStorm.
 * User: doria
 * Date: 16/09/2018
 * Time: 16:59
 */

namespace Dorian\ORM\Association;


use Cake\Utility\Inflector;

class HasMany extends AssociationManager
{

    public function getJoinCondition()
    {
        $foreignKey = $this->_to . '.' . Inflector::singularize($this->_getRealTableName($this->_tableName)) . '_id';
        return <<<EOD
$this->_tableName.$this->_id = $foreignKey
EOD;
    }
}