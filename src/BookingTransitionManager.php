<?php

namespace RebelCode\EddBookings\Logic\Module;

use ArrayAccess;
use Dhii\Data\Container\ContainerGetCapableTrait;
use Dhii\Data\Container\CreateContainerExceptionCapableTrait;
use Dhii\Data\Container\CreateNotFoundExceptionCapableTrait;
use Dhii\Data\Container\NormalizeContainerCapableTrait;
use Dhii\Data\Container\NormalizeKeyCapableTrait;
use Dhii\Event\EventFactoryInterface;
use Dhii\Events\TransitionEventInterface;
use Dhii\Exception\CreateOutOfRangeExceptionCapableTrait;
use Dhii\Invocation\InvocableInterface;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use Dhii\Util\String\StringableInterface as Stringable;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventManager\EventInterface;
use Psr\EventManager\EventManagerInterface;
use RebelCode\Bookings\BookingInterface;
use RebelCode\Bookings\TransitionerAwareTrait;
use RebelCode\Bookings\TransitionerInterface;
use RebelCode\EddBookings\Logic\Module\BookingStatusInterface as S;
use RebelCode\EddBookings\Logic\Module\BookingTransitionInterface as T;
use RebelCode\Modular\Events\EventsConsumerTrait;
use RebelCode\State\PossibleTransitionsAwareTrait;
use stdClass;

/**
 * Manages the booking transitions for EDD Bookings.
 *
 * @since [*next-version*]
 */
class BookingTransitionManager implements InvocableInterface
{
    /*
     * Provides awareness of a booking transitioner.
     *
     * @since [*next-version*]
     */
    use TransitionerAwareTrait;

    /*
     * Provides awareness of configuration.
     *
     * @since [*next-version*]
     */
    use PossibleTransitionsAwareTrait;

    /*
     * Provides functionality for reading from any type of container.
     *
     * @since [*next-version*]
     */
    use ContainerGetCapableTrait;

    /*
     * Provides key normalization functionality.
     *
     * @since [*next-version*]
     */
    use NormalizeKeyCapableTrait;

    /*
     * Provides string normalization functionality.
     *
     * @since [*next-version*]
     */
    use NormalizeStringCapableTrait;

    /*
     * Provides container normalization functionality.
     *
     * @since [*next-version*]
     */
    use NormalizeContainerCapableTrait;

    /*
     * Provides functionality for creating out-of-range exceptions.
     *
     * @since [*next-version*]
     */
    use CreateOutOfRangeExceptionCapableTrait;

    /*
     * Provides functionality for creating container exceptions.
     *
     * @since [*next-version*]
     */
    use CreateContainerExceptionCapableTrait;

    /*
     * Provides functionality for creating not-found exceptions.
     *
     * @since [*next-version*]
     */
    use CreateNotFoundExceptionCapableTrait;

    /*
     * Provides all required functionality for working with events.
     *
     * @since [*next-version*]
     */
    use EventsConsumerTrait;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param TransitionerInterface                         $transitioner The booking transitioner.
     * @param EventManagerInterface                         $eventManager The event manager.
     * @param EventFactoryInterface                         $eventFactory The event factory.
     * @param array|ArrayAccess|stdClass|ContainerInterface $transitions  The possible transitions.
     */
    public function __construct(
        TransitionerInterface $transitioner,
        EventManagerInterface $eventManager,
        EventFactoryInterface $eventFactory,
        $transitions
    ) {
        $this->_setTransitioner($transitioner);
        $this->_setEventManager($eventManager);
        $this->_setEventFactory($eventFactory);
        $this->_setPossibleTransitions($transitions);
    }

    /**
     * Retrieves the possible transitions from a given booking status.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable $status The booking status.
     *
     * @return array|ArrayAccess|stdClass|ContainerInterface A container of transition-to-status mappings.
     */
    protected function _getPossibleTransitionsForStatus($status)
    {
        $transitions = $this->_getPossibleTransitions();
        $status = ($status === null) ? S::STATUS_NONE : $status;

        try {
            return $this->_containerGet($transitions, $status);
        } catch (NotFoundExceptionInterface $notFoundException) {
            return [];
        }
    }

    /**
     * Retrieves the status that would result from a successful transition from a particular status.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable $status     The booking status.
     * @param string|Stringable $transition The transition.
     *
     * @throws NotFoundExceptionInterface If the status or transition are not found in the config.
     *
     * @return string|Stringable The new booking status.
     */
    protected function _getStatusAfterTransition($status, $transition)
    {
        $transitions = $this->_getPossibleTransitionsForStatus($status);

        return $this->_containerGet($transitions, $transition);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function __invoke()
    {
        $this->_attach('on_booking_transition', [$this, '_beforeTransition']);
        $this->_attach('after_booking_transition', [$this, '_afterPendingTransition']);
        $this->_attach('after_booking_transition', [$this, '_afterApprovedTransition']);
    }

    /**
     * Limits the transitions allows for bookings to only those declared in the module config.
     *
     * @since [*next-version*]
     *
     * @param TransitionEventInterface $event The transition event.
     */
    public function _beforeTransition(TransitionEventInterface $event)
    {
        $booking = $event->getParam('booking');

        if (!($booking instanceof BookingInterface)) {
            throw $this->_createRuntimeException($this->__('Transition does not have a valid booking instance'));
        }

        $status = $booking->getStatus();
        $status = ($status === null) ? S::STATUS_NONE : $status;
        $transition = $event->getTransition();

        try {
            $newStatus = $this->_getStatusAfterTransition($status, $transition);
            $newParams = ['new_state' => $newStatus] + $event->getParams();
            $event->setParams($newParams);
        } catch (NotFoundExceptionInterface $notFoundException) {
            $event->abortTransition(true);
            $event->stopPropagation(true);
        }
    }

    /**
     * Attempts an approval transition after a booking is submitted.
     *
     * @since [*next-version*]
     *
     * @param EventInterface $event The event.
     */
    public function _afterPendingTransition(EventInterface $event)
    {
        if ($event->getParam('transition') === T::TRANSITION_SUBMIT) {
            $booking = $event->getParam('booking');
            $booking = $this->transitioner->transition($booking, T::TRANSITION_APPROVE);

            $event->setParams(['booking' => $booking] + $event->getParams());
        }
    }

    /**
     * Attempts a schedule transition after a booking is approved.
     *
     * @since [*next-version*]
     *
     * @param EventInterface $event The event.
     */
    public function _afterApprovedTransition(EventInterface $event)
    {
        if ($event->getParam('transition') === T::TRANSITION_APPROVE) {
            $booking = $event->getParam('booking');
            $booking = $this->transitioner->transition($booking, T::TRANSITION_SCHEDULE);

            $event->setParams(['booking' => $booking] + $event->getParams());
        }
    }
}