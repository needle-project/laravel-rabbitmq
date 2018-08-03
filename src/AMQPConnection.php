<?php
namespace NeedleProject\LaravelRabbitMq;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Class AMQPConnection
 *
 * @package NeedleProject\LaravelRabbitMq
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
        'read_write_timeout' => 8,   // default timeout for writing/reading (in seconds)
        'connect_timeout'    => 10,
        'heartbeat'          => 4
    ];

    /**
     * @var array
     */
    protected $connectionDetails = [];

    /**
     * @var string
     */
    protected $aliasName = '';

    /**
     * @var null|AMQPStreamConnection
     */
    private $connection = null;

    /**
     * @var null|AMQPChannel
     */
    private $channel = null;

    /**
     * @param string $aliasName
     * @param array $connectionDetails
     * @return AMQPConnection
     */
    public static function createConnection(string $aliasName, array $connectionDetails)
    {
        if ($diff = array_diff(array_keys($connectionDetails), array_keys(self::DEFAULTS))) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Cannot create connection %s, received unknown arguments: %s!",
                    (string)$aliasName,
                    implode(', ', $diff)
                )
            );
        }

        return new static(
            $aliasName,
            array_merge(self::DEFAULTS, $connectionDetails)
        );
    }

    /**
     * AMQPConnection constructor.
     *
     * @param string $aliasName
     * @param array $connectionDetails
     */
    public function __construct(string $aliasName, array $connectionDetails = [])
    {
        $this->aliasName = $aliasName;
        $this->connectionDetails = $connectionDetails;
        if (isset($connectionDetails['lazy']) &&  $connectionDetails['lazy'] === false) {
            // dummy call
            $this->getConnection();
        }
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
     * Reconnect
     */
    public function reconnect()
    {
        $this->getConnection()->channel()->close();
        $this->channel = null;
        $this->getConnection()->reconnect();
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
    public function getAliasName(): string
    {
        return $this->aliasName;
    }
}
