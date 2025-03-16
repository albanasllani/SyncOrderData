<?php
declare(strict_types=1);

namespace Dotdigital\Flow\Core\Framework\Event;

use Shopware\Core\Framework\Event\FlowEventAware;
use Shopware\Core\Framework\Event\OrderAware;

interface SendOrderStatusUpdateAware extends FlowEventAware, OrderAware
{
}
