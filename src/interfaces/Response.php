<?php

/*
 * This file is part of the Core package in (c)Paybox Integration Component.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Paybox\Core\Interfaces;

/**
 *
 * @package Paybox\Core\Interfaces
 * @version 1.1.0
 * @author Sergey Astapenko <sa@paybox.money> @link https://paybox.money
 * @copyright LLC Paybox.money
 * @license GPLv3 @link https://www.gnu.org/licenses/gpl-3.0-standalone.html
 *
 */

interface Response {

    public function error($errorDescription);
    public function accept($successMessage);
    public function waiting($waitingTimer);
    public function cancel($cancelDescription);
    public function refunded();
    public function captured();

}
