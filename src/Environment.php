<?php
/**
 * Created by PhpStorm.
 * User: doria
 * Date: 14/09/2018
 * Time: 16:21
 */

namespace Dorian\ORM;


use Dorian\ORM\Exception\ConfigurationException;

class Environment
{
    const REPOSITORY_NAMSPACE = 'Repository.namespace';
    const ENTITY_NAMESPACE = 'Entity.namespace';
    const DB_HOST = 'Database.host';
    const DB_NAME = 'Database.name';
    const DB_USER = 'Database.user';
    const DB_PASSWORD = 'Database.password';
    const DRIVER = 'Driver';
    /**
     * @var String[]
     */
    private $_config = [
        self::DRIVER => 'mysql',
    ];

    /**
     * @param string $namespace
     * @return Environment
     */
    public function setRepositoryNamspace(string $namespace): self
    {
        $this->_config[self::REPOSITORY_NAMSPACE] = $this->_formatNamespace($namespace);
        return $this;
    }

    /**
     * @param string $namespace
     * @return Environment
     */
    public function setEntityNamespace(string $namespace): self
    {
        $this->_config[self::ENTITY_NAMESPACE] = $this->_formatNamespace($namespace);
        return $this;
    }

    /**
     * @param string $namespace
     * @return string
     */
    private function _formatNamespace(string $namespace): string
    {
        return trim($namespace, '\\') . '\\';;
    }

    /**
     * @param string $hostname
     * @param string $database
     * @param string $user
     * @param string $password
     * @return Environment
     */
    public function setDatabaseConnexion(string $hostname, string $database, string $user, string $password): self
    {
        $this->_config = array_merge($this->_config, [
            self::DB_HOST => $hostname,
            self::DB_NAME => $database,
            self::DB_USER => $user,
            self::DB_PASSWORD => $password,
        ]);
        return $this;
    }

    /**
     * @param string $driver
     * @return Environment
     */
    public function setDriver(string $driver): self
    {
        $this->_config[self::DRIVER] = $driver;
        return $this;
    }

    /**
     * @return array
     * @throws ConfigurationException
     */
    public function getConfig():array
    {
        $reflexionClass = new \ReflectionClass(__CLASS__);
        foreach (array_values($reflexionClass->getConstants()) as $constant) {
            if (!isset($this->_config[$constant])) {
                throw new ConfigurationException("Missing Configuration key: $constant");
            }
        }
        return $this->_config;
    }

}