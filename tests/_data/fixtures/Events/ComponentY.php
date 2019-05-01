<?php
declare(strict_types=1);

/**
 * This file is part of the Phalcon Framework.
 *
 * (c) Phalcon Team <team@phalconphp.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

use Phalcon\Events\Manager;

class ComponentY
{
    /**
     * @var Manager
     */
    protected $eventsManager;

    public function setEventsManager(Manager $eventsManager)
    {
        $this->eventsManager = $eventsManager;
    }

    /**
     * @return Manager
     */
    public function getEventsManager()
    {
        return $this->eventsManager;
    }

    public function leAction()
    {
        $this->eventsManager->fire('another:beforeAction', $this);
        $this->eventsManager->fire('another:afterAction', $this);
    }
}
