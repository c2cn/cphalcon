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

namespace Phalcon\Test\Unit\Cache\Frontend\Base64;

use UnitTester;

class StartCest
{
    /**
     * Tests Phalcon\Cache\Frontend\Base64 :: start()
     *
     * @author Phalcon Team <team@phalconphp.com>
     * @since  2018-11-13
     */
    public function cacheFrontendBase64Start(UnitTester $I)
    {
        $I->wantToTest('Cache\Frontend\Base64 - start()');

        $I->skipTest('Need implementation');
    }
}
