<?php

namespace RebelCode\EddBookings\Logic\Module;

use ArrayAccess;
use Dhii\Data\Container\ContainerAwareTrait;
use Dhii\Data\Container\CreateContainerExceptionCapableTrait;
use Dhii\Data\Container\NormalizeContainerCapableTrait;
use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Invocation\InvocableInterface;
use Psr\Container\ContainerInterface;
use RebelCode\Modular\Config\ConfigAwareTrait;
use stdClass;

/**
 * Provides a state machine for a particular booking-transition combo.
 *
 * @since [*next-version*]
 */
class BookingStateMachineProvider implements InvocableInterface
{
    /*
     * Provides container awareness.
     *
     * @since [*next-version*]
     */
    use ContainerAwareTrait;

    /*
     * Provides config awareness.
     *
     * @since [*next-version*]
     */
    use ConfigAwareTrait;

    /*
     * Provides container normalization functionality.
     *
     * @since [*next-version*]
     */
    use NormalizeContainerCapableTrait;

    /*
     * Provides functionality for creating invalid argument exceptions.
     *
     * @since [*next-version*]
     */
    use CreateInvalidArgumentExceptionCapableTrait;

    /*
     * Provides functionality for creating container exceptions.
     *
     * @since [*next-version*]
     */
    use CreateContainerExceptionCapableTrait;

    /*
     * Provides string translating functionality.
     *
     * @since [*next-version*]
     */
    use StringTranslatingTrait;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param ContainerInterface                            $container The DI container.
     * @param array|ArrayAccess|stdClass|ContainerInterface $config    The configuration.
     */
    public function __construct(
        ContainerInterface $container,
        $config
    ) {
        $this->_setContainer($container);
        $this->_setConfig($config);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function __invoke()
    {
        $booking = func_get_arg(0);

        $c = $this->_getContainer();
        $x = $this->_getConfig();

        return $c->get('booking_state_machine_factory')->make(
            [
                'event_manager'     => $c->get('event_manager'),
                'initial_state'     => $booking->getStatus(),
                'transitions'       => $x['booking_status_transitions'],
                'event_name_format' => $x['booking_event_state_machine']['event_name_format'],
                'event_params'      => [
                    'booking' => $booking,
                ],
            ]
        );
    }
}
