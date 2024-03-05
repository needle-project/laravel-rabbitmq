<?php

namespace Tests\NeedleProject\LaravelRabbitMq\Interpreter;

use NeedleProject\LaravelRabbitMq\Interpreter\EntityArgumentsInterpreter;
use PhpAmqpLib\Wire\AMQPTable;
use PHPUnit\Framework\TestCase;

class EntityArgumentsInterpreterTest extends TestCase
{
    public function testArgumentsInterpret()
    {
        $interpretedArguments = EntityArgumentsInterpreter::interpretArguments(['x-queue-type' => 'quorum']);
        $this->assertInstanceOf(AMQPTable::class, $interpretedArguments);
        $this->assertEquals(
            ['x-queue-type' => [14 /* represent longstring*/, 'quorum']],
            [$interpretedArguments->key() => $interpretedArguments->current()]
        );
    }

    public function testArgumentsInterpretForPassedInAMQPTable()
    {
        $inputAmqpTable = new AMQPTable(['x-queue-type' => 'quorum']);
        $interpretedArguments = EntityArgumentsInterpreter::interpretArguments($inputAmqpTable);

        $this->assertEquals($interpretedArguments, $inputAmqpTable);
    }
}
