<?php
/**
 * Created by PhpStorm.
 * User: doria
 * Date: 16/09/2018
 * Time: 16:17
 */

namespace Dorian\ORM;


use DI\ContainerBuilder;
use \PDO;
use Psr\Container\ContainerInterface;

class Manager
{

    /**
     * @var Environment
     */
    private $_environment;
    /**
     * @var \DI\Container
     */
    private $_container;

    /**
     * Manager constructor.
     * @param Environment $environment
     */
    public function __construct(Environment $environment)
    {
        $this->_environment = $environment;
        $builder = new ContainerBuilder();
        $config = $this->_environment->getConfig();
        $builder->addDefinitions($environment->getConfig() + [
                PDO::class => function (\Psr\Container\ContainerInterface $c) use ($config) {
                    return new PDO(
                        'mysql:host=' . $c->get(Environment::DB_HOST) . ';dbname=' . $c->get(Environment::DB_NAME),
                        $c->get(Environment::DB_USER),
                        $c->get(Environment::DB_PASSWORD),
                        [
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                        ]
                    );
                },
            ]);
        $this->_container = $builder->build();
    }

    /**
     * @param string $className
     * @return string
     */
    private function _makeCompleteClassName(string $className): string
    {
        $className = str_replace('Repository', '', $className);
        return $this->_environment->getConfig()[Environment::REPOSITORY_NAMSPACE] . $className . 'Repository';
    }

    /**
     * @return ContainerInterface
     */
    public function getRepositoryContainer(): ContainerInterface
    {
        return $this->_container;
    }

    /**
     * @param string $repositoryClass
     * @return Repository
     */
    public function getRepository(string $repositoryClass): Repository
    {
        return $this->_container->get($this->_makeCompleteClassName($repositoryClass));
    }


}