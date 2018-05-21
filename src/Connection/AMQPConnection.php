<?php
namespace NeedleProject\LaravelRabbitMq\Connection;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Class AMQPConnection
 *
 * @package NeedleProject\LaravelRabbitMq\Connection
 */
class AMQPConnection
{
    /**
     * @const array Default connections parameters
     */
    const DEFAULTS = [
        'hostname'           => '127.0.0.1',
        'port'               => 5672,
        'username'           => 'guest',
        'password'           => 'guest',
        'vhost'              => '/',

        # whether the connection should be lazy
        'lazy'               => true,

        # More info about timeouts can be found on https://www.rabbitmq.com/networking.html
        'read_timeout'       => 1,   // default timeout in seconds
        'read_write_timeout' => 8,   // default timeout for writing/reading (in seconds)
        'heartbeat'          => 4
    ];

    /**
     * @var array
     */
    private $connectionDetails = [];

    /**
     * @var string
     */
    private $aliasName = '';

    /**
     * @var null|AMQPStreamConnection
     */
    private $connection = null;

    /**
     * @var null|AMQPChannel
     */
    private $channel = null;

    /**
     * AMQPConnection constructor.
     *
     * @param string $aliasName
     * @param array $connectionDetails
     */
    public function __construct(string $aliasName, array $connectionDetails = [])
    {
        $this->connectionDetails = array_merge(
            static::DEFAULTS,
            $connectionDetails
        );
        $this->aliasName = $aliasName;
    }

    /**
     * @return AMQPStreamConnection
     */
    protected function getConnection(): AMQPStreamConnection
    {
        if (is_null($this->connection)) {
            $this->connection = new AMQPStreamConnection(
                $this->connectionDetails['hostname'],
                $this->connectionDetails['port'],
                $this->connectionDetails['username'],
                $this->connectionDetails['password'],
                $this->connectionDetails['vhost'],
                /** insist */
                false,
                /** login method */
                'AMQPLAIN',
                /** login_response */
                null,
                /** locale */
                'en_US',
                $this->connectionDetails['connect_timeout'],
                $this->connectionDetails['read_write_timeout'],
                null,
                true,
                $this->connectionDetails['heartbeat']
            );
        }
        return $this->connection;
    }

    /**
     * @return \PhpAmqpLib\Channel\AMQPChannel
     */
    public function getChannel()
    {
        if (is_null($this->channel)) {
            $this->channel = $this->getConnection()->channel();
        }
        return $this->channel;
    }

    /**
     * Retrieve the connection alias name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->aliasName;
    }
}
