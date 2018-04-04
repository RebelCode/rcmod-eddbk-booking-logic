<?php

use Psr\Container\ContainerInterface;
use RebelCode\EddBookings\Logic\Module\EddBookingsLogicModule;

define('EDDBK_BOOKING_LOGIC_MODULE_DIR', __DIR__);
define('EDDBK_BOOKING_LOGIC_MODULE_CONFIG', __DIR__ . DIRECTORY_SEPARATOR . 'config.php');
define('EDDBK_BOOKING_LOGIC_MODULE_KEY', 'eddbk_booking_logic');

return function(ContainerInterface $c) {
    return new EddBookingsLogicModule(
        EDDBK_BOOKING_LOGIC_MODULE_KEY,
        $c->get('container_factory'),
        $c->get('event_manager'),
        $c->get('event_factory')
    );
};
