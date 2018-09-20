<?php
/**
 * Created by PhpStorm.
 * User: doria
 * Date: 14/09/2018
 * Time: 15:17
 */

class QueryTest extends \PHPUnit\Framework\TestCase
{
    public function testBuildingStatment()
    {
        $environment = (new \Dorian\ORM\Environment())
            ->setRepositoryNamspace("Tests\\Framework\\Repository\\")
            ->setDatabaseConnexion('localhost', 'orm', 'root', '')
            ->setEntityNamespace("Tests\\Framework\\Entity\\");
        $manager = new \Dorian\ORM\Manager($environment);
        var_dump($manager->getRepository('Levels')->find()
            ->contain('Roles' )
            ->first());

        die('here');
    }
}