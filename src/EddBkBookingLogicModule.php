<?php

namespace RebelCode\EddBookings\Logic\Module;

use Dhii\Data\Container\ContainerFactoryInterface;
use Dhii\Event\EventFactoryInterface;
use Dhii\Exception\InternalException;
use Dhii\Util\String\StringableInterface as Stringable;
use Psr\Container\ContainerInterface;
use Psr\EventManager\EventManagerInterface;
use RebelCode\Bookings\FactoryStateMachineTransitioner;
use RebelCode\Modular\Module\AbstractBaseModule;
use RebelCode\EddBookings\Logic\Module\BookingStatusInterface as BookingStatus;

/**
 * Module class for the EDDBK booking logic module.
 *
 * @since [*next-version*]
 */
class EddBkBookingLogicModule extends AbstractBaseModule
{
    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable         $key                  The module key.
     * @param string[]|Stringable[]     $dependencies         The module dependencies.
     * @param ContainerFactoryInterface $configFactory        The config factory.
     * @param ContainerFactoryInterface $containerFactory     The container factory.
     * @param ContainerFactoryInterface $compContainerFactory The composite container factory.
     * @param EventManagerInterface     $eventManager         The event manager.
     * @param EventFactoryInterface     $eventFactory         The event factory.
     */
    public function __construct(
        $key,
        $dependencies,
        $configFactory,
        $containerFactory,
        $compContainerFactory,
        $eventManager,
        $eventFactory
    ) {
        $this->_initModule($key, $dependencies, $configFactory, $containerFactory, $compContainerFactory);
        $this->_initModuleEvents($eventManager, $eventFactory);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     *
     * @throws InternalException
     */
    public function setup()
    {
        return $this->_setupContainer(
            $this->_loadPhpConfigFile(EDDBK_BOOKING_LOGIC_MODULE_CONFIG),
            [
                'booking_transitioner' => function (ContainerInterface $c) {
                    return new EventsDelegateTransitioner(
                        new FactoryStateMachineTransitioner(
                            $c->get('booking_state_machine_provider'),
                            $c->get('booking_factory')
                        ),
                        $c->get('event_manager'),
                        $c->get('transition_event_factory')
                    );
                },
                'transition_event_factory' => function (ContainerInterface $c) {
                    return new TransitionEventFactory();
                },
                'booking_state_machine_provider' => function (ContainerInterface $c) {
                    return new BookingStateMachineProvider($c);
                },
                'booking_transition_manager' => function (ContainerInterface $c) {
                    return new BookingTransitionManager(
                        $c->get('booking_validator'),
                        $c->get('booking_transitioner'),
                        $c->get('event_manager'),
                        $c->get('event_factory'),
                        $c->get('booking_logic/status_transitions')
                    );
                },
                'booking_validator' => function (ContainerInterface $c) {
                    return new BookingValidator([
                        $c->get('booking_conflict_validator'),
                        $c->get('booking_session_validator'),
                    ]);
                },
                'booking_conflict_validator' => function (ContainerInterface $c) {
                    return new BookingConflictValidator(
                        $c->get('bookings_select_rm'),
                        $c->get('booking_conflict_condition_factory')
                    );
                },
                'booking_session_validator' => function (ContainerInterface $c) {
                    return new BookingSessionValidator(
                        $c->get('sessions_select_rm'),
                        $c->get('sql_expression_builder')
                    );
                },
                'booking_conflict_condition_factory' => function (ContainerInterface $c) {
                    return new BookingConflictConditionFactory(
                        $c->get('sql_expression_builder'),
                        $c->get('booking_logic/non_blocking_statuses')
                    );
                },
                'wp_unbooked_sessions_condition' => function (ContainerInterface $c) {
                    $b  = $c->get('sql_expression_builder');
                    $bt = $c->get('cqrs/bookings/table');

                    // Sessions are considered to be unbooked if:
                    // Booking ID is null - meaning no booking matched the JOIN
                    // Or booking status is `in_cart`
                    // OR booking status is `cancelled`
                    // The latter 2 mean a booking DID match, but we are excluding them from blocking the session
                    return $b->or(
                        $b->is(
                            $b->ef($bt, 'id'),
                            $b->lit(null)
                        ),
                        $b->eq(
                            $b->ef($bt, 'status'),
                            $b->lit(BookingStatus::STATUS_IN_CART)
                        ),
                        $b->eq(
                            $b->ef($bt, 'status'),
                            $b->lit(BookingStatus::STATUS_CANCELLED)
                        )
                    );
                },
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
        // Set up transition manager
        $transitionManager = $c->get('booking_transition_manager');
        $transitionManager();
    }
}
