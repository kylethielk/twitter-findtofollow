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
     * Initialize this driver.
     */
    public function __construct()
    {
        parent::__construct();


    }

    /**
     * Checks response for errors and returns string if has error. false otherwise.
     * @param $response Object The response object received from Twitter that contains errors.
     * @return String the error messages.
     */
    public static function checkForTwitterErrors($response)
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
     * Parses a response received from twitter and will return an array of the codes.
     * @param $response Object The response object received from Twitter that contains errors.
     * @return array Empty array if no error, array of error code strings otherwise.
     */
    public static function parseErrorCodesFromResponse($response)
    {
        $errorCodes = array();
        if ($response && isset($response->errors))
        {
            $errors = $response->errors;

            foreach ($errors as $error)
            {
                $errorCodes[] = $error->code;
            }
        }

        return $errorCodes;
    }

    /**
     * Pull all friend ids for the supplied user.
     * @param string $username The username to pull friend ids for.
     * @param int $cursor The twitter api cursor.
     * @return array The friend ids.
     */
    public function twitterFriendsIds($username, $cursor = -1)
    {
        $twitterOAuth = new TwitterOAuth(FTF_Config::$apiKeys['consumer_key'],
            FTF_Config::$apiKeys['consumer_secret'],
            FTF_UserData::getUserData()->currentUser()->oauthToken,
            FTF_UserData::getUserData()->currentUser()->oauthSecret);

        $response = $twitterOAuth->get('friends/ids', array('cursor' => $cursor, 'screen_name' => $username, 'count' => 5000));

        $errorMessage = $this->checkForTwitterErrors($response);
        if ($errorMessage === false && isset($response))
        {

            if ($response->next_cursor > 0)
            {
                return array_merge($response->ids, $this->twitterFriendsIds($username, $response->next_cursor_str));
            } else
            {
                return $response->ids;
            }

        } else
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

        $twitterOAuth = new TwitterOAuth(FTF_Config::$apiKeys['consumer_key'],
            FTF_Config::$apiKeys['consumer_secret'],
            FTF_UserData::getUserData()->currentUser()->oauthToken,
            FTF_UserData::getUserData()->currentUser()->oauthSecret);

        $response = $twitterOAuth->get('followers/ids', array('cursor' => $cursor, 'screen_name' => $username, 'count' => $count));


        $errorMessage = $this->checkForTwitterErrors($response);
        if ($errorMessage === false)
        {
            if ($response->next_cursor > 0)
            {
                return array_merge($response->ids, $this->twitterFollowersIds($username, $maximum - $count, $response->next_cursor_str));
            } else
            {
                return $response->ids;
            }
        } else
        {
            $this->addLogMessage("We received a bad response from twitter: " . $errorMessage);
            FTF_Web::writeErrorResponse($errorMessage, $this->generateLog());
            return array();
        }

    }

    /**
     * Given an array of user ids, we will determine if we have their data cached, if so we will use that, otherwise we will fetch it.
     * @param array $userIds Array of ids.
     * @return array of user data.
     */
    public function fetchUserData($userIds)
    {
        if (!is_array($userIds) || count($userIds) < 1)
        {
            return array();
        }

        //Get a list of fresh and cached ids, so we don't fetch profiles for people we already have
        $idObject = $this->breakIdsByCached($userIds);

        //Get new and cached user data, and merge it so we can filter.
        $newUsers = $this->fetchUserDataFromTwitter($idObject->freshUserIds);
        $cachedFollowers = FTF_UserData::getUserData()->fetchCachedUsers($idObject->cachedUserIds);

        return array_merge($newUsers, $cachedFollowers);
    }

    /**
     * Fetches full user objects from twitter.
     * @private
     * @param $userIds array Twitter user id's to fetch data for.
     * @return array|mixed Array of user data.
     */
    private function fetchUserDataFromTwitter($userIds)
    {

        $users = $this->twitterUsersLookup($userIds);

        //Write new data to cache.
        foreach ($users as $user)
        {
            $friend = new FTF_Friend($user);
            FTF_UserData::getUserData()->writeUserToCache($friend);
        }
        FTF_UserData::getUserData()->flushUserListCache();


        return $users;
    }

    /**
     * Call to twitter API to get full profile information for provided userIds. Note can only process 100 userIds at once.
     * @param array $userIds Array of twitter user ids.
     * @return array Of Full Twitter User objects.
     */
    public function twitterUsersLookup($userIds)
    {

        if ($userIds && count($userIds) > 0)
        {
            $idString = implode(',', $userIds);


            $twitterOAuth = new TwitterOAuth(FTF_Config::$apiKeys['consumer_key'],
                FTF_Config::$apiKeys['consumer_secret'],
                FTF_UserData::getUserData()->currentUser()->oauthToken,
                FTF_UserData::getUserData()->currentUser()->oauthSecret);

            $users = $twitterOAuth->post('users/lookup', array('user_id' => $idString));


            $errorMessage = $this->checkForTwitterErrors($users);
            if ($errorMessage === false)
            {
                return $users;
            } else
            {
                $this->addLogMessage("We received a bad response from twitter: " . $errorMessage);
                return array();

            }

        }
        return array();
    }

    /**
     * Given an array of twitter userids, break out the ids we already have cached locally so that we don't have to fetch them from twitter.
     * @private
     * @param $ids Array of twitter ids.
     * @return Object An object with two arrays, {cachedUserIds: [], freshUserIds: []}
     */
    public function breakIdsByCached($ids)
    {
        $returnObject = (Object)array();
        $returnObject->freshUserIds = array();
        $returnObject->cachedUserIds = array();

        foreach ($ids as $id)
        {
            if (in_array($id, FTF_UserData::getUserData()->cachedUserIds))
            {
                $returnObject->cachedUserIds[] = $id;
            } else
            {
                $returnObject->freshUserIds[] = $id;
            }
        }

        $this->addLogMessage('Pulling fresh profiles for ' . count($returnObject->freshUserIds) . ' users from twitter. Had ' . count($returnObject->cachedUserIds) . ' cached.');

        return $returnObject;
    }
}