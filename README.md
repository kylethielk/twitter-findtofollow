twitter-findtofollow
====================

A PHP tool to find and filter potential users to follow.

Written to automate the task of finding interesting and similar users that are likely to follow you back. The potential users are drawn from the followers of one user you specify. A list of criteria is then applied to each user in order to filter out those that are of little interest.

The application caches user profile information to cut down on Twitter API calls and network traffic. All caching is done on the local file system as I explicity wanted to avoid any reliance on a DB.

The application also keeps track of users that you are following and those that you have followed in the past (but are no longer following) to avoid re-following. Unfortunately Twitter does not expose a service to get a list of all people you have followed in the past, so this feature only works on users you have followed and then unfollowed while using this application.

![twitter-findtofollow Screenshot](https://github.com/kylethielk/twitter-findtofollow/blob/master/images/screenshot.png?raw=true)

![twitter-findtofollow Automatic Following Screenshot](https://github.com/kylethielk/twitter-findtofollow/blob/master/images/screenshot-2.png?raw=true)

Instructions
------------

Assuming you have a server capable of running php, the only non-standard requirement is that cURL is installed and activated. Personally I've been running [WAMP Server](http://www.wampserver.com/).

Rename **config.php.txt** to **config.php**. Then add your Twitter API keys into config.php. Note your api keys must have read/write access.

Then simply load index.html in your browser and let the application do the rest.

Updates
-------

**June 3, 2013** - You can now follow other users automatically from within the application. No more opening 25 tabs at once to follow users. The following is automatic and done at random intervals based on a time range you specifiy.

**May 20, 2013**- Initial release of Twitter-FindToFollow. Filtering fully functional but we still need to be able to follow from within the application.

Limitations
-----------

This first pass is fully functional but is void of error checking. So far it has only been used for personal use on a local webserver so is lacking in basic security checks, input validation  etc..

Credit
------

Extensive use of TwitterApiExchange library from James Mallison. Code found [here](http://github.com/j7mbo/twitter-api-php).

For tracking performance of API calls the timer library from David Zoli is used. Code [here](https://github.com/davidzoli/php-timer-class).

License (MIT)
-------------

Copyright (c) 2013 Kyle Thielk

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

