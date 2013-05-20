<?php
/**
 * The object form of the json we will receive from the front-end.
 * Class FTF_WebRequest
 */
class FTF_WebRequest
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
    public function FTF_WebRequest($ajaxData)
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