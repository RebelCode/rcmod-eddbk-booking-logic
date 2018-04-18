<?php

namespace RebelCode\EddBookings\Logic\Module;

use Dhii\Data\Container\ContainerAwareTrait;
use Dhii\Data\Container\CreateContainerExceptionCapableTrait;
use Dhii\Data\Container\NormalizeContainerCapableTrait;
use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Invocation\InvocableInterface;
use Psr\Container\ContainerInterface;

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
     * @param ContainerInterface $container The DI container.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->_setContainer($container);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function __invoke()
    {
        $booking = func_get_arg(0);

        $container = $this->_getContainer();
        $status    = $booking->getStatus();
        $state     = ($status === null) ? BookingStatusInterface::STATUS_NONE : $status;

        return $container->get('booking_state_machine_factory')->make(
            [
                'event_manager'     => $container->get('event_manager'),
                'initial_state'     => $state,
                'transitions'       => $container->get('booking_logic/status_transitions'),
                'event_name_format' => $container->get('booking_logic/transition_event_format'),
                'event_params'      => [
                    'booking' => $booking,
                ],
            ]
        );
    }
}
