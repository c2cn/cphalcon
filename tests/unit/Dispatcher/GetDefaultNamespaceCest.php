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

namespace Phalcon\Test\Unit\Dispatcher;

use UnitTester;

class GetDefaultNamespaceCest
{
    /**
     * Tests Phalcon\Dispatcher :: getDefaultNamespace()
     *
     * @author Phalcon Team <team@phalconphp.com>
     * @since  2018-11-13
     */
    public function dispatcherGetDefaultNamespace(UnitTester $I)
    {
        $I->wantToTest('Dispatcher - getDefaultNamespace()');

        $I->skipTest('Need implementation');
    }
}
