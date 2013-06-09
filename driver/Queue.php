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
require_once(dirname(__FILE__) . '/Base.php');

/**
 * Controls the flow of following users.
 *
 * Class FTF_Driver_Queue
 */
class FTF_Driver_Queue extends FTF_Driver_Base
{
    /**
     * @var FTF_Request_Base
     */
    private $queueRequest;


    /**
     * @param FTF_Request_Base $queueRequest Request from UI.
     */
    public function FTF_Driver_Queue($queueRequest)
    {
        parent::__construct(FTF_Config::$twitterUsername);
        $this->queueRequest = $queueRequest;
    }

    /**
     * Takes userIds from FTF_Request_QueueAdd and pushes into our queue.
     */
    public function pushUserIdsToQueue()
    {
        if (!($this->queueRequest instanceof FTF_Request_QueueAdd))
        {
            trigger_error("Invalid request passed for addUserIdsToQueue.", E_USER_ERROR);
        }

        $this->userData = new FTF_UserData($this->twitterUsername);
        $this->userData->mergeInUserIdsToQueue($this->queueRequest->queuedUserIds);
        $this->userData->flushPrimaryUserData();
    }

    /**
     * Generates and returns our HTML for all users in the queue.
     * @return string html.
     */
    public function generateHtmlForQueue()
    {
        $users = $this->userData->fetchCachedUsers($this->userData->queuedUserIds);

        $html = '<h1>Users in Queue</h1>';

        $html .= $this->generateUserTablesHtml($users, 'followPage',true);
        return $html;
    }


}