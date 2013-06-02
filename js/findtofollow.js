var FindToFollow = new function()
{
    this.followIntervalTime = 0;
    this.interval = null;
    this.idsToFollow = [];
    this.ticks = 0;
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
    this.sendFilterRequest = function()
    {
        $("#results").removeClass("error-message");

        var requestObject = this.buildFilterRequestObject();
        requestObject.action = "run";

        $("#sendRequestBtn").attr("disabled", "disabled");

        if (this.validateFilterRequest(requestObject))
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
     * @param {FindToFollow.FilterJsonRequest} requestObject The object to send to backend.
     * @returns {boolean} True if valid, false otherwise.
     */
    this.validateFilterRequest = function(requestObject)
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
     * Dynamically builds request object to send to backend. HTML elements must have id's that match each property of FindToFollow.FilterJsonRequest.
     * @returns {FindToFollow.FilterJsonRequest} .
     */
    this.buildFilterRequestObject = function()
    {
        var jsonRequest = new FindToFollow.FilterJsonRequest();

        for (var i in jsonRequest)
        {
            jsonRequest[i] = $("#" + i).val();
        }

        return jsonRequest;

    };
    /**
     * Select or deselect all rows.
     * @param {MouseEvent} event Triggered by the click.
     */
    this.checkAllClicked = function(event)
    {
        var checkBox = $(event.currentTarget);

        var checkedValue = true;
        if (!checkBox.is(':checked'))
        {
            checkedValue = false;
        }

        var _this = this;
        $(".row-checkbox").each(function()
        {
            _this.setRowSelectedState($(this).val(), $(this), checkedValue);
        });
    };
    /**
     * Handles a user table row being clicked.
     * @param {MouseEvent} event Triggered by the click.
     */
    this.userTableRowClicked = function(event)
    {
        var table = $(event.currentTarget);
        var srcElement = $(event.srcElement);
        var checkBox = table.find("#checked");

        var currentlyChecked = srcElement.is(":checkbox") ? !checkBox.is(":checked") : checkBox.is(":checked");
        var id = checkBox.val();

        this.setRowSelectedState(id, checkBox, !currentlyChecked);
    };
    /**
     *
     * @param {number} id Row id.
     * @param {jQuery} checkBox jQuery object for the row's checkbox.
     * @param {boolean} selected True if row should be selected.
     */
    this.setRowSelectedState = function(id, checkBox, selected)
    {
        if (!selected)
        {
            $("#userRow" + id).removeClass("user-table-selected");
            checkBox.prop("checked", false);
        } else
        {
            $("#userRow" + id).addClass("user-table-selected");
            checkBox.prop("checked", true);
        }
    };
    this.launchFollowSettings = function()
    {
        $("#dialogBackground").show();
    };
    this.startFollowing = function()
    {
        var checkedIds = $('input.row-checkbox:checked').map(function()
        {
            return this.value
        }).get();

        var followIntervalTime = $("#followIntervalTime").val();
        if (!$.isNumeric(followIntervalTime))
        {
            $("#followIntervalTime").addClass("input-error");
            return;
        }
        else
        {
            $("#followIntervalTime").removeClass("input-error");
        }

        //Convert seconds to ms.
        this.followIntervalTime = followIntervalTime * 1000;
        this.idsToFollow = checkedIds;

        $("#startFollowingBtn").attr('disabled', 'disabled');
        $("#totalFollowingNumber").html(checkedIds.length);

        $("#followStatus").show();
        $("#loadingImageForFollowing").show();

        this.followNextUser();

    };
    this.startInterval = function()
    {
        if (this.followIntervalTime < 1000)
        {
            return;
        }

        this.ticks = 0;

        var _this = this;
        this.interval = setInterval(function()
        {
            _this.ticks++;
            $("#nextFollowTime").html((_this.followIntervalTime - (_this.ticks * 1000)) / 1000);
            if ((_this.ticks * 1000) >= _this.followIntervalTime)
            {
                //Run follower
                clearInterval(_this.interval);
                _this.followNextUser();
            }
        }, 1000);
    };
    this.followNextUser = function()
    {
        var nextId = this.idsToFollow.shift();
        var username = $("#username" + nextId).html();

        $("#currentFollowStatus").html("Attempting to follow " + username + ".");
        $("#nextFollowTime").html(this.followIntervalTime / 1000);

        var _this = this;

        var requestObject = new FindToFollow.FollowJsonRequest();
        requestObject.twitterUserId = nextId;
        requestObject.action = "follow";

        $.post("FindToFollow.php", requestObject)
            .done(function(response)
            {
                response = JSON.parse(response);

                if (response.hasError)
                {
                    $("#currentFollowStatus").html("Error from twitter while trying to follow " + username + ". Stopping all follows.");
                }
                else
                {
                    $("#currentFollowingNumber").html(parseInt($("#currentFollowingNumber").html()) + 1);
                    if (_this.idsToFollow.length > 0)
                    {
                        var nextId = _this.idsToFollow[0];
                        username = $("#username" + nextId).html();

                        $("#currentFollowStatus").html("Next user to follow is " + username + ".");
                        _this.startInterval();
                    }
                    else
                    {
                        $("#currentFollowStatus").html("Done following all users.");
                        $("#loadingImageForFollowing").hide();
                    }
                }
            })
            .fail(function()
            {
                $("#results").addClass("error-message");
                $("#results").html("An unexpected error occurred during the request.");
            })

    };
};
/**
 * Structure for requests sent to backend.
 * @constructor
 */
FindToFollow.FilterJsonRequest = function()
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
FindToFollow.FollowJsonRequest = function()
{
    this.twitterUserId = "";
};