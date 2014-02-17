/**
 * The main class that all of our javascript functionality sits under.
 */
var FindToFollow = new function()
{
    /**
     * Methods called and utilized by the follow page.
     */
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
         * Send a request to backend to find us some followers. This is called by the Filter Followers button
         * on the UI.
         */
        this.sendFilterRequest = function()
        {
            $("#filterResults").removeClass("error-message");

            var requestObject = this.buildFilterRequestObject();

            FindToFollow.disableButton("sendRequestBtn");

            if (this.validateFilterRequest(requestObject))
            {
                $("#filterLoadingImage").show();

                FindToFollow.obscureContainer("filterResults");
                FindToFollow.obscureContainer("logContainer");

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
                        FindToFollow.enableButton("sendRequestBtn");
                        $("#filterLoadingImage").hide();

                        FindToFollow.unObscureContainer("filterResults");
                        FindToFollow.unObscureContainer("logContainer");
                    });
            }
            else
            {
                FindToFollow.enableButton("sendRequestBtn");
            }
        };
        /**
         * Added selected users to our queue. Called by button on UI.
         */
        this.addSelectedUsersToQueue = function()
        {
            var idsToQueue = $("#filterPage input.row-checkbox:checked").map(function()
            {
                return this.value;
            }).get();

            //Make sure we have atleast one item selected
            if (idsToQueue.length < 1)
            {
                FindToFollow.showErrorMessage("You must select at least one person to add to queue.");
                return;
            }

            var requestObject = new FindToFollow.AddQueueJsonRequest();
            requestObject.queuedUserIds = idsToQueue;

            $.post("FindToFollow.php", requestObject)
                .done(function(response)
                {
                    response = JSON.parse(response);

                    if (response.hasError)
                    {
                        FindToFollow.showErrorMessage("Error: " + response.errorMessage);
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
                                FindToFollow.updateSelectedCount();
                            });
                        }

                        FindToFollow.Follow.refreshQueue();
                    }
                })
                .fail(function()
                {
                    FindToFollow.showErrorMessage("Unexpected error when adding users to queue.");
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

            $("#filterPage .row-checkbox").each(function()
            {
                FindToFollow.setRowSelectedState($(this).val(), $(this), checkedValue);
            });

            FindToFollow.updateSelectedCount();

        };

        return this;
    };
    this.UnFollow = new function()
    {
        /**
         * The minimum time between unfollows.
         * @type {number}
         */
        this.unFollowIntervalTimeMinimum = -1;
        /**
         * The maximum time between unfollows.
         * @type {number}
         */
        this.unFollowIntervalTimeMaximum = -1;
        /**
         * The amount of time in ms between each unfollow.
         * @type {number}
         */
        this.unFollowIntervalTime = 0;
        /**
         * The interval # returned from setInterval.
         * @type {null}
         */
        this.unFollowInterval = null;
        /**
         * Array of ids to start unfollowing.
         * @type {Array}
         */
        this.idsToUnFollow = [];
        /**
         * Keeps track of how many ticks of the interval we have had. This allows us to have a countdown timer.
         * @type {number}
         */
        this.unFollowTicks = 0;

        /**
         * Simple Boolean flag that will be true if we are in the middle of processing unfollows.
         * @type {boolean}
         */
        this.processingUnFollows = false;


        /**
         * Refresh the list of users shown on screen that are not following us back.
         */
        this.refreshUsers = function()
        {
            if (FindToFollow.UnFollow.processingUnFollows)
            {
                return;
            }

            //When running locally the refresh can happen so fast we don't even notice,
            //do a little animating to indicate refresh actually happened
            FindToFollow.obscureContainer("unFollowResults");

            var requestObject = new FindToFollow.FetchUnFollowUserJsonRequest();


            FindToFollow.disableButton("refreshUnFollowBtn");
            FindToFollow.disableButton("unFollowUsersBtn");

            $.post("FindToFollow.php", requestObject)
                .done(function(response)
                {
                    response = JSON.parse(response);
                    FindToFollow.unObscureContainer("unFollowResults");

                    if (response.hasError)
                    {
                        FindToFollow.showErrorMessage("Error fetching users to un-follow: " + response.errorMessage);
                    }
                    else
                    {
                        $("#unFollowResults").html(response.html);
                    }
                })
                .fail(function()
                {
                    FindToFollow.showErrorMessage("An unexpected error occurred during the request.");
                })
                .always(function()
                {
                    FindToFollow.enableButton("refreshUnFollowBtn");
                    FindToFollow.enableButton("unFollowUsersBtn");
                });

        };
        /**
         * Called by button on UI. Starts processing the queue with settings supplied on the UI.
         */
        this.startAutomaticUnFollowing = function()
        {
            this.unFollowIntervalTimeMinimum = parseInt($("#unFollowIntervalTimeMinimum").val());
            this.unFollowIntervalTimeMaximum = parseInt($("#unFollowIntervalTimeMaximum").val());


            if (!$.isNumeric(this.unFollowIntervalTimeMinimum))
            {
                $("#unFollowIntervalTimeMinimum").addClass("input-error");
                return;
            }
            else
            {
                $("#unFollowIntervalTimeMinimum").removeClass("input-error");
            }
            if (!$.isNumeric(this.unFollowIntervalTimeMaximum))
            {
                $("#unFollowIntervalTimeMaximum").addClass("input-error");
                return;
            }
            else
            {
                $("#unFollowIntervalTimeMaximum").removeClass("input-error");
            }

            if (this.unFollowIntervalTimeMaximum < this.unFollowIntervalTimeMinimum)
            {
                $("#unFollowIntervalTimeMinimum").addClass("input-error");
                $("#unFollowIntervalTimeMaximum").addClass("input-error");
                return;
            }
            else
            {
                $("#unFollowIntervalTimeMinimum").removeClass("input-error");
                $("#unFollowIntervalTimeMaximum").removeClass("input-error");
            }


            //Convert seconds to ms.
            this.unFollowIntervalTime = FindToFollow.generateIntervalTime(this.unFollowIntervalTimeMinimum, this.unFollowIntervalTimeMaximum);


            this.idsToUnFollow = $("#unFollowPage .user-table-selected").map(function()
            {
                return $(this).data("user-id");

            }).get();

            if (!this.idsToUnFollow || this.idsToUnFollow.length < 1)
            {
                FindToFollow.showErrorMessage("Please select atleast one user to unfollow.");
                return;
            }

            this.preUnFollowSetup();

            //Start by following first user immediately, rather than waiting for first countdown.
            var firstRowId = "unFollowPageUserRow" + this.idsToUnFollow[0];
            FindToFollow.showCountdownOverlay("unFollowPage", firstRowId, 500);

            this.unFollowNextUser();

        };
        /**
         * Initializes the UI for automatic unfollowing i.e disables buttons and form fields.
         */
        this.preUnFollowSetup = function()
        {
            FindToFollow.disableButton("refreshUnFollowBtn");
            FindToFollow.disableButton("unFollowUsersBtn");

            //Hide users we are not unfollowing
            $("#unFollowPage .user-table").each(function(index)
            {
                if (!$(this).hasClass("user-table-selected"))
                {
                    $(this).hide();
                }
                else
                {
                    //Remove green selected background
                    $(this).removeClass("user-table-selected");
                    //Disable clicking of row
                    $(this).attr("onclick", "").unbind("click");
                    //Disable clicking of checkbox
                    $(this).find("input.row-checkbox").attr("disabled", "disabled");
                }
            });


            FindToFollow.UnFollow.processingUnFollows = true;
        };
        /**
         * Re-Enables the UI for automatic unfollowing i.e enables buttons and form fields.
         */
        this.postUnFollowTeardown = function()
        {
            FindToFollow.enableButton("refreshUnFollowBtn");
            FindToFollow.enableButton("unFollowUsersBtn");


            //Re-show users we did not unfollow.
            $("#unFollowPage .user-table").each(function(index)
            {
                if ($(this).is(":visible"))
                {
                    //We must have errored out, re-select row
                    var checkBox = $(this).find("input.row-checkbox");
                    checkBox.attr("disabled", "");

                    $(this).addClass("user-table-selected");

                }

                $(this).show();
            });

            FindToFollow.updateSelectedCount("unFollowPage");


            FindToFollow.UnFollow.processingUnFollows = false;
        };
        /**
         * Plucks the first element in the idsToUnFollow array and sends an unfollow request to Twitter for this user.
         * On success assuming there are more users to unfollow still, this method will start a new interval.
         */
        this.unFollowNextUser = function()
        {
            var nextId = this.idsToUnFollow.shift();
            var rowId = "unFollowPageUserRow" + nextId;

            var requestObject = new FindToFollow.UnFollowJsonRequest();
            requestObject.toUnFollowUserId = nextId;

            $.post("FindToFollow.php", requestObject)
                .done(function(response)
                {
                    response = JSON.parse(response);

                    if (response.hasError)
                    {
                        FindToFollow.showErrorMessage("Error from twitter. Stopping all unfollows. [" + response.errorMessage + "]");
                        FindToFollow.changePage('unFollowPage');
                        FindToFollow.UnFollow.postUnFollowTeardown();
                    }
                    else
                    {
                        //Remove the row from UI
                        $("#" + rowId).remove();

                        if (FindToFollow.UnFollow.idsToUnFollow.length > 0)
                        {
                            FindToFollow.UnFollow.startUnFollowInterval();
                        }
                        else
                        {
                            FindToFollow.UnFollow.postUnFollowTeardown();
                        }
                    }
                })
                .fail(function()
                {
                    FindToFollow.showErrorMessage("An unexpected error occurred during the request.", 60000);
                    FindToFollow.changePage('unFollowPage');
                    FindToFollow.UnFollow.postUnFollowTeardown();
                });

        };
        /**
         * Called for each user that is to be unfollowed. Starts countdown, at the end of which it will call
         * unFollowNextUser.  Relies on unFollowIntervalTime being set and assumes there is another user to unfollow.
         */
        this.startUnFollowInterval = function()
        {
            this.unFollowIntervalTime = FindToFollow.generateIntervalTime(this.unFollowIntervalTimeMinimum, this.unFollowIntervalTimeMaximum);
            this.unFollowTicks = 0;

            var nextId = this.idsToUnFollow[0];
            var rowId = "unFollowPageUserRow" + nextId;

            FindToFollow.showCountdownOverlay("unFollowPage", rowId, this.unFollowIntervalTime + 1000);


            var _this = this;
            this.unFollowInterval = setInterval(function()
            {
                _this.unFollowTicks++;

                if ((_this.unFollowTicks * 1000) >= _this.unFollowIntervalTime)
                {
                    //Timer has expired, Follow user.
                    clearInterval(_this.unFollowInterval);
                    _this.unFollowNextUser();
                }
            }, 1000);
        };
    };
    /**
     * All methods utilized by Follow Page.
     */
    this.Follow = new function()
    {
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
        /**
         * A boolean flag that when set to true indicates we are in the middle of processing the queue.
         * @type {boolean}
         */
        this.processingQueue = false;

        /**
         * Refreshes the screen with all users in queue.
         */
        this.refreshQueue = function()
        {
            if (FindToFollow.Follow.processingQueue)
            {
                return;
            }

            //When running locally the reresh can happen so fast we don't even notice,
            //do a little animating to indicate refresh actually happened
            FindToFollow.obscureContainer("followResults");

            var requestObject = new FindToFollow.FetchQueueJsonRequest();


            $.post("FindToFollow.php", requestObject)
                .done(function(response)
                {

                    response = JSON.parse(response);
                    FindToFollow.unObscureContainer("followResults");

                    if (response.hasError)
                    {
                        FindToFollow.showErrorMessage("Error fetching current queue: " + response.errorMessage);
                    }
                    else
                    {
                        $("#followResults").html(response.html);
                    }
                })
                .fail(function()
                {
                    FindToFollow.showErrorMessage("An unexpected error occurred during the request.");
                });
        };
        /**
         * Called by button on UI. Starts processing the queue with settings supplied on the UI.
         */
        this.startAutomaticFollowing = function()
        {
            this.followIntervalTimeMinimum = parseInt($("#followIntervalTimeMinimum").val());
            this.followIntervalTimeMaximum = parseInt($("#followIntervalTimeMaximum").val());
            var followMaximum = parseInt($("#followMaximum").val());

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
            if (!$.isNumeric(followMaximum) || followMaximum < 1)
            {
                $("#followMaximum").addClass("input-error");
                return;
            }
            else
            {
                $("#followMaximum").removeClass("input-error");
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
            this.followIntervalTime = FindToFollow.generateIntervalTime(this.followIntervalTimeMinimum, this.followIntervalTimeMaximum);


            //Remove all from visible queue that are past the amount we are following
            $("#followPage .user-table").each(function(index)
            {
                if (index >= followMaximum)
                {
                    $(this).remove();
                }
            });

            this.idsToFollow = $("#followPage .user-table").map(function()
            {
                return $(this).data("user-id");

            }).get();

            FindToFollow.Follow.preFollowSetup();

            //Start by following first user immediately, rather than waiting for first countdown.
            var firstRowId = "followPageUserRow" + this.idsToFollow[0];
            FindToFollow.showCountdownOverlay("followPage", firstRowId, 500);

            this.followNextUser();

        };

        /**
         * Called for each user that is to be followed. Starts countdown, at the end of which it will call
         * followNextUser.  Relies on followIntervalTime being set and assumes there is another user to follow.
         */
        this.startFollowInterval = function()
        {
            this.followIntervalTime = FindToFollow.generateIntervalTime(this.followIntervalTimeMinimum, this.followIntervalTimeMaximum);
            this.followTicks = 0;

            var nextId = this.idsToFollow[0];
            var rowId = "followPageUserRow" + nextId;

            FindToFollow.showCountdownOverlay("followPage", rowId, this.followIntervalTime + 1000);


            var _this = this;
            this.followInterval = setInterval(function()
            {
                _this.followTicks++;

                if ((_this.followTicks * 1000) >= _this.followIntervalTime)
                {
                    //Timer has expired, Follow user.
                    clearInterval(_this.followInterval);
                    _this.followNextUser();
                }
            }, 1000);
        };
        /**
         * Initializes the UI for automatic following i.e disables buttons and form fields.
         */
        this.preFollowSetup = function()
        {
            FindToFollow.disableButton("followUsersBtn");
            FindToFollow.disableButton("refreshQueueBtn");

            FindToFollow.Follow.processingQueue = true;
        };
        /**
         * Re-Enables the UI for automatic following i.e enables buttons and form fields.
         */
        this.postFollowTeardown = function()
        {
            FindToFollow.enableButton("followUsersBtn");
            FindToFollow.enableButton("refreshQueueBtn");

            FindToFollow.Follow.processingQueue = false;
        };
        /**
         * Plucks the first element in the idsToFollow array and sends a follow request to Twitter for this user.
         * On success assuming there are more users to follow still, this method will start a new interval.
         */
        this.followNextUser = function()
        {
            var nextId = this.idsToFollow.shift();
            var rowId = "followPageUserRow" + nextId;

            var requestObject = new FindToFollow.FollowJsonRequest();
            requestObject.toFollowUserId = nextId;

            $.post("FindToFollow.php", requestObject)
                .done(function(response)
                {
                    response = JSON.parse(response);

                    if (response.hasError && !response.continue)
                    {
                        FindToFollow.showErrorMessage("Error from twitter. Stopping all follows. [" + response.errorMessage + "]");
                        FindToFollow.Follow.postFollowTeardown();
                    }
                    else
                    {
                        if(response.hasError && response.continue)
                        {
                            //There was an error but we can skip
                            FindToFollow.showErrorMessage(response.errorMessage);

                        }

                        //Remove the row from UI
                        $("#" + rowId).remove();

                        if (FindToFollow.Follow.idsToFollow.length > 0)
                        {
                            FindToFollow.Follow.startFollowInterval();
                        }
                        else
                        {
                            //We have finished following all users.
                            $("#followResults").html("All items have been processed. Please refresh the queue. If no more items are in the queue please add them.");
                            FindToFollow.Follow.postFollowTeardown();
                        }
                    }
                })
                .fail(function()
                {
                    FindToFollow.showErrorMessage("An unexpected error occurred during the request.", 60000);
                    FindToFollow.Follow.postFollowTeardown();
                });

        };
    };
    this.Authenticate = new function()
    {
        this.switchUser = function()
        {
            var requestObject = new FindToFollow.SwitchUserJsonRequest();

            $.post("FindToFollow.php", requestObject)
                .done(function(response)
                {
                    location.reload();
                })
                .fail(function()
                {
                    FindToFollow.showErrorMessage("An unexpected error occurred during the request.");
                });
        };
        this.toggleSwitchUserWarning = function(show)
        {
            if (show)
            {
                $(".switch-user-warning").show();
            }
            else
            {
                $(".switch-user-warning").hide();
            }
        };
    };

    /**
     * The HTML Id of the current visible page.
     * @type {string}
     */
    this.currentPageId = "filterPage";
    this.lastClickedTable = null;

    /**
     * Show global error message.
     * @param {String} message The error message to display.
     * @param {number=} timeout Defaults to 15000, time in MS to show the message.
     */
    this.showErrorMessage = function(message, timeout)
    {
        timeout = timeout || 15000;

        $("#errorBar").show();

        var messageId = "message" + Math.floor(Math.random() * 10000);

        $("#errorBar").append('<p id="' + messageId + '">' + message + '</p>');
        setTimeout(function()
        {
            $("#" + messageId).remove();
            if ($("#errorBar").children("*").length < 1)
            {
                $("#errorBar").hide();
            }
        }, timeout);
    };
    /**
     * Called each time a tab is clicked.
     * @param page
     */
    this.changePage = function(page)
    {
        $(".page").each(function()
        {
            if ($(this).attr("id") == page)
            {
                $("#" + page + "Tab").addClass("tab-selected");
                $(this).show();
                FindToFollow.currentPageId = $(this).attr("id");
            }
            else
            {
                $("#" + $(this).attr("id") + "Tab").removeClass("tab-selected");
                $(this).hide();
            }
        });
        FindToFollow.lastClickedTable = null;
        FindToFollow.repositionCountdownOverlay(page);
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

        //Change state of clicked row.
        FindToFollow.setRowSelectedState(id, checkBox, !currentlyChecked);


        if (event.shiftKey && FindToFollow.lastClickedTable)
        {
            //Shift click, select or unselect multiple rows.
            var maximum = Math.max(FindToFollow.lastClickedTable.data('count'), table.data('count'));
            var minimum = Math.min(FindToFollow.lastClickedTable.data('count'), table.data('count'));

            $("#" + this.currentPageId).find(".user-table").each(function()
            {
                var userTable = $(this);
                var count = userTable.data("count");

                if (count >= minimum && count <= maximum && userTable.attr('id') != table.attr('id'))
                {
                    var userTableCheckBox = userTable.find("#checked");
                    FindToFollow.setRowSelectedState(userTableCheckBox.val(), userTableCheckBox, !currentlyChecked);
                }
            });

        }

        FindToFollow.updateSelectedCount();
        FindToFollow.lastClickedTable = table;
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
     * @param {String=} pageId Optional pageId, that when set to false will revert to the current pageId.
     */
    this.updateSelectedCount = function(pageId)
    {
        pageId = pageId || this.currentPageId;
        var checkedCount = $("#" + pageId + " input.row-checkbox:checked").map(function()
        {
            return this.value
        }).get().length;
        $("#" + pageId + "SelectedCount").html(checkedCount);
    };
    /**
     * Both disable the button physically and visibly.
     */
    this.disableButton = function(buttonId)
    {
        $("#" + buttonId).addClass("disabled-button");
        $("#" + buttonId).attr("disabled", "disabled");
    };
    this.enableButton = function(buttonId)
    {
        $("#" + buttonId).removeClass("disabled-button");
        $("#" + buttonId).removeAttr("disabled");
    };
    /**
     * Generates a random interval time between (minimum and maximum) * 1000 to get ms.
     * @param {Number} minimum The minimum value our interval should be, in seconds.
     * @param {Number} maximum The maximum value our interval should be, in seconds.
     * @returns {number} The result in ms.
     */
    this.generateIntervalTime = function(minimum, maximum)
    {
        var random = Math.floor(Math.random() * (maximum - minimum + 1)) + minimum;
        return random * 1000;
    };
    /**
     * Shows an overlay over the row that acts as a progress bar getting going from 100% -> 0% width in teh given timeout.
     * @param {String} pageId The id of the page this overlay will be shown on.
     * @param {string} rowId The id of the row the overlay will sit on top of.
     * @param {number} timeout The timeout in ms.
     */
    this.showCountdownOverlay = function(pageId, rowId, timeout)
    {
        var row = $("#" + rowId);
        var top = row.position().top;
        var left = row.position().left;
        var width = row.width();
        var height = row.height();

        var overlayId = rowId + "Overlay";
        var div = '<div class="overlay-tracker" id="' + overlayId + '" style="margin:0;z-index: 500; height: ' + height + 'px;width: ' + width + 'px;position:absolute; top: ' + top + 'px; left: ' + left + 'px; background-color: rgba(181, 255, 170,0.5);">&nbsp;</div>';

        $("#" + pageId + " .rightColumn .content-block").append(div);
        $("#" + overlayId).animate({width: 0}, timeout, "swing", function()
        {
            $("#" + overlayId).remove();
        });

    };
    /**
     * If we attempt to build a countdown overlay for a page that his hidden,
     * jquery can't properly calculate the size of the overlay so it gets set to 0.
     *
     * This forces the overlay to be the correct size on the page by recalculating the dimensions.
     * @param {String} pageId The id of the page we are repositioning the overlay on.
     */
    this.repositionCountdownOverlay = function(pageId)
    {
        if (pageId != "unFollowPage" && pageId != "followPage")
        {
            return;
        }

        var overlay = $("#" + pageId + " .rightColumn .content-block .overlay-tracker");

        if (overlay.length)
        {
            var row = $("#" + pageId + " .rightColumn .content-block .user-table:visible").first();

            var top = row.position().top;
            var left = row.position().left;
            var width = row.width();
            var height = row.height();
            overlay.css({height: height, top: top, left: left});
        }
    };
    /**
     * Obscure the results, just a nice UI effect for when we are refreshing the results.
     * @param {String} containerId Id of the container to obscure.
     */
    this.obscureContainer = function(containerId)
    {
        $("#" + containerId).fadeTo(200, 0.2);
    };
    /**
     * Fades back to full opacity once results have been refreshed.
     * @param {String} containerId Id of the container to obscure.
     */
    this.unObscureContainer = function(containerId)
    {
        $("#" + containerId).fadeTo(250, 1);
    };

};
/**
 * Structure for requests sent to backend.
 * @constructor
 */
FindToFollow.FilterJsonRequest = function()
{
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
 * Request sent to backend to unfollow user.
 * @constructor
 */
FindToFollow.UnFollowJsonRequest = function()
{
    this.toUnFollowUserId = -1;
    this.action = "unfollow";
};
/**
 * Request sent to backend to follow user.
 * @constructor
 */
FindToFollow.FollowJsonRequest = function()
{
    this.toFollowUserId = -1;
    this.action = "follow";
};
/**
 * Request sent to backend to add users to queue.
 * @constructor
 */
FindToFollow.AddQueueJsonRequest = function()
{
    this.queuedUserIds = [];
    this.action = "addqueue";
};
/**
 * Request sent to backend to fetch users in queue.
 * @constructor
 */
FindToFollow.FetchQueueJsonRequest = function()
{
    this.action = "fetchqueue";
};
FindToFollow.FetchUnFollowUserJsonRequest = function()
{
    this.action = "fetchunfollowusers";
};
FindToFollow.SwitchUserJsonRequest = function()
{
    this.action = "switchuser";
};

$(document).ready(function()
{
    //Pull queue on initial load of application.
    FindToFollow.Follow.refreshQueue();
});