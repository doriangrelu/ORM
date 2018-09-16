<?php
/**
 * Created by PhpStorm.
 * User: doria
 * Date: 16/09/2018
 * Time: 16:46
 */

namespace Tests\Framework\Repository;


use Dorian\ORM\Repository;
use Psr\Container\ContainerInterface;

class RolesRepository extends Repository
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->hasMany('Personnes');
    }
}