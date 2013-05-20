<?php
require_once('WebRequest.php');
require_once('WebResponse.php');
require_once('Driver.php');

/**
 * Executes AJAX requests from html file.
 * Class FTF_Web
 */
class FTF_Web
{
    const ACTION_RUN = 'run';

    /**
     * Execute the ajax request.
     * @param $data $_POST data.
     */
    public static function executeRequest($data)
    {
        if (!isset($data) || !isset($data['action']))
        {
            return;
        }
        $action = $data['action'];

        if ($action == FTF_Web::ACTION_RUN)
        {
            FTF_Web::filterFollowers($data);
        }
    }

    /**
     * Start the process and find filtered users to follow.
     * @param $data $_POST data.
     */
    private static function filterFollowers($data)
    {
        global $apiKeys;

        $settings = new FTF_WebRequest($data);

        if (!isset($settings) || !$settings->validate())
        {
            FTF_Web::writeErrorResponse('Settings invalid or not supplied.' . print_r($settings, true));
        }
        else
        {
            $findToFollow = new FTF_Driver($apiKeys, $settings);
            $findToFollow->buildFriendIds();
            $findToFollow->buildFollowerIds();
            $findToFollow->buildFilteredFollowers();
            FTF_Web::writeValidResponse($findToFollow->generateHtml(), $findToFollow->generateLog());
        }
    }

    /**
     * Write response to browser.
     * @param $html String The html to write.
     * @param $log String a string for the log.
     */
    private static function writeValidResponse($html, $log)
    {
        $response = new FTF_WebResponse();
        $response->hasError = false;
        $response->errorMessage = '';
        $response->html = $html;
        $response->log = $log;

        echo json_encode($response);
        exit(3);
    }

    /**
     * Write response to browser and mark it as having an error.
     * @param $errorMessage String The error message to write.
     */
    public static function writeErrorResponse($errorMessage)
    {
        $response = new FTF_WebResponse();
        $response->hasError = true;
        $response->errorMessage = $errorMessage;

        echo json_encode($response);
        exit(3);

    }

}

?>