<?php

/*
 * This file is part of the Core package in (c)Paybox Integration Component.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Paybox\Core\Abstractions;

use SimpleXMLElement;
use Paybox\Core\Exceptions\Request as RequestException;

/**
 *
 * @package Paybox\Core\Abstractions
 * @version 1.1.0
 * @author Sergey Astapenko <sa@paybox.money> @link https://paybox.money
 * @copyright LLC Paybox.money
 * @license GPLv3 @link https://www.gnu.org/licenses/gpl-3.0-standalone.html
 *
 */

abstract class DataProvider {

    /**
     * Send requests to Paybox and get answer
     *
     * @return void
     */

    protected function send() {
        if($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_URL, $this->getBaseUrl().$this->url);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $this->query);
            $xml = new SimpleXMLElement(curl_exec($curl));
            curl_close($curl);
            if($xml->xpath('//pg_error_code')) {
                throw new RequestException($xml->xpath('//pg_error_code')[0]
                    . ':' .
                    $xml->xpath('//pg_error_description')[0]
                    . PHP_EOL
                );
            } else {
                $this->serverAnswer = (array) $xml;
            }
        }
    }

    /**
     * Get one property of Paybox answer
     *
     * @return string
     */

    protected function getServerAnswer($param) {
        return $this->serverAnswer[$this->prefix . $this->delimeter . $param];
    }

    /**
     * Get one property of Paybox answer
     *
     * @return string
     */

    protected function getServerArrayAnswer($param) {
        return $this->serverAnswer[$this->prefix . $this->delimeter . $param];
    }

    /**
     * Get full answer of Paybox
     *
     * @return array
     */

    public function getFullServerAnswer() {
        return $this->serverAnswer;
    }

}
