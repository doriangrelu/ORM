<?php
/**
 * Created by PhpStorm.
 * User: doria
 * Date: 16/09/2018
 * Time: 17:07
 */

namespace Dorian\ORM\Association;


use Cake\Utility\Inflector;
use Dorian\ORM\Environment;

class BelongsToMany extends AssociationManager
{

    public function getJoinCondition()
    {
        $primary = $this->_to . '.' . $this->_container->get($this->_container->get(Environment::REPOSITORY_NAMSPACE) . $this->_to . 'Repository')->getId();
        $foreignKey = $this->_tableName . '.' . Inflector::singularize($this->_getRealTableName($this->_to)) . '_id';
        return <<<EOD
$foreignKey = $primary
EOD;

    }
}
