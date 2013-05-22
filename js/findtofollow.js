var FindToFollow = new function()
{
    /**
     * Minimize the blog container so that only "Show Log" link is visible.
     */
    this.minimizeLog = function()
    {
        $("#logVisibilityLink").text("Show Log");
        $("#logText").hide();
    };
    /**
     * Maximize the log window to see all the log details.
     */
    this.maximizeLog = function()
    {
        $("#logVisibilityLink").text("Hide Log");
        $("#logText").show();
    };
    /**
     * If minimized will maximize the log container and visa versa.
     */
    this.toggleLogVisibility = function()
    {
        if ($("#logText").is(":visible"))
        {
            this.minimizeLog();
        }
        else
        {
            this.maximizeLog();
        }
    };
    /**
     * Obscure the results, just a nice UI effect for when we are refreshing the results.
     */
    this.obscureResults = function()
    {
        $("#results").fadeTo(500, 0.2);
        $("#logContainer").fadeTo(500, 0.2);
    };
    /**
     * Fades back to full opacity once results have been refreshed.
     */
    this.unObscureResults = function()
    {
        $("#results").fadeTo(250, 1);
        $("#logContainer").fadeTo(250, 1);
    };
    /**
     * Send a request to backend to find us some followers. This is called by teh Filter Followers button
     * on the UI.
     */
    this.sendRequest = function()
    {
        $("#results").removeClass("error-message");

        var requestObject = this.buildRequestObject();
        requestObject.action = "run";

        $("#sendRequestBtn").attr("disabled", "disabled");

        if (this.validateRequest(requestObject))
        {
            $("#loadingImage").show();
            this.obscureResults();

            var _this = this;

            $.post("FindToFollow.php", requestObject)
                .done(function(response)
                {
                    response = JSON.parse(response);

                    //Log container is hidden before first request is sent.
                    $("#logContainer").show();
                    //Set log text.
                    $("#logText").html(response.log);
                    //Default to minimized
                    _this.minimizeLog();

                    if (response.hasError)
                    {
                        $("#results").addClass("error-message");
                        $("#results").html("Error occurred: " + response.errorMessage);
                        _this.maximizeLog();
                    }
                    else
                    {
                        $("#results").html(response.html);
                    }
                })
                .fail(function()
                {
                    $("#results").addClass("error-message");
                    $("#results").html("An unexpected error occurred during the request.");
                })
                .always(function()
                {
                    $("#sendRequestBtn").removeAttr("disabled");
                    $("#loadingImage").hide();
                    _this.unObscureResults();
                });
        }
        else
        {
            $("#sendRequestBtn").removeAttr("disabled");
        }
    };
    /**
     * Validates our request to make sure we are sending required data. Marks invalid fields on UI as well.
     * @param {FindToFollow.JsonRequest} requestObject The object to send to backend.
     * @returns {boolean} True if valid, false otherwise.
     */
    this.validateRequest = function(requestObject)
    {

        var hasError = false;
        if (!requestObject.twitterUsername)
        {
            hasError = true;
            $("#twitterUsername").addClass("input-error");
        }
        else
        {
            $("#twitterUsername").removeClass("input-error");
        }

        if (!requestObject.sourceUsername)
        {
            hasError = true;
            $("#sourceUsername").addClass("input-error");
        }
        else
        {
            $("#sourceUsername").removeClass("input-error");
        }

        if (!requestObject.followerLimit)
        {
            hasError = true;
            $("#followerLimit").addClass("input-error");
        }
        else
        {
            $("#followerLimit").removeClass("input-error");
        }

        if (hasError)
        {
            $("#sendRequestBtn").addClass("input-error");
        }
        else
        {
            $("#sendRequestBtn").removeClass("input-error");
        }

        return !hasError;
    };
    /**
     * Dynamically builds request object to send to backend. HTML elements must have id's that match each property of FindToFollow.JsonRequest.
     * @returns {FindToFollow.JsonRequest} .
     */
    this.buildRequestObject = function()
    {
        var jsonRequest = new FindToFollow.JsonRequest();

        for (var i in jsonRequest)
        {
            jsonRequest[i] = $("#" + i).val();
        }

        return jsonRequest;

    };
};
/**
 * Structure for requests sent to backend.
 * @constructor
 */
FindToFollow.JsonRequest = function()
{
    this.twitterUsername = "";
    this.sourceUsername = "";
    this.followerLimit = -1;
    this.minimumFollowers = -1;
    this.maximumFollowers = -1;
    this.minimumFriends = -1;
    this.maximumFriends = -1;
    this.friendToFollowerRatio = "";
    this.keywords = "";
};