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

/**
 * Class with functions to write/read from our cached data.
 * Class FTF_UserData
 */
class FTF_UserData
{
    /**
     * @var String Username of person we are running app for.
     */
    private $twitterUsername;
    /**
     * List of user id's we have in our cache.
     * @var
     */
    public $cachedUserIds;
    /**
     * List of ids for people that we are or have followed in the past. Helps keep us from re-following
     * we have followed in past and then unfollowed.
     * @var
     */
    public $friendIds;


    public function FTF_UserData($twitterUsername)
    {
        $this->twitterUsername = $twitterUsername;
        $this->initializeUserDataDirectory();
        $this->initializeCachedUserIdList();
        $this->initializePrimaryUserData();
    }

    /**
     * Initializes user data i.e ensure directories and necessary files exist, creates
     * them if they don't.
     */
    private function initializePrimaryUserData()
    {
        $primaryFileName = './userdata/' . $this->twitterUsername . '.json';
        if (file_exists($primaryFileName) === false)
        {
            //Write blank file to keep track of user's we are following
            $primaryFilePointer = fopen($primaryFileName, 'w');

            fwrite($primaryFilePointer, '{"friendIds":[]}');
            fclose($primaryFilePointer);

            $this->friendIds = array();
        }
        else
        {
            $primaryFilePointer = fopen($primaryFileName, 'r');
            $read = fread($primaryFilePointer, filesize($primaryFileName));
            fclose($primaryFilePointer);

            $readObject = json_decode($read);
            $this->friendIds = (array)$readObject->friendIds;
        }
    }

    /**
     * Initialze the list of cached users we have.
     */
    private function initializeCachedUserIdList()
    {
        $filename = './userdata/cacheduserlist.json';
        if (file_exists($filename) === false)
        {
            $userListFilePointer = fopen($filename, 'w');
            fwrite($userListFilePointer, '{"userIds":[]}');
            fclose($userListFilePointer);

            $this->cachedUserIds = array();
        }
        else
        {
            $userListFilePointer = fopen($filename, 'r');
            $read = fread($userListFilePointer, filesize($filename));
            fclose($userListFilePointer);

            $readObject = json_decode($read);
            $this->cachedUserIds = $readObject->userIds;

        }
    }

    /**
     * @return Boolean True if already exists, false if had to be created.
     */
    private function initializeUserDataDirectory()
    {
        if (is_dir('./userdata') === false)
        {
            mkdir('./userdata');
            return false;
        }
        else
        {
            return true;
        }

    }

    /**
     * Merge in friends to our existing list of friends. Must call flushPrimaryUserData to
     * write to disk.
     * @param $friendIds Array List of friend ids to merge in.
     */
    public function mergeInFriendIds($friendIds)
    {
        $this->friendIds = array_values($this->friendIds);
        $friendIds = array_values($friendIds);

        $previousCount = count($this->friendIds);

        $diff = array_diff($friendIds, $this->friendIds);
        $this->friendIds = array_merge($this->friendIds, array_unique($diff));

        $currentCount = count($this->friendIds);

        if ($previousCount != $currentCount)
        {
            FTF_Web::$currentDriver->addLogMessage('Cached friend count: ' . $previousCount . '. New friend count: ' . $currentCount);
        }
    }

    /**
     * Write primary user file i.e twiterUsername.json to filesystem.
     */
    public function flushPrimaryUserData()
    {
        $primaryFileName = './userdata/' . $this->twitterUsername . '.json';

        if (!isset($this->friendIds))
        {
            $this->friendIds = array();
            FTF_Web::$currentDriver->addLogMessage("Cannot flush primary data to filesystem. Error occurred.");
            return;
        }

        $toWrite = (Object)array();
        $toWrite->friendIds = $this->friendIds;

        $primaryFilePointer = fopen($primaryFileName, 'w');
        fwrite($primaryFilePointer, json_encode($toWrite));
        fclose($primaryFilePointer);
    }

    /**
     * Writer user to our cache. Don't forget to call flushUserListCache.
     * @param $user Object The JSON User object received from twitter to write.
     */
    public function writeUserToCache($user)
    {
        $userFilePointer = fopen('./userdata/' . $user->id . '.json', 'w');
        $toWrite = (Object)Array();
        $toWrite->downloadDate = time();
        $toWrite->user = $user;
        fwrite($userFilePointer, json_encode($toWrite));
        fclose($userFilePointer);

        $this->cachedUserIds[] = $user->id;
    }

    /**
     * Writes to the filesystem our list of cached userids. Should be called anytime writeFollowerToCache is called, however
     * if you expect to call it many times, best to call persistUserListCache once at the end.
     */
    public function flushUserListCache()
    {
        $filename = './userdata/cacheduserlist.json';
        $filePointer = fopen($filename, 'w');

        if (!isset($this->cachedUserIds))
        {
            $this->cachedUserIds = array();
            FTF_Web::$currentDriver->addLogMessage("Cannot flush primary data to filesystem. Error occurred.");
            return;
        }

        $toWrite = (Object)array();
        $toWrite->userIds = $this->cachedUserIds;


        fwrite($filePointer, json_encode($toWrite));
        fclose($filePointer);
    }

    /**
     * Fetch user data for list of user ids.
     * @param $userIds array List of ids to fetch users for.
     * @return array Return array of users.
     */
    public function fetchCachedUsers($userIds)
    {
        $users = array();
        foreach ($userIds as $userId)
        {
            $filename = './userdata/' . $userId . '.json';
            $filePointer = fopen($filename, 'r');
            $data = fread($filePointer, filesize($filename));
            $dataObject = json_decode($data);

            $users[] = $dataObject->user;
        }
        return $users;
    }
}

?>