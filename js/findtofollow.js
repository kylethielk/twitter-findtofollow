/**
 * The main class that all of our javascript functionality sits under.
 */
var FindToFollow = new function()
{
    this.Filter = new function()
    {
        /**
         * Minimize the log container so that only "Show Log" link is visible.
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
            $("#filterResults").fadeTo(500, 0.2);
            $("#logContainer").fadeTo(500, 0.2);
        };
        /**
         * Fades back to full opacity once results have been refreshed.
         */
        this.unObscureResults = function()
        {
            $("#filterResults").fadeTo(250, 1);
            $("#logContainer").fadeTo(250, 1);
        };
        /**
         * Send a request to backend to find us some followers. This is called by the Filter Followers button
         * on the UI.
         */
        this.sendFilterRequest = function()
        {
            $("#filterResults").removeClass("error-message");

            var requestObject = this.buildFilterRequestObject();

            $("#sendRequestBtn").attr("disabled", "disabled");

            if (this.validateFilterRequest(requestObject))
            {
                $("#filterLoadingImage").show();
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
                            $("#filterResults").addClass("error-message");
                            $("#filterResults").html("Error occurred: " + response.errorMessage);
                            _this.maximizeLog();
                        }
                        else
                        {
                            $("#filterResults").html(response.html);
                        }
                    })
                    .fail(function()
                    {
                        $("#filterResults").addClass("error-message");
                        $("#filterResults").html("An unexpected error occurred during the request.");
                    })
                    .always(function()
                    {
                        $("#sendRequestBtn").removeAttr("disabled");
                        $("#filterLoadingImage").hide();
                        _this.unObscureResults();
                    });
            }
            else
            {
                $("#sendRequestBtn").removeAttr("disabled");
            }
        };
        this.addSelectedUsersToQueue = function()
        {
            var idsToQueue = $("#filterPage input.row-checkbox:checked").map(function()
            {
                return this.value;
            }).get();

            //Make sure we have atleast one item selected
            if (idsToQueue.length < 1)
            {
                alert("You must select at least one person to add to queue.");
                return;
            }

            var requestObject = new FindToFollow.AddQueueJsonRequest();
            requestObject.queuedUserIds = idsToQueue;
            requestObject.twitterUsername = $("#twitterUsername").val();

            var _this = this;
            $.post("FindToFollow.php", requestObject)
                .done(function(response)
                {
                    response = JSON.parse(response);

                    if (response.hasError)
                    {
                        _this.showErrorMessage("Error: " + response.errorMessage);
                    }
                    else
                    {
                        for (var index in idsToQueue)
                        {
                            var id = idsToQueue[index];
                            //Remove rows
                            $("#filterPageUserRow" + id).fadeOut(400, function()
                            {
                                $(this).remove();
                                _this.updateSelectedCount();
                            });
                        }
                    }
                })
                .fail(function()
                {
                    _this.showErrorMessage("Unexpected error when adding users to queue.");
                });


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
            jsonRequest.action = "run";

            return jsonRequest;

        };
        /**
         * Select or deselect all rows. Called by All checkbox on Filter UI.
         * @param {MouseEvent} event Triggered by the click.
         */
        this.checkAllClicked = function(event)
        {
            var checkBox = $(event.currentTarget);

            var checkedValue = true;
            if (!checkBox.is(":checked"))
            {
                checkedValue = false;
            }

            $(".row-checkbox").each(function()
            {
                FindToFollow.setRowSelectedState($(this).val(), $(this), checkedValue);
            });

            this.updateSelectedCount();

        };

        return this;
    };
    /**
     * The minimum time between follows.
     * @type {number}
     */
    this.followIntervalTimeMinimum = -1;
    /**
     * The maximum time between follows.
     * @type {number}
     */
    this.followIntervalTimeMaximum = -1;
    /**
     * The amount of time in ms between each follow.
     * @type {number}
     */
    this.followIntervalTime = 0;
    /**
     * The interval # returned from setInterval.
     * @type {null}
     */
    this.followInterval = null;
    /**
     * Array of ids to start following.
     * @type {Array}
     */
    this.idsToFollow = [];
    /**
     * Keeps track of how many ticks of the interval we have had. This allows us to have a countdown timer.
     * @type {number}
     */
    this.followTicks = 0;
    this.currentPageId = "filterPage";

    /**
     * Show global error message.
     * @param {String} message The error message to display.
     * @param {number=} timeout Defaults to 15000, time in MS to show the message.
     */
    this.showErrorMessage = function(message, timeout)
    {
        timeout = timeout || 15000;

        $("#errorBar").show();

        var messageId = Math.random();

        $("#errorBar").append('<p id="' + messageId + '">' + message + '</p>');
        setTimeout(function()
        {
            $("#" + messageId).remove();
            if ($("#errorBar").children("*").length < 1)
            {
                $("#errorBar").hide();
            }
        })
    };
    /**
     * Called each time a tab is clicked.
     * @param page
     */
    this.changePage = function(page)
    {
        $(".page").each(function()
        {
            if ($(this).attr('id') == page)
            {
                $("#" + page + "Tab").addClass("tab-selected");
                $(this).show();
            }
            else
            {
                $("#" + $(this).attr('id') + "Tab").removeClass("tab-selected");
                $(this).hide();
            }
        })
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
        this.updateSelectedCount()
    };

    /**
     * Sets the visible state of a row.
     * @param {number} id Row id.
     * @param {jQuery} checkBox jQuery object for the row's checkbox.
     * @param {boolean} selected True if row should be selected.
     */
    this.setRowSelectedState = function(id, checkBox, selected)
    {
        var rowId = "#" + this.currentPageId + "userRow" + id;
        if (!selected)
        {
            $("#" + this.currentPageId + "UserRow" + id).removeClass("user-table-selected");
            checkBox.prop("checked", false);
        } else
        {
            $("#" + this.currentPageId + "UserRow" + id).addClass("user-table-selected");
            checkBox.prop("checked", true);
        }
    };
    /**
     * Counts the number of selected rows, and updates the Follow Users button with result.
     */
    this.updateSelectedCount = function()
    {
        var checkedCount = $("#" + this.currentPageId + " input.row-checkbox:checked").map(function()
        {
            return this.value
        }).get().length;
        $("#" + this.currentPageId + "SelectedCount").html(checkedCount);
    };

    /**
     * Called by Follow Users button. Opens the popup that allows us to start automatic following.
     */
    this.openFollowPopup = function()
    {
        this.idsToFollow = $("input.row-checkbox:checked").map(function()
        {
            return this.value
        }).get();

        //Make sure we have atleast one item selected
        if (this.idsToFollow.length < 1)
        {
            alert("You must select at least one person to follow.");
            return;
        }

        //Re-initialize items to starting point.
        $("#dialogBackground").show();
        $("#startFollowingBtn").removeAttr("disabled");
        $("#followLoadingImage").hide();
        $("#currentFollowStatus").html("");
        $("#currentFollowStatus").removeClass("error-message");
        $("#nextFollowTime").html("N/A");
        $("#closePopupBtn").hide();


        $("#currentFollowingNumber").html(0);
        $("#totalFollowingNumber").html(this.idsToFollow.length);
    };
    /**
     * Closes the follow popup.
     */
    this.closeFollowPopup = function()
    {
        $("#dialogBackground").hide();
    };
    /**
     * User triggered after setting follow interval.
     * @private
     */
    this.startAutomaticFollowing = function()
    {
        this.followIntervalTimeMinimum = parseInt($("#followIntervalTimeMinimum").val());
        this.followIntervalTimeMaximum = parseInt($("#followIntervalTimeMaximum").val());

        if (!$.isNumeric(this.followIntervalTimeMinimum))
        {
            $("#followIntervalTimeMinimum").addClass("input-error");
            return;
        }
        else
        {
            $("#followIntervalTimeMinimum").removeClass("input-error");
        }
        if (!$.isNumeric(this.followIntervalTimeMaximum))
        {
            $("#followIntervalTimeMaximum").addClass("input-error");
            return;
        }
        else
        {
            $("#followIntervalTimeMaximum").removeClass("input-error");
        }

        if (this.followIntervalTimeMaximum < this.followIntervalTimeMinimum)
        {
            $("#followIntervalTimeMinimum").addClass("input-error");
            $("#followIntervalTimeMaximum").addClass("input-error");
            return;
        }
        else
        {
            $("#followIntervalTimeMinimum").removeClass("input-error");
            $("#followIntervalTimeMaximum").removeClass("input-error");
        }

        //Convert seconds to ms.
        this.followIntervalTime = this.generateIntervalTime();

        $("#startFollowingBtn").attr("disabled", "disabled");
        $("#followLoadingImage").show();

        //Start by following first user immediately, rather than waiting for first countdown.
        this.followNextUser();

    };
    /**
     * Generates a random interval time between (followIntervalTimeMinimum and followIntervalTimeMaximum) * 1000 to get ms.
     * @returns {number} The result in ms.
     */
    this.generateIntervalTime = function()
    {
        var random = Math.floor(Math.random() * (this.followIntervalTimeMaximum - this.followIntervalTimeMinimum + 1)) + this.followIntervalTimeMinimum;
        return random * 1000;
    };
    /**
     * Called for each user that is to be followed. Starts countdown, at the end of which it will call
     * followNextUser.  Relies on followIntervalTime being set and assumes there is another user to follow.
     */
    this.startFollowInterval = function()
    {
        this.followIntervalTime = this.generateIntervalTime();
        $("#nextFollowTime").html(this.followIntervalTime / 1000);

        this.followTicks = 0;

        var _this = this;
        this.followInterval = setInterval(function()
        {
            _this.followTicks++;
            $("#nextFollowTime").html((_this.followIntervalTime - (_this.followTicks * 1000)) / 1000);
            if ((_this.followTicks * 1000) >= _this.followIntervalTime)
            {
                //Timer has expired, Follow user.
                clearInterval(_this.followInterval);
                _this.followNextUser();
            }
        }, 1000);
    };
    /**
     * Plucks the first element in the idsToFollow array and sends a follow request to Twitter for this user.
     * On success assuming there are more users to follow still, this method will start a new interval.
     */
    this.followNextUser = function()
    {
        var nextId = this.idsToFollow.shift();
        var username = $("#username" + nextId).html();

        $("#currentFollowStatus").html("Attempting to follow " + username + ".");


        var _this = this;

        var requestObject = new FindToFollow.FollowJsonRequest();
        requestObject.toFollowUserId = nextId;
        requestObject.twitterUsername = $("#twitterUsername").val();
        requestObject.action = "follow";

        $.post("FindToFollow.php", requestObject)
            .done(function(response)
            {
                response = JSON.parse(response);

                if (response.hasError)
                {
                    $("#currentFollowStatus").html("Error from " + username + ". Stopping all follows. [" + response.errorMessage + "]");
                }
                else
                {
                    $("#currentFollowingNumber").html(parseInt($("#currentFollowingNumber").html()) + 1);

                    //Lets remove table
                    $("#userRow" + nextId).fadeOut(1000, function()
                    {
                        $(this).remove();
                        _this.updateSelectedCount();
                    });


                    if (_this.idsToFollow.length > 0)
                    {
                        //More users to follow still, start timer again.
                        nextId = _this.idsToFollow[0];
                        username = $("#username" + nextId).html();

                        $("#currentFollowStatus").html("Next user to follow is " + username + ".");
                        _this.startFollowInterval();
                    }
                    else
                    {
                        //We have finished following all users.
                        $("#currentFollowStatus").html("Done following all users.");
                        $("#closePopupBtn").show();
                        $("#followLoadingImage").hide();
                    }
                }
            })
            .fail(function()
            {
                $("#currentFollowStatus").addClass("error-message");
                $("#currentFollowStatus").html("An unexpected error occurred during the request.");
            });

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
    this.action = "run";
};
/**
 * Request sent to backend to follow user.
 * @constructor
 */
FindToFollow.FollowJsonRequest = function()
{
    this.toFollowUserId = -1;
    this.twitterUsername = "";
    this.action = "follow";
};
/**
 * Request sent to backend to add users to queue.
 * @constructor
 */
FindToFollow.AddQueueJsonRequest = function()
{
    this.queuedUserIds = [];
    this.twitterUsername = "";
    this.action = "addqueue";
};
/**
 * Request sent to backend to fetch users in queue.
 * @constructor
 */
FindToFollow.FetchQueueJsonRequest = function()
{
    this.twitterUsername = "";
    this.action = "fetchqueue";
};