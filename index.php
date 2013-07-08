<?php
require_once('TwitterAuthenticate.php');

if (!FTF_Web::validateConfig())
{
    die("Config.php has not been setup or configured properly.");
}
else
{

    FTF_TwitterAuthenticate::processRequest();

    $user = FTF_TwitterAuthenticate::loggedInUser();

    if (!isset($user))
    {
        FTF_TwitterAuthenticate::redirectToLogin();
    }
}
?>


<!DOCTYPE html>
<head>
    <title>Twitter - FindToFollow</title>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <script src="./js/findtofollow.js"></script>
    <link rel="stylesheet" href="css/findtofollow.css" media="all"/>
</head>
<body class="elements">

<div id="errorBar" style="display: none">

</div>
<div id="wrapper">
<div class="user-profile">
    <img src="<?php echo $user->profileImageUrl; ?>"/>
    <span id="twitterUsername"><a href="https://www.twitter.com/<?php echo $user->twitterUsername; ?>"
                                  target="_blank"><?php echo $user->twitterUsername; ?></a></span>
    <span id="twitterSwitchUser"><a href="javascript:void(0);">(Switch User)</a></span>
</div>
<ul class="tab-wrapper">
    <li id="helpPageTab" onclick="FindToFollow.changePage('helpPage');">Help</li>
    <li id="unFollowPageTab" onclick="FindToFollow.changePage('unFollowPage');">UnFollow</li>
    <li id="followPageTab" onclick="FindToFollow.changePage('followPage');">Follow</li>
    <li id="filterPageTab" onclick="FindToFollow.changePage('filterPage');" class="tab-selected">Filter</li>
</ul>
<div id="unFollowPage" class="page" style="display:none">
    <div class="leftColumn">
        <div class="content-block">
            <div id="unFollowUsersBtn"
                 onclick="FindToFollow.UnFollow.startAutomaticUnFollowing();return false;"
                 class="blue-button">Start UnFollowing Users (<span id="unFollowPageSelectedCount">0</span>)
            </div>

            <h2>Required Fields</h2>
            <label for="unFollowIntervalTimeMinimum">Min Time (s) For UnFollow:</label>
            <input type="text" id="unFollowIntervalTimeMinimum" name="unFollowIntervalTimeMinimum"
                   title="The amount of time between each unfollow."
                   value="60"/>
            <label for="unFollowIntervalTimeMaximum">Max Time (s) For UnFollow:</label>
            <input type="text" id="unFollowIntervalTimeMaximum" name="unFollowIntervalTimeMaximum"
                   title="The amount of time between each unfollow."
                   value="180"/>

        </div>
    </div>
    <div class="rightColumn">
        <div class="content-block" style="min-height: 655px">
            <input type="button" value="Load Users" id="refreshUnFollowBtn"
                   onclick="FindToFollow.UnFollow.refreshUsers();return false;"
                   class="blue-button"/>
            <br/>

            <div id="unFollowResults">

            </div>
        </div>


    </div>
</div>

<div id="followPage" class="page" style="display:none">
    <div class="leftColumn">
        <div class="content-block">
            <input type="button" value="Start Following Users" id="followUsersBtn"
                   onclick="FindToFollow.Follow.startAutomaticFollowing();return false;"
                   class="blue-button"/>

            <h2>Required Fields</h2>
            <label for="followIntervalTimeMinimum">Min Time (s) For Follow:</label>
            <input type="text" id="followIntervalTimeMinimum" name="followIntervalTimeMinimum"
                   title="The amount of time between each follow."
                   value="60"/>
            <label for="followIntervalTimeMaximum">Max Time (s) For Follow:</label>
            <input type="text" id="followIntervalTimeMaximum" name="followIntervalTimeMaximum"
                   title="The amount of time between each follow."
                   value="180"/>
            <label for="followMaximum">Max People to Follow:</label>
            <input type="text" id="followMaximum" name="followMaximum"
                   title="Amount of people to process this round."
                   value="25"/>

        </div>
    </div>
    <div class="rightColumn">
        <div class="content-block" style="min-height: 655px">
            <input type="button" value="Refresh Queue" id="refreshQueueBtn"
                   onclick="FindToFollow.Follow.refreshQueue();return false;"
                   class="blue-button"/>
            <br/>

            <div id="followResults">

            </div>
        </div>


    </div>
</div>
<div id="filterPage" class="page">
    <div class="leftColumn">
        <div class="content-block">
            <input type="button" value="Filter Followers" id="sendRequestBtn"
                   onclick="FindToFollow.Filter.sendFilterRequest();return false;"
                   class="blue-button"/>

            <h2>Required Fields</h2>
            <label for="sourceUsername">Follower Source Username:</label>
            <input type="text" id="sourceUsername" placeholder="patio11"
                   title="Username of person whose follower list we will pull users from." class="medium-input"
                   value=""/>
            <label for="followerLimit">Max Followers to Pull:</label>
            <input type="text" id="followerLimit" title="Maximum number of followers to pull."
                   class="medium-input" placeholder="200" value="250"/>

            <h2>Filter Followers</h2>

            <p class="sub-text">All fields optional.</p>
            <label for="minimumFollowers">Minimum Followers:</label>
            <input type="text" id="minimumFollowers" title="Minimum number of followers person must have." value=""
                   class="medium-input"/>
            <label for="maximumFollowers">Maximum Followers:</label>
            <input type="text" id="maximumFollowers" title="Maximum number of followers person must have." value=""
                   class="medium-input"/>
            <label for="minimumFriends">Minimum Friends:</label>
            <input type="text" id="minimumFriends" title="Minimum number of friends person must have." value=""
                   class="medium-input"/>
            <label for="maximumFriends">Maximum Friends:</label>
            <input type="text" id="maximumFriends" title="Maximum number of friends person must have." value=""
                   class="medium-input"/>
            <label for="friendToFollowerRatio" style="display:block">Follower/Friend Ratio:</label>
            <select id="friendToFollowerRatio">
                <option value="doesNotMatter">Does Not Matter</option>
                <option value="friendsGreaterThanFollowers">Friends Greater Than Followers</option>
                <option value="followersGreaterThanFriends">Followers Greater Than Friends</option>
            </select>
            <label for="keywords" style="display:block">Comma Separated Keywords:</label>
            <textarea id="keywords" title="Used to search user's bio."></textarea>
        </div>
    </div>
    <div class="rightColumn">
        <div class="content-block" style="min-height: 655px">

            <br/>
            <img id="filterLoadingImage" src="./images/ajax-loader.gif" style="display: none"/>

            <div id="logContainer" style="display: none">
                <a href="javascript:void(0);" id="logVisibilityLink"
                   onclick="FindToFollow.Filter.toggleLogVisibility()">Show
                    Log</a>

                <div id="logText"></div>
            </div>


            <div id="filterResults">

            </div>
        </div>


    </div>
</div>
<div id="helpPage" class="page full-column" style="display:none">
    <h1> Twitter FindToFollow</h1>

    <p>
        This application is simple, it helps filter people that you could potentially want to filter based
        on
        keywords
        in
        their bio and their follower/friend counts. Currently the source of people to follow is the follower
        list of
        one
        user. I recommend
        finding a person who has a lot of followers and who has interests similar to the people you are
        trying
        to
        find.
    </p>

    <h2>To Find Potential Followers</h2>

    <p>At a minimum enter your twitter username (must be the twitter username that is linked to your API Keys), the
        source username (i.e the person whose follower list we are scraping) and the maximum # of followers to pull
        (currently limited to 2000). Then simply click "Filter Followers" button. </p>

    <h2>Follow People</h2>

    <p>Once you have some filtered results, you can add them to your queue of people to follow. Select people by
        clicking the row or the checkbox (selected row will be
        marked as green). Then click "Add To Queue" button. </p>

    <p>Flick over to the Follow tab and you should see these people listed. You can build up any number of users in
        your queue. To start processing your queue
        and follow some users fill in the interval minimum and maximum in seconds. We will randomly generate a value
        between these two numbers for each user and wait that time before following them. The let us know how many
        users
        to process, click "Start Following Users" and watch the magic happen.</p>

    <h2>UnFollow People</h2>

    <p>You can unfollow people by flicking over to the UnFollow tab. We do not automatically load the list of users
        that do not
        follow you back to save Twitter API calls. To load the list click the button labelled "Load Users". You can
        then select users
        that you wish to unfollow, specify an interval (similar to how we do with following), click "Start
        Unfollowing Users" button and let the work be done for you.</p>

    <p>
        For more information visit this project on github: <a
            href="https://github.com/kylethielk/twitter-findtofollow/" target="_blank">Twitter FindToFollow</a>.
    </p>

    <p>
        Kyle Thielk
        can be contacted at his website: <a href="http://www.kylethielk.com" target="_blank">kylethielk.com</a>.
    </p>
</div>
<div id="statsPage" class="page full-column" style="display:none">
    Coming soon.
</div>
</div>


</body>
</html>