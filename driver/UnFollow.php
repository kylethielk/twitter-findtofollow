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
 * Controls the flow of unfollowing users.
 *
 * Class FTF_Driver_UnFollow
 */
class FTF_Driver_UnFollow extends FTF_Driver_Twitter
{

    /**
     * @var FTF_Request_UnFollow
     */
    private $unFollowRequest;

    /**
     * @param Array $apiKeys Our Twitter API Keys.
     * @param FTF_Request_UnFollow $unFollowRequest Optional request. Only needed if we are unfollowing a user with this instance.
     */
    public function FTF_Driver_UnFollow($apiKeys, $unFollowRequest = null)
    {
        parent::__construct($apiKeys, FTF_Config::$twitterUsername);
        $this->unFollowRequest = $unFollowRequest;
    }

    /**
     * Start the call to the Twitter API to unfollow the user specified in our $unFollowRequest.
     * This method is terminating.
     */
    public function unFollowUser()
    {
        //Reset api exchange
        $this->twitterApi = new TwitterAPIExchange($this->apiKeys);

        //Build and send request to twitter api.
        $url = 'https://api.twitter.com/1.1/friendships/destroy.json';

        $postFields = array(
            'user_id' => $this->unFollowRequest->toUnFollowUserId,
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
            //We successfully unfollowed user, set unfollow date.
            $this->userData->updateUserData($this->unFollowRequest->toUnFollowUserId, -1, time());
            //Update follow date
            FTF_Web::writeValidResponse("", $this->generateLog());
        }


    }

    /**
     * Generates and returns our HTML for all users in the queue.
     * @return string html.
     */
    public function generateHtmlForUsers()
    {
        //Get list of users we current follow
        $friendIds = $this->twitterFriendsIds($this->twitterUsername);
        $followerIds = $this->twitterFollowersIds($this->twitterUsername);

        $nonFollowerIds = array_diff($friendIds, $followerIds);

        $users = array_reverse($this->fetchUserData($nonFollowerIds));

        $html = '<h1>Users Who Do Not Follow Back</h1>';

        $html .= $this->generateUserTablesHtml($users, 'unFollowPage', false);
        return $html;
    }


}