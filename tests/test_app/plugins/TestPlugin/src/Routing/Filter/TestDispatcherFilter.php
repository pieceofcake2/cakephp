<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.TestApp.Routing.Filter
 * @since         CakePHP(tm) v 2.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace TestPlugin\Routing\Filter;

use Cake\Event\CakeEvent;
use Cake\Network\CakeResponse;
use Cake\Routing\DispatcherFilter;

/**
 * TestDispatcherFilter
 *
 * @package       Cake.Test.TestApp.Routing.Filter
 */
class TestDispatcherFilter extends DispatcherFilter
{
    /**
     * @param CakeEvent $event
     * @return CakeResponse|false|null
     */
    public function beforeDispatch(CakeEvent $event): CakeResponse|false|null
    {
        $event->data['request']->params['altered'] = true;

        return null;
    }

    public function afterDispatch(CakeEvent $event): ?bool
    {
        $event->data['response']->statusCode(304);

        return null;
    }
}
