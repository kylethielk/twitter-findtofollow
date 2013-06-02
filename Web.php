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

require_once('WebRequest.php');
require_once('WebResponse.php');
require_once('Driver.php');

/**
 * Executes AJAX requests from html file.
 * Class FTF_Web
 */
class FTF_Web
{
    const ACTION_RUN = 'run';
    const ACTION_FOLLOW = 'follow';
    /**
     * @var FTF_Driver
     */
    static public $currentDriver = null;

    /**
     * Execute the ajax request.
     * @param $data $_POST data.
     */
    public static function executeRequest($data)
    {
        if (!isset($data) || !isset($data['action']))
        {
            return;
        }
        $action = $data['action'];

        if ($action == FTF_Web::ACTION_RUN)
        {
            FTF_Web::filterFollowers($data);
        }
        else if ($action == FTF_Web::ACTION_FOLLOW)
        {
            FTF_Web::followUser($data);
        }
    }

    private static function followUser($data)
    {
        sleep(2);
        FTF_Web::writeValidResponse(print_r($data, true), "");
    }

    /**
     * Start the process and find filtered users to follow.
     * @param $data $_POST data.
     */
    private static function filterFollowers($data)
    {
        global $apiKeys;

        $settings = new FTF_WebRequest($data);

        if (!isset($settings) || !$settings->validate())
        {
            FTF_Web::writeErrorResponse('Settings invalid or not supplied.' . print_r($settings, true));
        }
        else
        {
            $findToFollow = new FTF_Driver($apiKeys, $settings);

            FTF_Web::$currentDriver = $findToFollow;

            set_error_handler(array('FTF_Web', 'errorHandler'));

            $findToFollow->buildFriendIds();
            $findToFollow->buildFollowerIds();
            $findToFollow->buildFilteredFollowers();
            FTF_Web::writeValidResponse($findToFollow->generateHtml(), $findToFollow->generateLog());
        }
    }

    /**
     * Write response to browser.
     * @param $html String The html to write.
     * @param $log String a string for the log.
     */
    private static function writeValidResponse($html, $log)
    {
        $response = new FTF_WebResponse();
        $response->hasError = false;
        $response->errorMessage = '';
        $response->html = $html;
        $response->log = $log;

        echo json_encode($response);
        exit(3);
    }

    /**
     * Write response to browser and mark it as having an error.
     * @param $errorMessage String The error message to write.
     * @param $log String Optional.
     *
     */
    public static function writeErrorResponse($errorMessage, $log = '')
    {
        $response = new FTF_WebResponse();
        $response->hasError = true;
        $response->errorMessage = $errorMessage;
        $response->log = $log;

        echo json_encode($response);
        exit(3);

    }

    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (!(error_reporting() & $errno))
        {
            // This error code is not included in error_reporting
            return false;
        }

        FTF_Web::writeErrorResponse('Unknown error happened. Error Message [' . $errstr . '] at ' . $errfile . ' (' . $errline . ')', FTF_Web::$currentDriver->generateLog());

        /* Don't execute PHP internal error handler */
        return true;
    }

}

?>