<?php

namespace RebelCode\EddBookings\Logic\Module;

use RebelCode\EddBookings\Logic\Module\BookingStatusInterface as S;
use Dhii\Expression\TermInterface;
use Dhii\Storage\Resource\SelectCapableInterface;
use Dhii\Util\Normalization\NormalizeIterableCapableTrait;
use Dhii\Validation\AbstractValidatorBase;
use Dhii\Validation\ValidatorInterface;
use Psr\Container\ContainerInterface;
use RebelCode\Bookings\BookingInterface;
use RebelCode\Expression\Builder\ExpressionBuilderAwareTrait;
use RebelCode\Expression\Builder\ExpressionBuilderInterface;

/**
 * A booking validator that validates whether a booking conflicts with another.
 *
 * @since [*next-version*]
 */
class BookingConflictValidator extends AbstractValidatorBase implements ValidatorInterface
{
    /* @since [*next-version*] */
    use ExpressionBuilderAwareTrait;

    /* @since [*next-version*] */
    use NormalizeIterableCapableTrait;

    /**
     * The SELECT resource model for bookings.
     *
     * @since [*next-version*]
     *
     * @var SelectCapableInterface
     */
    protected $bookingsSelectRm;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param SelectCapableInterface     $bookingsSelectRm The SELECT resource model for bookings.
     * @param ExpressionBuilderInterface $exprBuilder      The expression builder.
     */
    public function __construct($bookingsSelectRm, $exprBuilder)
    {
        $this->_setBookingsSelectRm($bookingsSelectRm);
        $this->_setExpressionBuilder($exprBuilder);
    }

    /**
     * Retrieves the bookings SELECT resource model.
     *
     * @since [*next-version*]
     *
     * @return SelectCapableInterface The bookings SELECT resource model instance.
     */
    protected function _getBookingsSelectRm()
    {
        return $this->bookingsSelectRm;
    }

    /**
     * Sets the bookings SELECT resource model.
     *
     * @since [*next-version*]
     *
     * @param SelectCapableInterface $bookingsSelectRm The bookings SELECT resource model instance.
     */
    protected function _setBookingsSelectRm($bookingsSelectRm)
    {
        if ($bookingsSelectRm !== null && !($bookingsSelectRm instanceof SelectCapableInterface)) {
            throw $this->_createInvalidArgumentException(
                $this->__('Argument is not a SELECT resource model'), null, null, $bookingsSelectRm
            );
        }

        $this->bookingsSelectRm = $bookingsSelectRm;
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

        $errors       = [];
        $condition    = $this->_buildBookingConflictCondition($booking);
        $conflicts    = $this->_getBookingsSelectRm()->select($condition);
        $numConflicts = count($conflicts);

        if ($numConflicts > 0) {
            $errors[] = $this->__('The booking conflicts with %d other booking(s)', [$numConflicts]);
        }

        return $errors;
    }

    /**
     * Builds the condition for retrieving the conflicting bookings.
     *
     * @since [*next-version*]
     *
     * @param BookingInterface $booking The booking instance for which to retrieve conflicting bookings.
     *
     * @return TermInterface The built condition.
     */
    protected function _buildBookingConflictCondition(BookingInterface $booking)
    {
        $b = $this->_getExpressionBuilder();

        $s1 = $b->lit($booking->getStart());
        $s2 = $b->ef('booking', 'start');
        $e1 = $b->lit($booking->getEnd());
        $e2 = $b->ef('booking', 'end');

        // This booking starts within range of another
        // or
        // Another booking starts within range of this booking
        $overlap = $b->or(
            $b->and(
                $b->gte($s1, $s2),
                $b->lt($s1, $e2)
            ),
            $b->and(
                $b->gte($s2, $s1),
                $b->lt($s2, $e1)
            )
        );

        if (!($booking instanceof ContainerInterface)) {
            return $overlap;
        }

        return $b->and(
            $overlap,
            // Booking status is either of the below:
            $b->or(
            // Booking status is `approved`
                $b->eq(
                    $b->ef('booking', 'status'),
                    $b->lit(S::STATUS_APPROVED)
                ),
                // Booking status is `scheduled`
                $b->eq(
                    $b->ef('booking', 'status'),
                    $b->lit(S::STATUS_SCHEDULED)
                )
            ),
            // Bookings' service IDs are the same
            $b->eq(
                $b->ef('booking', 'service_id'),
                $b->lit($booking->get('service_id'))
            ),
            // Bookings' resource IDs are the same
            $b->eq(
                $b->ef('booking', 'resource_id'),
                $b->lit($booking->get('resource_id'))
            )
        );
    }
}
