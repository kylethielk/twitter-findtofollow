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
require_once(dirname(__FILE__) . '/../UserData.php');

/**
 * Parent class for all of our driver classes.
 * Class FTF_Driver_Base
 */
class FTF_Driver_Base
{
    /**
     * @var string
     */
    protected $twitterUsername;

    /**
     * @var FTF_UserData
     */
    protected $userData;
    /**
     * @var array Log Messages.
     */
    private $logArray = array();

    /**
     * Initialize this driver.
     * @param string $twitterUsername The username for who we are running this app for.
     */
    public function __construct($twitterUsername)
    {
        $this->twitterUsername = $twitterUsername;
        $this->userData = new FTF_UserData($twitterUsername);
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
        return $message;
    }

    /**
     * Add a message to the log.
     * @param $message String The message to add to the log.
     */
    public function addLogMessage($message)
    {
        $this->logArray[] = $message . '<br />';
    }

    /**
     * Generates the HTML for the supplied users.
     * @param array $users Twitter user data array.
     * @param string $pageId The id of the page this table will be in.
     * @param boolean $disableSelect Defaults to false, set to true to disable clicking of the row.
     * @return string The HTML.
     */
    public function generateUserTablesHtml($users, $pageId, $disableSelect = false)
    {
        $counter = 1;
        $html = '';
        foreach ($users as $user)
        {
            $onClick = '';
            if (!$disableSelect)
            {
                $onClick = 'onclick="FindToFollow.userTableRowClicked(event);"';
            }

            $followInfo = '';
            if ($pageId == "unFollowPage")
            {
                $dateFollowed = intval($this->userData->fetchUserFTFData($user->id, 'dateFollowed'));
                if ($dateFollowed > 0)
                {
                    $followInfo = '<span class="follow-information">Followed User ' . $this->timeAgo($dateFollowed) . '</span>';
                }
                else
                {
                    $followInfo = '<span class="follow-information">No Follow Information</span>';
                }
            }

            $html = $html . '<table width="500" class="user-table" id="' . $pageId . 'UserRow' . $user->id . '" ' . $onClick . ' data-user-id="' . $user->id . '">
                <tr>
                <td valign="top" class="number-td">
                    ' . $counter . '.
                </td>
                <td valign="middle" class="picture-td">
                    <img src="' . $user->profile_image_url . '" />
                </td>
                <td valign="middle" class="description-td">
                    <a href="http://www.twitter.com/' . $user->screen_name . '" target="_blank">' . $user->name . ' (@<span id="' . $pageId . 'Username' . $user->id . '">' . $user->screen_name . '</span>)</a>
                    ' . $followInfo . '
                    <br />
                    <p>' . $user->description . '</p>
                    Friends/Following : <strong>' . $user->friends_count . '</strong> &nbsp;&nbsp;&nbsp;&nbsp; Followers: <strong>' . $user->followers_count . '</strong>
                </td>';

            if (!$disableSelect)
            {
                $html .= '<td class="checkbox-td">
                    <input type="checkbox" name="checked" id="checked" value="' . $user->id . '" class="row-checkbox" />
                </td>';
            }


            $html .= '</tr >
                </table > ';
            $counter++;
        }

        if (count($users) < 1)
        {
            $html = 'No Results Found!';
        }
        return $html;
    }

    /**
     * Returns in words how long ago time occurred.
     * @param $time
     * @return string
     */
    private function timeAgo($time)
    {
        $periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
        $lengths = array("60", "60", "24", "7", "4.35", "12", "10");

        $now = time();

        $difference = $now - intval($time);
        $tense = "ago";

        for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths) - 1; $j++)
        {
            $difference /= $lengths[$j];
        }

        $difference = round($difference);

        if ($difference != 1)
        {
            $periods[$j] .= "s";
        }

        return "$difference $periods[$j] ago ";
    }
}