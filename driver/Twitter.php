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
 * Parent class for all of our driver classes that interact with the Twitter API.
 * Class FTF_Driver_Twitter
 */
class FTF_Driver_Twitter extends FTF_Driver_Base
{
    /**
     * @var array Associative Array of Twitter OAuth Keys.
     */
    protected $apiKeys;
    /**
     * @var TwitterAPIExchange
     */
    protected $twitterApi;

    /**
     * Initialize this driver.
     * @param array $apiKeys Our twitter api keys.
     * @param string $twitterUsername The username for who we are running this app for.
     */
    public function __construct($apiKeys, $twitterUsername)
    {
        parent::__construct($twitterUsername);

        $this->apiKeys = $apiKeys;
        $this->twitterApi = new TwitterAPIExchange($this->apiKeys);


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
     * Pull all friend ids for the supplied user.
     * @param string $username The username to pull friend ids for.
     * @param int $cursor The twitter api cursor.
     * @return array The friend ids.
     */
    public function twitterFriendsIds($username, $cursor = -1)
    {
        $this->twitterApi = new TwitterAPIExchange($this->apiKeys);
        $url = 'https://api.twitter.com/1.1/friends/ids.json';

        $getField = '?cursor=' . $cursor . '&screen_name=' . $username . '&count=150';
        $requestMethod = 'GET';

        $response = $this->twitterApi
            ->setGetfield($getField)
            ->buildOauth($url, $requestMethod)
            ->performRequest();

        $response = json_decode($response);

        $errorMessage = $this->checkForTwitterErrors($response);
        if ($errorMessage === false)
        {

            if ($response->next_cursor > 0)
            {
                return array_merge($response->ids, $this->twitterFriendsIds($username, $response->next_cursor_str));
            }
            else
            {
                return $response->ids;
            }

        }
        else
        {
            $this->addLogMessage("We received a bad response from twitter: " . $errorMessage);
            FTF_Web::writeErrorResponse($errorMessage, $this->generateLog());
            return array();
        }
    }

    /**
     * Get a list of follower ids for the supplied username.
     * @param string $username The person to pull followers for.
     * @param int $maximum Optional, the maximum number of followers to pull.
     * @param int $cursor Optional, The Twitter API cursor.
     * @return array An array with all the follower ids.
     */
    public function twitterFollowersIds($username, $maximum = 75000, $cursor = -1)
    {
        if ($maximum > 75000)
        {
            $maximum = 75000;
        }

        if ($maximum <= 0)
        {
            return array();
        }

        $count = $maximum > 5000 ? 5000 : $maximum;

        $this->twitterApi = new TwitterAPIExchange($this->apiKeys);

        $url = 'https://api.twitter.com/1.1/followers/ids.json';
        $getField = '?cursor=' . $cursor . '&screen_name=' . $username . '&count=' . $count;
        $requestMethod = 'GET';

        $response = json_decode($this->twitterApi
            ->setGetfield($getField)
            ->buildOauth($url, $requestMethod)
            ->performRequest());

        $errorMessage = $this->checkForTwitterErrors($response);
        if ($errorMessage === false)
        {
            if ($response->next_cursor > 0)
            {
                return array_merge($response->ids, $this->twitterFollowersIds($username, $maximum - $count, $response->next_cursor_str));
            }
            else
            {
                return $response->ids;
            }
        }
        else
        {
            $this->addLogMessage("We received a bad response from twitter: " . $errorMessage);
            FTF_Web::writeErrorResponse($errorMessage, $this->generateLog());
            return array();
        }

    }

    /**
     * Call to twitter API to get full profile information for provided userIds. Note can only process 100 userIds at once.
     * @param array $userIds Array of twitter user ids.
     * @return array Of Full Twitter User objects.
     */
    public function twitterUsersLookup($userIds)
    {

        $this->twitterApi = new TwitterAPIExchange($this->apiKeys);

        $url = 'https://api.twitter.com/1.1/users/lookup.json';
        $requestMethod = 'POST';

        if ($userIds && count($userIds) > 0)
        {
            $idString = implode(',', $userIds);

            $postfields = array(
                'user_id' => $idString,
            );


            $response = $this->twitterApi
                ->setPostfields($postfields)
                ->buildOauth($url, $requestMethod)
                ->performRequest();

            $users = json_decode($response);

            $errorMessage = $this->checkForTwitterErrors($users);
            if ($errorMessage === false)
            {
                return $users;
            }
            else
            {
                $this->addLogMessage("We received a bad response from twitter: " . $errorMessage);
                return array();

            }

        }
        return array();
    }
}