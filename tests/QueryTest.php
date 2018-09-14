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
        $repositoy = new \Tests\Framework\Repository\PersonnesRepository();

        var_dump($repositoy->find()->contain('Tests.Dorians')->select('Tests.dorian')->__toString());

        die();

    }
}