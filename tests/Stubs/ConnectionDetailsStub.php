<?php
namespace Tests\NeedleProject\LaravelRabbitMq\Stubs;

use NeedleProject\LaravelRabbitMq\AMQPConnection;

class ConnectionDetailsStub extends AMQPConnection
{
    public function __construct($aliasName, array $connectionDetails = [])
    {
        $this->connectionDetails = $connectionDetails;
        $this->aliasName = $aliasName;
    }

    public function getConnectionDetails()
    {
        return $this->connectionDetails;
    }
}
