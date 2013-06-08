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
 * The object form of the json we will receive from the front-end when adding users to the queue.
 * Class FTF_QueueRequest
 */
class FTF_QueueAddRequest extends FTF_QueueRequest
{

    /**
     * Username for person running this app.
     * @var String
     */
    public $twitterUsername;
    /**
     * Array of UserIds to add to queue.
     * @var array user ids.
     */
    public $queuedUserIds;

    /**
     * Construct from json object received from front-end.
     * @param $ajaxData Object.
     */
    public function FTF_QueueAddRequest($ajaxData)
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
        if (!isset($this->twitterUsername) || !isset($this->queuedUserIds) || count($this->queuedUserIds) < 1)
        {
            return false;
        }
        return true;

    }
}

?>