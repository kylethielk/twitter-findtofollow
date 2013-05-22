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
 * Controls the flow of the application. General execution:
 *
 * buildFriendIds();
 * buildFollowerIds();
 * buildFilteredFollowers();
 *
 * Class FTF_Driver
 */
class FTF_Driver
{

    /**
     * @var FTF_UserData
     */
    private $userData;
    /**
     * @var array Array of Twitter OAuth Keys.
     */
    private $apiKeys;
    /**
     * @var FTF_WebRequest
     */
    private $webRequest;
    /**
     * @var Array Ids of potential friends.
     */
    private $potentialFriendIds;
    /**
     * @var timer Timer class.
     */
    private $timer;
    /**
     * @var Array of users that matched our filter criteria. These are the final resuts.
     */
    public $filteredUsers;
    /**
     * @var TwitterAPIExchange
     */
    private $twitterApi;
    /**
     * @var array Log Messages.
     */
    private $logArray = array();

    /**
     * @param $apiKeys array .
     * @param $webRequest FTF_WebRequest .
     */
    public function FTF_Driver($apiKeys, $webRequest)
    {
        $this->apiKeys = $apiKeys;
        $this->webRequest = $webRequest;
        $this->timer = new timer;
        $this->timer->set_output(2);

        $this->userData = new FTF_UserData($this->webRequest->twitterUsername);
    }

    /**
     * Populate our driver with a list of people we are currently following.
     */
    public function buildFriendIds()
    {
        //Reset api exchange
        $this->twitterApi = new TwitterAPIExchange($this->apiKeys);

        $url = 'https://api.twitter.com/1.1/friends/ids.json';
        $getfield = '?cursor=-1&screen_name=' . $this->webRequest->twitterUsername . '&count=5000';
        $requestMethod = 'GET';

        $this->timer->add_cp('Start: Building Friend Ids ');

        $response = $this->twitterApi
            ->setGetfield($getfield)
            ->buildOauth($url, $requestMethod)
            ->performRequest();

        $this->timer->add_cp('End: Building Friend Ids ');

        $friendIdResponse = json_decode($response);

        $this->addLogMessage('You have ' . ($friendIdResponse && $friendIdResponse->ids ? count($friendIdResponse->ids) : 0) . ' friends according to twitter.');

        $this->userData->mergeInFriendIds($friendIdResponse->ids);
        $this->userData->flushPrimaryUserData();


    }

    /**
     * Get a list of all followers of sourceUsername up to WebRequest->followerLimit.
     */
    public function buildFollowerIds()
    {
        if (!isset($this->webRequest->followerLimit) || $this->webRequest->followerLimit > 5000)
        {
            $this->webRequest->followerLimit = 5000;
        }

        $this->twitterApi = new TwitterAPIExchange($this->apiKeys);

        $url = 'https://api.twitter.com/1.1/followers/ids.json';
        $getField = '?cursor=-1&screen_name=' . $this->webRequest->sourceUsername . '&count=' . $this->webRequest->followerLimit;
        $requestMethod = 'GET';

        $this->timer->add_cp('Start: Building Follower Ids ');

        $followerIdResponse = json_decode($this->twitterApi
            ->setGetfield($getField)
            ->buildOauth($url, $requestMethod)
            ->performRequest());

        $this->timer->add_cp('End: Building Follower Ids ');

        $this->potentialFriendIds = $followerIdResponse->ids;
    }

    /**
     * We don't care about people we are already following, remove them from the list of people
     * we are about to filter.
     */
    public function removeUsersAlreadyFollowed()
    {
        $before_remove_count = count($this->potentialFriendIds);
        $this->potentialFriendIds = array_diff($this->potentialFriendIds, $this->userData->friendIds);
        $after_remove_count = count($this->potentialFriendIds);

        $this->addLogMessage('Removed a total of ' . ($before_remove_count - $after_remove_count) . ' people because you are already following them.');
    }

    /**
     * Given an array of twitter userids, break out the ids we already have cached locally so that we don't have to fetch them from twitter.
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
            if (in_array($id, $this->userData->cachedUserIds))
            {
                $returnObject->cachedUserIds[] = $id;
            }
            else
            {
                $returnObject->freshUserIds[] = $id;
            }
        }

        $this->addLogMessage('Pulling fresh profiles for ' . count($returnObject->freshUserIds) . ' users from twitter. Had ' . count($returnObject->cachedUserIds) . ' cached.');

        return $returnObject;
    }

    /**
     * Fetches full user objects from twitter.
     * @param $twitterApi TwitterAPIExchange
     * @param $userIds array Twitter user id's to fetch data for.
     * @return array|mixed Array of user data.
     */
    private function fetchUserDataFromTwitter($twitterApi, $userIds)
    {
        $users = array();

        $url = 'https://api.twitter.com/1.1/users/lookup.json';
        $requestMethod = 'POST';

        if ($userIds && count($userIds) > 0)
        {
            $ids_string = implode(',', $userIds);

            $postfields = array(
                'user_id' => $ids_string,
            );


            $random = rand(0, 15000);
            $this->timer->add_cp('Start: Get User Information - ' . $random);

            $response = $twitterApi
                ->setPostfields($postfields)
                ->buildOauth($url, $requestMethod)
                ->performRequest();

            $this->timer->add_cp('End: Get User Information - ' . $random);


            $users = json_decode($response);

            //Write new data to cache.
            foreach ($users as $user)
            {
                $this->userData->writeUserToCache($user);
            }
            $this->userData->flushUserListCache();
        }

        return $users;
    }

    /**
     * Once buildFriendIds and buildFollowerIds have been called, call this function
     * to filter the results. FTF_Driver->filteredUsers will hold the results.
     */
    public function buildFilteredFollowers()
    {
        $this->removeUsersAlreadyFollowed();

        $this->twitterApi = new TwitterAPIExchange($this->apiKeys);

        $this->filteredUsers = array();

        $offset = 0;
        $limit = 100;


        while ((($offset + $limit) <= $this->webRequest->followerLimit || $offset == 0) && ($offset) <= count($this->potentialFriendIds))
        {
            //Get subset of user ids to fetch full profiles for, twitter limits this call to 100 per call.
            $ids = array_slice($this->potentialFriendIds, $offset, $limit);

            //Get a list of fresh and cached ids, so we don't fetch profiles for people we already have
            $idObject = $this->breakIdsByCached($ids);

            //Get new and cached user data, and merge it so we can filter.
            $newUsers = $this->fetchUserDataFromTwitter($this->twitterApi, $idObject->freshUserIds);
            $cachedFollowers = $this->userData->fetchCachedUsers($idObject->cachedUserIds);
            $unfilteredUsers = array_merge($newUsers, $cachedFollowers);

            $usersToAdd = $this->filterUsers($unfilteredUsers);
            $this->filteredUsers = array_merge($this->filteredUsers, $usersToAdd);

            //Update offset and possibly limit for next go around in loop
            $offset = $offset + $limit;
            if (($offset + $limit) > $this->webRequest->followerLimit)
            {
                $limit = max($this->webRequest->followerLimit - $offset, 1);
            }

        }


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
        $message .= $this->generateTimerStats();
        return $message;
    }

    /**
     * Generates and returns the HTML for the filtered user results.
     * @return string HTML.
     */
    public function generateHtml()
    {
        $html = '';
        $counter = 1;
        foreach ($this->filteredUsers as $user)
        {
            $html = $html . '<table width="500" class="user-table">
                <tr>
                <td valign="top" class="number-td">
                    ' . $counter . '.
                </td>
                <td valign="middle" class="picture-td">
                    <img src="' . $user->profile_image_url . '" />
                </td>
                <td valign="middle">
                    <a href="http://www.twitter.com/' . $user->screen_name . '" target="_blank">' . $user->name . ' (@' . $user->screen_name . ')</a><br />
                    <p>' . $user->description . '</p>
                    Friends/Following : <strong>' . $user->friends_count . '</strong> &nbsp;&nbsp;&nbsp;&nbsp; Followers: <strong>' . $user->followers_count . '</strong>
                </td>
                </tr>
                </table>';
            $counter++;
        }

        if (count($this->filteredUsers) < 1)
        {
            $html = 'No Results Found!';
        }
        return $html;
    }

    /**
     * Given a list of users, filter based on the constraints set forth in the request.
     * @param $users Array of users.
     * @return array Filtered users that made the cut.
     */
    private function filterUsers($users)
    {
        $keywordArray = explode(',', $this->webRequest->keywords);
        if ($keywordArray && count($keywordArray) == 1 && empty($keywordArray[0]))
        {
            $keywordArray = null;
        }
        $filteredUsers = array();

        foreach ($users as $user)
        {
            //$filteredUsers[] = $user;
            $addUser = true;

            if (!empty($this->webRequest->minimumFriends) && $user->friends_count < $this->webRequest->minimumFriends)
            {
                $addUser = false;
            }
            if ($addUser && !empty($this->webRequest->maximumFriends) && $user->friends_count > $this->webRequest->maximumFriends)
            {
                $addUser = false;
            }
            if (!empty($this->webRequest->minimumFollowers) && $user->followers_count < $this->webRequest->minimumFollowers)
            {
                $addUser = false;
            }
            if ($addUser && !empty($this->webRequest->maximumFollowers) && $user->followers_count > $this->webRequest->maximumFollowers)
            {
                $addUser = false;
            }
            if ($addUser && $this->webRequest->friendToFollowerRatio == FTF_WebRequest::FOLLOWERS_GREATER_THAN_FRIENDS && $user->followers_count < $user->friends_count)
            {
                $addUser = false;
            }
            if ($addUser && $this->webRequest->friendToFollowerRatio == FTF_WebRequest::FRIENDS_GREATER_THAN_FOLLOWERS && $user->followers_count > $user->friends_count)
            {
                $addUser = false;
            }

            if (isset($keywordArray) && count($keywordArray) > 0)
            {
                $keywordMatch = false;

                foreach ($keywordArray as $keyword)
                {
                    $keyword = trim($keyword);
                    $position = stripos($user->description, $keyword);
                    if ($position !== false)
                    {
                        $user->description = str_ireplace($keyword, '<strong>' . $keyword . '</strong>', $user->description);
                        $keywordMatch = true;
                    }
                }

                if (!$keywordMatch)
                {
                    $addUser = false;
                }
            }

            if ($addUser)
            {
                $filteredUsers[] = $user;
            }

        }

        return $filteredUsers;
    }

    /**
     * Generates a string of timer details.
     */
    public function generateTimerStats()
    {
        $this->timer->add_cp('Complete');

        ob_start();
        $this->timer->showme();
        $string = ob_get_contents();
        ob_end_clean();

        return $string;

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

?>