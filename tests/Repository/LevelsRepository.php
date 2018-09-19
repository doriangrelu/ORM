<?php
/**
 * Created by PhpStorm.
 * User: doria
 * Date: 19/09/2018
 * Time: 10:59
 */

namespace Tests\Framework\Repository;


use Dorian\ORM\Repository;
use Psr\Container\ContainerInterface;

class LevelsRepository extends Repository
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->hasMany('Roles');
    }
}