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

require_once(dirname(__FILE__) . '/twitteroauth/twitteroauth.php');
require_once(dirname(__FILE__) . '/UserData.php');
require_once(dirname(__FILE__) . '/Config.php');
require_once(dirname(__FILE__) . '/Web.php');

session_name('ftf');
session_start();

class FTF_TwitterAuthenticate
{
    const FINALIZE_OAUTH = "Twitter_FinalizeOAuth";

    static public $skipRedirect = false;

    /**
     * Process any request do's.
     */
    static function processRequest()
    {
        $do = isset($_REQUEST['do']) ? $_REQUEST['do'] : "";

        if ($do == FTF_TwitterAuthenticate::FINALIZE_OAUTH)
        {
            FTF_TwitterAuthenticate::finalizeOAuth();
            FTF_TwitterAuthenticate::$skipRedirect = true;
        }

    }

    /**
     * Returns the currently logged in user or null if none.
     * @return FTF_TwitterUser
     */
    static function loggedInUser()
    {
        return FTF_UserData::getUserData()->currentUser();
    }

    /**
     * Redirect to Twitter Login page.
     */
    static function redirectToLogin()
    {
        $twitterOAuth = new TwitterOAuth(FTF_Config::$apiKeys['consumer_key'], FTF_Config::$apiKeys['consumer_secret']);

        $redirectUrl = FTF_TwitterAuthenticate::buildOAuthRedirectUrl();

        // Requesting authentication tokens, the parameter is the URL we will be redirected to
        $request_token = $twitterOAuth->getRequestToken($redirectUrl);

        // Saving them into the session
        $_SESSION['ftf_oauth_token'] = $request_token['oauth_token'];
        $_SESSION['ftf_oauth_token_secret'] = $request_token['oauth_token_secret'];


        // If everything goes well..
        if ($twitterOAuth->http_code == 200)
        {
            // Let's generate the URL and redirect
            $url = $twitterOAuth->getAuthorizeURL($request_token['oauth_token']);
            header("Location: " . $url);
        }
        else
        {
            die("There was an error authorizing you via Twitter, please try again.");
        }
    }

    /**
     * We have to add on a parameter to current URL so we can process Twitter OAUTH.
     */
    static function buildOAuthRedirectUrl()
    {

        return FTF_TwitterAuthenticate::buildFullUrl() . '?do=' . FTF_TwitterAuthenticate::FINALIZE_OAUTH;
    }

    static function buildFullUrl()
    {
        $s = empty($_SERVER['HTTPS']) ? '' : ($_SERVER['HTTPS'] == 'on') ? 's' : '';
        $protocol = substr(strtolower($_SERVER['SERVER_PROTOCOL']), 0, strpos(strtolower($_SERVER['SERVER_PROTOCOL']), '/')) . $s;
        $port = ($_SERVER['SERVER_PORT'] == '80') ? '' : (":" . $_SERVER['SERVER_PORT']);
        return strtok($protocol . "://" . $_SERVER['HTTP_HOST'] . $port . $_SERVER['REQUEST_URI'],'?');
    }

    /**
     * Finalize the last leg of OAuth from Twitter.
     */
    static function finalizeOAuth()
    {

        if (!empty($_GET['oauth_verifier']) && !empty($_SESSION['ftf_oauth_token']) && !empty($_SESSION['ftf_oauth_token_secret']))
        {
            // TwitterOAuth instance, with two new parameters we got in startOAuth
            $twitterOAuth = new TwitterOAuth(FTF_Config::$apiKeys['consumer_key'], FTF_Config::$apiKeys['consumer_secret'], $_SESSION['ftf_oauth_token'], $_SESSION['ftf_oauth_token_secret']);

            // Let's request the access token
            $accessToken = $twitterOAuth->getAccessToken($_GET['oauth_verifier']);

            // Save it in a session var
            $_SESSION['ftf_access_token'] = $accessToken;

            // Let's get the user's info
            $twitterUser = $twitterOAuth->get('account/verify_credentials');

            $errorMessage = FTF_Driver_Twitter::checkForTwitterErrors($twitterUser);

            if ($errorMessage !== false)
            {
                die("Received error from twitter: " . $errorMessage);
                return;
            }
            else
            {
                $user = new FTF_TwitterUser($twitterUser->screen_name,
                    $accessToken['oauth_token'],
                    $accessToken['oauth_token_secret'],
                    $twitterUser->profile_image_url,
                    $twitterUser->description);
                FTF_UserData::getUserData()->setCurrentUser($user);

                header("Location: " . FTF_TwitterAuthenticate::buildFullUrl());

            }
        }
        else
        {
            // Something's missing, go back to square 1
            die("Error from twitter oauth when finalizing authentication.");
        }
    }

}