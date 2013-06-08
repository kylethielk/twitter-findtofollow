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

require_once("Request.php");
/**
 * The object form of the json we will receive from the front-end.
 * Class FTF_WebRequest
 */
class FTF_FilterRequest extends Request
{
    const FRIENDS_GREATER_THAN_FOLLOWERS = 'friendsGreaterThanFollowers';
    const FOLLOWERS_GREATER_THAN_FRIENDS = 'followersGreaterThanFriends';

    /**
     * Username for person running this app.
     * @var String
     */
    public $twitterUsername;
    /**
     * We build our list of potential people to follow based one person's list of followers. This is their username.
     * @var String
     */
    public $sourceUsername;
    /**
     * The maximum number of followers to fetch from sourceUsername.
     * @var Integer
     */
    public $followerLimit;
    /**
     * Minimum number of followers a potential user must have.
     * Optional.
     * @var Integer
     */
    public $minimumFollowers;
    /**
     * Maximum number of followers a potential user must have.
     * Optional.
     * @var Integer
     */
    public $maximumFollowers;
    /**
     * Minimum number of friends a potential user must have.
     * Optional.
     * @var Integer
     */
    public $minimumFriends;
    /**
     * Maximum number of friends a potential user must have.
     * Optional.
     * @var Integer
     */
    public $maximumFriends;
    /**
     * Whether potential user should have more friends than followers, visa versa or don't care.
     * @var
     */
    public $friendToFollowerRatio;
    /**
     * Comma separated list of keywords that must be in description of user. Only one has to match, not all.
     * @var
     */
    public $keywords;

    /**
     * Construct from json object received from front-end.
     * @param $ajaxData Object.
     */
    public function FTF_FilterRequest($ajaxData)
    {
        foreach ($this as $key => $value)
        {
            if (isset($ajaxData[$key]))
            {
                $this->$key = $ajaxData[$key];

            }
        }

    }

    /**
     * Validate all required fields are set.
     * @return Boolean True if valid, false otherwise.
     */
    public function validate()
    {
        //Required fields
        if (!isset($this->twitterUsername) || !isset($this->sourceUsername) || !isset($this->followerLimit) || !is_numeric($this->followerLimit))
        {
            return false;
        }
        return true;

    }
}
?>