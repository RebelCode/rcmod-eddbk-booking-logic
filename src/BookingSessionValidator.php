<?php

namespace RebelCode\EddBookings\Logic\Module;

use Dhii\Expression\TermInterface;
use Dhii\Storage\Resource\SelectCapableInterface;
use Dhii\Util\Normalization\NormalizeIterableCapableTrait;
use Dhii\Validation\AbstractValidatorBase;
use Psr\Container\ContainerInterface;
use RebelCode\Bookings\BookingInterface;
use RebelCode\Expression\Builder\ExpressionBuilderAwareTrait;
use RebelCode\Expression\Builder\ExpressionBuilderInterface;

/**
 * A booking validator that validates whether a booking matches an existing session.
 *
 * @since [*next-version*]
 */
class BookingSessionValidator extends AbstractValidatorBase
{
    /* @since [*next-version*] */
    use ExpressionBuilderAwareTrait;

    /* @since [*next-version*] */
    use NormalizeIterableCapableTrait;

    /**
     * The SELECT resource model for sessions.
     *
     * @since [*next-version*]
     *
     * @var SelectCapableInterface
     */
    protected $sessionsSelectRm;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param SelectCapableInterface     $sessionsSelectRm The SELECT resource model for sessions.
     * @param ExpressionBuilderInterface $exprBuilder      The expression builder.
     */
    public function __construct($sessionsSelectRm, $exprBuilder)
    {
        $this->_setSessionsSelectRm($sessionsSelectRm);
        $this->_setExpressionBuilder($exprBuilder);
    }

    /**
     * Retrieves the sessions SELECT resource model.
     *
     * @since [*next-version*]
     *
     * @return SelectCapableInterface The sessions SELECT resource model instance.
     */
    protected function _getSessionsSelectRm()
    {
        return $this->sessionsSelectRm;
    }

    /**
     * Sets the sessions SELECT resource model.
     *
     * @since [*next-version*]
     *
     * @param SelectCapableInterface $sessionsSelectRm The sessions SELECT resource model instance.
     */
    protected function _setSessionsSelectRm($sessionsSelectRm)
    {
        if ($sessionsSelectRm !== null && !($sessionsSelectRm instanceof SelectCapableInterface)) {
            throw $this->_createInvalidArgumentException(
                $this->__('Argument is not a SELECT resource model'), null, null, $sessionsSelectRm
            );
        }

        $this->sessionsSelectRm = $sessionsSelectRm;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _getValidationErrors($booking)
    {
        if (!($booking instanceof BookingInterface)) {
            throw $this->_createInvalidArgumentException(
                $this->__('Subject is not a booking instance'), null, null, $booking
            );
        }

        $errors     = [];
        $condition  = $this->_buildBookingSessionCondition($booking);
        $matching   = $this->_getSessionsSelectRm()->select($condition);
        $numMatches = $this->_countIterable($matching);

        if ($numMatches === 0) {
            $errors[] = $this->__('The booking does not match any session');
        }

        return $errors;
    }

    /**
     * Builds the condition for retrieving the matching session for a booking.
     *
     * @since [*next-version*]
     *
     * @param BookingInterface $booking The booking instance for which to retrieve a matching session.
     *
     * @return TermInterface The condition.
     */
    protected function _buildBookingSessionCondition(BookingInterface $booking)
    {
        $b = $this->_getExpressionBuilder();

        $s1 = $b->lit($booking->getStart());
        $s2 = $b->ef('session', 'start');
        $e1 = $b->lit($booking->getEnd());
        $e2 = $b->ef('session', 'end');

        // Booking and session have identical start and end times
        $matchingTime = $b->and(
            $b->eq($s1, $s2),
            $b->eq($e1, $e2)
        );

        if (!($booking instanceof ContainerInterface)) {
            return $matchingTime;
        }

        return $b->and(
            $matchingTime,
            // Booking and session have the same service IDs
            $b->eq(
                $b->ef('service', 'service_id'),
                $b->lit($booking->get('service_id'))
            ),
            // Booking and session have the same resource IDs
            $b->eq(
                $b->ef('service', 'resource_id'),
                $b->lit($booking->get('resource_id'))
            )
        );
    }
}
