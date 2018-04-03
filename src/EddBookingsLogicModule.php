<?php

namespace RebelCode\EddBookings\Logic\Module;

use Dhii\Data\Container\ContainerFactoryInterface;
use Dhii\Event\EventFactoryInterface;
use Dhii\Exception\InternalException;
use Dhii\Util\String\StringableInterface as Stringable;
use Psr\Container\ContainerInterface;
use Psr\EventManager\EventManagerInterface;
use RebelCode\Modular\Module\AbstractBaseModule;

/**
 * Module class for the EDDBK booking logic module.
 *
 * @since [*next-version*]
 */
class EddBookingsLogicModule extends AbstractBaseModule
{
    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable              $key              The module key.
     * @param ContainerFactoryInterface|null $containerFactory The container factory, if any.
     * @param EventManagerInterface|null     $eventManager     The event manager, if any.
     * @param EventFactoryInterface|null     $eventFactory     The event factory, if any.
     *
     * @throws InternalException
     */
    public function __construct($key, $containerFactory, $eventManager, $eventFactory)
    {
        $this->_initModule($containerFactory, $key, [], $this->_loadPhpConfigFile(EDDBK_BOOKING_LOGIC_MODULE_CONFIG));
        $this->_initModuleEvents($eventManager, $eventFactory);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function setup()
    {
        $config = $this->_getConfig();

        return $this->_createContainer(
            [
                'booking_state_machine_provider' => function(ContainerInterface $c) use ($config) {
                    return new BookingStateMachineProvider($c, $config);
                }
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function run(ContainerInterface $c = null)
    {
    }
}
