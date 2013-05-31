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
 * Holds information about a twitter user.
 * Class FTF_Friend
 */
class FTF_Friend
{
    /**
     * Date we followed this user. 10 digit timestamp.
     * @var Integer
     */
    public $dateFollowed;
    /**
     * Date we unfollowed this user 10 digit timestamp.
     * @var Integer
     */
    public $dateUnfollowed;

    /**
     * Date we downloaded their information from twitter. 10 digit timestamp.
     * @var Integer
     */
    public $downloadDate;

    /**
     * Entire JSON object received from twitter.
     * @var Object
     */
    public $userData;

    /**
     * Constructor that sets all values to default.
     * @param $twitterUserData The JSON object received from Twitter API.
     */
    public function FTF_Friend($twitterUserData)
    {
        $this->dateUnfollowed = 0;
        $this->dateFollowed = 0;
        $this->downloadDate = 0;
        $this->userData = $twitterUserData;
    }

}

?>