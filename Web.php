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

require_once(dirname(__FILE__) . '/request/QueueAdd.php');
require_once(dirname(__FILE__) . '/request/QueueFetch.php');
require_once(dirname(__FILE__) . '/request/Filter.php');
require_once(dirname(__FILE__) . '/request/Follow.php');
require_once(dirname(__FILE__) . '/WebResponse.php');
require_once(dirname(__FILE__) . '/driver/Filter.php');
require_once(dirname(__FILE__) . '/driver/Follow.php');
require_once(dirname(__FILE__) . '/driver/Queue.php');

/**
 * Executes AJAX requests from html file.
 * Class FTF_Web
 */
class FTF_Web
{
    const ACTION_RUN = 'run';
    const ACTION_FOLLOW = 'follow';
    const ACTION_ADD_QUEUE = 'addqueue';
    const ACTION_FETCH_QUEUE = 'fetchqueue';

    /**
     * @var FTF_Driver_Base
     */
    static public $currentDriver = null;

    /**
     * Execute the ajax request.
     * @param array $data $_POST data.
     */
    public static function executeRequest($data)
    {
        if (!isset($data) || !isset($data['action']))
        {
            return;
        }
        else if (!FTF_Web::validateConfig())
        {
            FTF_Web::writeErrorResponse("Config.php has not been setup or configured properly.");
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
        else if ($action == FTF_Web::ACTION_ADD_QUEUE)
        {
            FTF_Web::addToQueue($data);
        }
        else if ($action == FTF_Web::ACTION_FETCH_QUEUE)
        {
            FTF_Web::fetchQueue($data);
        }
    }

    /**
     * Process request to fetch queue.
     * @param array $data $_POST
     */
    public static function fetchQueue($data)
    {
        $request = new FTF_Request_QueueFetch($data);

        if (!isset($request) || !$request->validate())
        {
            FTF_Web::writeErrorResponse('Queue Request invalid or not supplied.' . print_r($request, true));
        }

        $queue = new FTF_Driver_Queue($request);

        FTF_Web::writeValidResponse($queue->generateHtmlForQueue(), "");
    }

    /**
     * Process request to add users to queue.
     * @param array $data $_POST
     */
    public static function addToQueue($data)
    {
        $request = new FTF_Request_QueueAdd($data);

        if (!isset($request) || !$request->validate())
        {
            FTF_Web::writeErrorResponse('Queue Request invalid or not supplied.' . print_r($request, true));
        }

        $queue = new FTF_Driver_Queue($request);
        $queue->pushUserIdsToQueue();

        FTF_Web::writeValidResponse("", "");
    }

    /**
     * Executes the web request to follow an user.
     * @param array $data $_POST data.
     */
    private static function followUser($data)
    {
        $request = new FTF_Request_Follow($data);

        if (!isset($request) || !$request->validate())
        {
            FTF_Web::writeErrorResponse('Follow Request invalid or not supplied.' . print_r($request, true));
        }


        $follow = new FTF_Driver_Follow(FTF_Config::$apiKeys, $request);
        FTF_Web::$currentDriver = $follow;

        $follow->followUser();
    }

    /**
     * Start the process and find filtered users to follow.
     * @param array $data $_POST data.
     */
    private static function filterFollowers($data)
    {

        $settings = new FTF_Request_Filter($data);

        if (!isset($settings) || !$settings->validate())
        {
            FTF_Web::writeErrorResponse('Settings invalid or not supplied.' . print_r($settings, true));
        }
        else
        {
            $findToFollow = new FTF_Driver_Filter(FTF_Config::$apiKeys, $settings);

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
    public static function writeValidResponse($html, $log)
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

    /**
     * Our custom PHP error handler.
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     * @return bool
     */
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

    /**
     * Validates our config has been setup.
     * @return bool True if valid, false otherwise.
     */
    public static function validateConfig()
    {

        if (!class_exists('FTF_Config'))
        {
            return false;
        }
        else if (empty(FTF_Config::$twitterUsername) || FTF_Config::$twitterUsername == 'CHANGE_THIS')
        {
            return false;
        }
        else if (FTF_Config::$apiKeys['oauth_access_token'] == 'CHANGE_THIS'
            || FTF_Config::$apiKeys['oauth_access_token_secret'] == 'CHANGE_THIS'
            || FTF_Config::$apiKeys['consumer_key'] == 'CHANGE_THIS'
            || FTF_Config::$apiKeys['consumer_secret'] == 'CHANGE_THIS'
        )
        {
            return false;
        }

        return true;

    }

}

?>