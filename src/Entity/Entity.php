<?php
/**
 * Created by PhpStorm.
 * User: doria
 * Date: 14/09/2018
 * Time: 14:16
 */

namespace Dorian\ORM\Entity;


use Dorian\ORM\Exception\EntityException;

class Entity
{
    private $_fields = [];
    private $_allowedFields = [];
    private $_errors = [];

    public function __construct(array $fields = [])
    {
      $this->_allowedFields = $this->_filterFieds($fields);
    }

    private function _filterFieds(array $fields):array
    {
      $cleanFields = [];
      foreach($fields as $field=>$value){
        if(in_array($field, $this->_allowedFields)){
          $cleanFields[$fields] = $value;
        }
      }
      return $cleanFields;
    }


    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (method_exists($this, self::getMethodName('set', $name))) {
            $method = self::getMethodName('set', $name);
            $value = $method($value);
        }

        $this->_fields[$name] = $value;

    }

    /**
     * @param string $name
     * @param string $error
     */
    public function setError(string $name, string $error)
    {
        $this->_errors[$name][] = $error;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * @param $name
     * @return mixed
     * @throws EntityException
     */
    public function __get($name)
    {
        if (!isset($this->_fields[$name])) {
            throw new EntityException("Missing property $name");
        }
        return $this->_fields[$name];
    }

    /**
     * @param string $type
     * @param string $name
     * @return string
     */
    public static function getMethodName(string $type, string $name)
    {
        return '_' . mb_strtolower($type) . ucfirst($name);
    }

    /**
     * @return \ReflectionClass
     */
    private function _getReflexion()
    {
        return new \ReflectionClass(get_class($this));
    }

}
