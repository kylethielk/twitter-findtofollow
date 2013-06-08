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
require_once(dirname(__FILE__) . '/Twitter.php');

/**
 * Controls the flow of following users.
 *
 * Class FTF_Driver_Follow
 */
class FTF_Driver_Follow extends FTF_Driver_Twitter
{
    /**
     * @var FTF_Request_Follow
     */
    private $followRequest;


    /**
     * @param Array $apiKeys Our Twitter API Keys.
     * @param FTF_Request_Follow $followRequest Request from UI.
     */
    public function FTF_Driver_Follow($apiKeys, $followRequest)
    {
        parent::__construct($apiKeys, $followRequest->twitterUsername);
        $this->followRequest = $followRequest;
    }

    /**
     * Start the call to the Twitter API to follow the user specified in our $followRequest.
     * This method is terminating.
     */
    public function followUser()
    {
        //Reset api exchange
        $this->twitterApi = new TwitterAPIExchange($this->apiKeys);

        //Build and send request to twitter api.
        $url = 'https://api.twitter.com/1.1/friendships/create.json';

        $postFields = array(
            'user_id' => $this->followRequest->toFollowUserId,
        );

        $requestMethod = 'POST';

        $response = $this->twitterApi
            ->setPostfields($postFields)
            ->buildOauth($url, $requestMethod)
            ->performRequest();

        $response = json_decode($response);

        $errorMessage = $this->checkForTwitterErrors($response);

        if ($errorMessage !== false)
        {

            FTF_Web::writeErrorResponse("Received error from twitter: " . $errorMessage);
            return;
        }
        else
        {
            //We successfully followed user, add them to our list.
            $this->userData->mergeInFriendIds(array($this->followRequest->toFollowUserId));
            $this->userData->flushPrimaryUserData();
            $this->userData->updateUserData($this->followRequest->toFollowUserId, time());
            //Update follow date
            FTF_Web::writeValidResponse("", $this->generateLog());
        }


    }

}