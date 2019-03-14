<?php
namespace NeedleProject\LaravelRabbitMq\Entity;

/**
 * Interface AMQPEntityInterface
 *
 * @package NeedleProject\LaravelRabbitMq\Entity
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
interface AMQPEntityInterface
{
    /**
     * Create the entity
     * @return mixed
     */
    public function create();

    /**
     * Bind the entity
     * @return void
     */
    public function bind();

    /**
     * @return void
     */
    public function delete();

    /**
     * @return string
     */
    public function getAliasName();

    /**
     * Reconnect the entity
     */
    public function reconnect();
}
