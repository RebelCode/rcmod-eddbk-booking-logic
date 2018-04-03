<?php

return [
    'booking_event_state_machine' => [
        'event_name_format' => 'on_booking_transition'
    ],
    'booking_status_transitions' => [
        'none' => [
            'in_cart',
            'draft'
        ],
        'in_cart' => [
            'pending'
        ],
        'draft' => [
            'pending'
        ],
        'pending' => [
            'approve',
            'reject'
        ],
        'approved' => [
            'schedule',
            'cancel'
        ],
        'rejected' => [
        ],
        'scheduled' => [
            'complete',
            'cancel'
        ],
        'completed' => [
            'draft',
            'pending',
            'approved',
            'scheduled',
            'completed'
        ],
        'cancelled' => [
            'draft',
            'pending',
            'approved',
            'scheduled',
            'completed'
        ]
    ]
];
