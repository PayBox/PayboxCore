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
 * @version 1.2.2
 * @author Sergey Astapenko <sa@paybox.money> @link https://paybox.money
 * @copyright LLC Paybox.money
 * @license GPLv3 @link https://www.gnu.org/licenses/gpl-3.0-standalone.html
 *
 */

interface Payout {

    public function reg2reg();
    public function reg2nonreg();
    public function toIban();
    public function getStatus():string;
    public function getBalance():string;

}
