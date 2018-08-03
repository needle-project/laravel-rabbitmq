<?php
namespace Tests\NeedleProject\LaravelRabbitMq\Stubs;

use NeedleProject\LaravelRabbitMq\Entity\ExchangeEntity;

class ExchangeEntityDetailsStub extends ExchangeEntity
{
    public function getAttributes()
    {
        return $this->attributes;
    }

    public function __call($methodName, $arguments)
    {
        return $this->$methodName;
    }
}
