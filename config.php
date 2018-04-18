<?php

use RebelCode\EddBookings\Logic\Module\BookingStatusInterface as S;
use RebelCode\EddBookings\Logic\Module\BookingTransitionInterface as T;

return [
    'booking_logic' => [
        'transition_event_format' => 'on_booking_transition',
        'status_transitions'      => [
            S::STATUS_NONE      => [
                T::TRANSITION_CART  => S::STATUS_IN_CART,
                T::TRANSITION_DRAFT => S::STATUS_DRAFT,
            ],
            S::STATUS_IN_CART   => [
                T::TRANSITION_SUBMIT => S::STATUS_PENDING,
            ],
            S::STATUS_DRAFT     => [
                T::TRANSITION_SUBMIT => S::STATUS_PENDING,
                T::TRANSITION_DRAFT  => S::STATUS_DRAFT,
            ],
            S::STATUS_PENDING   => [
                T::TRANSITION_APPROVE => S::STATUS_APPROVED,
                T::TRANSITION_REJECT  => S::STATUS_REJECTED,
            ],
            S::STATUS_APPROVED  => [
                T::TRANSITION_SCHEDULE => S::STATUS_SCHEDULED,
                T::TRANSITION_CANCEL   => S::STATUS_CANCELLED,
            ],
            S::STATUS_REJECTED  => [
                T::TRANSITION_SUBMIT => S::STATUS_PENDING,
            ],
            S::STATUS_SCHEDULED => [
                T::TRANSITION_COMPLETE => S::STATUS_COMPLETED,
                T::TRANSITION_CANCEL   => S::STATUS_CANCELLED,
            ],
            S::STATUS_COMPLETED => [
                T::TRANSITION_DRAFT    => S::STATUS_DRAFT,
                T::TRANSITION_SUBMIT   => S::STATUS_PENDING,
                T::TRANSITION_APPROVE  => S::STATUS_APPROVED,
                T::TRANSITION_SCHEDULE => S::STATUS_SCHEDULED,
                T::TRANSITION_CANCEL   => S::STATUS_CANCELLED,
            ],
            S::STATUS_CANCELLED => [
                T::TRANSITION_DRAFT    => S::STATUS_DRAFT,
                T::TRANSITION_SUBMIT   => S::STATUS_PENDING,
                T::TRANSITION_APPROVE  => S::STATUS_APPROVED,
                T::TRANSITION_SCHEDULE => S::STATUS_SCHEDULED,
                T::TRANSITION_COMPLETE => S::STATUS_COMPLETED,
            ],
        ],
    ],
];
