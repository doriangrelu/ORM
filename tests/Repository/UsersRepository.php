<?php
/**
 * Created by PhpStorm.
 * User: doria
 * Date: 14/09/2018
 * Time: 15:19
 */

namespace Tests\Framework\Repository;


use Dorian\ORM\Repository;
use Psr\Container\ContainerInterface;

class UsersRepository extends Repository
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->belongsTo('Roles');
    }
}
