<?php
/**
 * Created by PhpStorm.
 * User: doria
 * Date: 16/09/2018
 * Time: 17:39
 */

namespace Dorian\ORM\Query;


use Cake\Utility\Inflector;
use Dorian\ORM\Exception\DatabaseException;

class ReflexionTable
{
    private $_connexion;

    public function __construct(\PDO $connexion)
    {
        $this->_connexion = $connexion;
    }

    private function _getRealTableName(string $tableName): string
    {
        return mb_strtolower(Inflector::underscore($tableName));
    }

    /**
     * @param $tableName
     * @param bool $taggedFields
     * @return array|string
     * @throws DatabaseException
     */
    public
    function showColumns($tableName, $taggedFields = true)
    {
        $fields = $this->_connexion->query("SHOW COLUMNS FROM `{$this->_getRealTableName($tableName)}`")->fetchAll(\PDO::FETCH_OBJ);

        if (empty($fields)) {
            throw new DatabaseException();
        }

        if ($taggedFields) {
            $taggedFieldsResult = [];
            foreach ($fields as $field) {
                $taggedFieldsResult[] = $tableName . '.`' . $field->Field . '` AS ' . $field->Field . '_' . $tableName;
            }
            return implode(', ', $taggedFieldsResult);
        }

        return $fields;
    }

}