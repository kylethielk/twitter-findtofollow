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
        parent::__construct($apiKeys, FTF_Config::$twitterUsername);
        $this->followRequest = $followRequest;
    }

    /**
     * Checks an array of error codes to see if one of them means the user is no longer valid
     * and can thus be removed from queue.
     * @param $errorCodeArray Array of error codes.
     * @return boolean True if yes, false otherwise.
     */
    private function userDoesNotExist($errorCodeArray)
    {
        foreach ($errorCodeArray as $key => $code)
        {
            if ($code == 34)
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Start the call to the Twitter API to follow the user specified in our $followRequest.
     * This method is terminating.
     */
    public function followUser()
    {
        $twitterOAuth = new TwitterOAuth(FTF_Config::$apiKeys['consumer_key'],
            FTF_Config::$apiKeys['consumer_secret'],
            FTF_UserData::getUserData()->currentUser()->oauthToken,
            FTF_UserData::getUserData()->currentUser()->oauthSecret);

        $response = $twitterOAuth->post('friendships/create', array('user_id' => $this->followRequest->toFollowUserId));


        $errorMessage = $this->checkForTwitterErrors($response);

        if ($errorMessage !== false)
        {
            $errorCodes = $this->parseErrorCodesFromResponse($response);

            if ($this->userDoesNotExist($errorCodes))
            {
                //Remove user from queue if error
                FTF_UserData::getUserData()->removeUserIdFromQueue($this->followRequest->toFollowUserId);
                FTF_UserData::getUserData()->flushPrimaryUserData();
                FTF_Web::writeErrorResponse("Received error from twitter: " . $errorMessage . ". Removing user from queue.", '', true);
            } else
            {
                FTF_Web::writeErrorResponse("Received error from twitter: " . $errorMessage);
            }

            return;
        } else
        {
            //We successfully followed user, add them to our list.
            FTF_UserData::getUserData()->mergeInFriendIds(array($this->followRequest->toFollowUserId));
            FTF_UserData::getUserData()->removeUserIdFromQueue($this->followRequest->toFollowUserId);
            FTF_UserData::getUserData()->flushPrimaryUserData();
            FTF_UserData::getUserData()->updateUserData($this->followRequest->toFollowUserId, time());
            //Update follow date
            FTF_Web::writeValidResponse("", $this->generateLog());
        }


    }

}