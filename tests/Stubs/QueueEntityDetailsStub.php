<?php
namespace Tests\NeedleProject\LaravelRabbitMq\Stubs;

use NeedleProject\LaravelRabbitMq\Entity\QueueEntity;

class QueueEntityDetailsStub extends QueueEntity
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
