<?php

namespace RebelCode\EddBookings\Logic\Module;

use Dhii\Data\Container\ContainerGetCapableTrait;
use Dhii\Data\Container\CreateContainerExceptionCapableTrait;
use Dhii\Data\Container\CreateNotFoundExceptionCapableTrait;
use Dhii\Data\Container\NormalizeKeyCapableTrait;
use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\Exception\CreateOutOfRangeExceptionCapableTrait;
use Dhii\Factory\Exception\CreateCouldNotMakeExceptionCapableTrait;
use Dhii\Factory\Exception\CreateFactoryExceptionCapableTrait;
use Dhii\Factory\FactoryInterface;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use Psr\Container\ContainerInterface;
use RebelCode\EddBookings\Logic\Module\BookingStatusInterface as S;

/**
 * The factory that creates the condition that is used to query for bookings that conflict with a specific booking.
 *
 * @since [*next-version*]
 */
class BookingConflictConditionFactory implements FactoryInterface
{
    /* @since [*next-version*] */
    use ContainerGetCapableTrait;

    /* @since [*next-version*] */
    use NormalizeKeyCapableTrait;

    /* @since [*next-version*] */
    use NormalizeStringCapableTrait;

    /* @since [*next-version*] */
    use CreateInvalidArgumentExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateOutOfRangeExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateNotFoundExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateContainerExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateFactoryExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateCouldNotMakeExceptionCapableTrait;

    /* @since [*next-version*] */
    use StringTranslatingTrait;

    /**
     * The expression builder.
     *
     * @since [*next-version*]
     *
     * @var object
     */
    protected $exprBuilder;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param object $exprBuilder The expression builder.
     */
    public function __construct($exprBuilder)
    {
        $this->exprBuilder = $exprBuilder;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function make($config = null)
    {
        $b = $this->exprBuilder;

        $booking = $this->_containerGet($config, 'booking');

        $s1 = $b->lit($booking->getStart());
        $s2 = $b->ef('booking', 'start');
        $e1 = $b->lit($booking->getEnd());
        $e2 = $b->ef('booking', 'end');

        // This booking starts within range of another booking in storage
        // or a booking in storage starts within range of this booking
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
