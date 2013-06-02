<?php
/**
 * Author: Kyle Thielk (www.kylethielk.com)
 * License:
 * Copyright (c) 2013 Kyle Thielk
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
require_once('UserData.php');

/**
 * Parent class for all of our driver classes that interact with the Twitter API.
 * Class FTF_TwitterDriver
 */
class FTF_TwitterDriver
{
    /**
     * @var array Associative Array of Twitter OAuth Keys.
     */
    protected $apiKeys;
    /**
     * @var FTF_UserData
     */
    protected $userData;
    /**
     * @var TwitterAPIExchange
     */
    protected $twitterApi;
    /**
     * @var array Log Messages.
     */
    private $logArray = array();

    public function __construct($apiKeys, $twitterUsername)
    {
        $this->apiKeys = $apiKeys;
        $this->twitterUsername = $twitterUsername;

        $this->userData = new FTF_UserData($twitterUsername);
    }

    /**
     * Checks response for errors and returns string if has error. false otherwise.
     * @param $response Object The response object received from Twitter that contains errors.
     * @return String the error messages.
     */
    public function checkForTwitterErrors($response)
    {
        if ($response && isset($response->errors))
        {
            $errors = $response->errors;
            $message = '';
            foreach ($errors as $error)
            {
                $message = $message . $error->message . '<br />';
            }
            return $message;
        }

        return false;

    }

    /**
     * Generates a string of all log entries.
     * @return string Log String.
     */
    public function generateLog()
    {
        $message = '';
        foreach ($this->logArray as $log)
        {
            $message .= $log;
        }
        return $message;
    }

    /**
     * Add a message to the log.
     * @param $message String The message to add to the log.
     */
    public function addLogMessage($message)
    {
        $this->logArray[] = $message . '<br />';
    }
}