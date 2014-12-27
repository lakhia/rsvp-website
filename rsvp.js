var app = angular.module("rsvp", ['ngCookies']);
app.controller("rsvpController", ["$scope", "$http", "$cookies", 
function($scope, $http, $cookies) {
    $scope.changed = false;
    $scope.toggleCount = 0;
    $scope.details = {};
    $scope.cookies = $cookies;
    $scope.fdate;
    $scope.selected = 10;

    $scope.init = function() {
        // Saturday is cutoff to show next week
        $scope.fdate = new Date();
        var day = $scope.fdate.getDay();
        if (day == 6) {
            day = -1;
        }
        addDaysToDate($scope.fdate, (1 - day));

        if ($cookies.name !== undefined) {
            $scope.name = $cookies.name.replace(/\+/g, " ");
        }

        postLogin($cookies.token);
    }
 
    /*
      Login related methods
     */
    $scope.login = function() {
        var request = $http({
            url: "login.php",
            method: "GET",
            params: {thaali: $cookies.thaali, email: $cookies.email}
        });
        request.success(
            function(response)
            {
                $scope.details = response;

                if (!$scope.details.message) {
                    $scope.name = response;
                    postLogin("1");
                }
            });
    }

    $scope.logout = function() {
        $scope.details.message = "You have been logged out";
        $scope.selected = 10;
        delete $cookies.admin;
        delete $cookies.token;
        delete $cookies.name;
        delete $cookies.thaali;
    }

   function postLogin(loggedIn) {
        if (loggedIn) {
            $scope.selected = 0;
            fetchDetails();
        } else {
            $scope.selected = 10;
        }
    }

    function addDaysToDate(date, days) {
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    }

    function convertDate(date) {
        return date.getFullYear() + "-" + (date.getMonth()+1) + "-" + date.getDate();
    }

    function fetchDetails() {
        var tdate = new Date($scope.fdate.getTime());
        addDaysToDate(tdate, 7);

        $http({
            url: "details.php",
            method: "GET",
            params: {from: convertDate($scope.fdate), to: convertDate(tdate)}
        }).success(
            function(response)
            {
                $scope.details = response;
            });
    }

    $scope.getDisplayDate = function(input) {
        input = $scope.details.data[input].date;
        var parts = input.split('-');
        var d = new Date(parts[0], parts[1]-1, parts[2]);
        input = input.replace(new Date().getFullYear() + "-", "");
        var output = ["Sun","Mon","Tue","Wed","Thr","Fri","Sat"][d.getDay()]
        return output + ", " + input;
    }

    /*
      RSVP related methods
     */

    $scope.nextWeek = function() {
        addDaysToDate($scope.fdate, 7);
        fetchDetails();
    }

    $scope.prevWeek = function() {
        addDaysToDate($scope.fdate, -7);
        fetchDetails();
    }

    $scope.disabledRSVP = function(id) {
        return !$scope.details.data[id].enabled;
    }

    $scope.getDetails = function(id) {
        return $scope.details.data[id].details;
    }

    $scope.getRsvp = function(id) {
        if ($scope.details.data[id]) {
            return $scope.details.data[id].rsvp;
        }
        return "No";
    }

    $scope.editRSVP = function(id) {
        // Clear message
        $scope.details.message = '\u00A0';

        // Create a "No" default entry
        if (!$scope.details.data[id]) {
            $scope.details.data[id] = { rsvp:"No" };
        }

        // Toggle response to RSVP
        if ($scope.details.data[id].rsvp == "Yes") {
            $scope.details.data[id].rsvp = "No";
        } else {
            $scope.details.data[id].rsvp = "Yes";
        }

        // Keep track of toggle count to enable button
        if ($scope.details.data[id].toggled) {
            $scope.details.data[id].toggled = 0;
            $scope.toggleCount--;
        } else {
            $scope.details.data[id].toggled = 1;
            $scope.toggleCount++;
        }
        $scope.changed = ($scope.toggleCount > 0);
    }

    $scope.submit = function() {
        var toggles = {};
        for (var id in $scope.details.data) {
            if ($scope.details.data.hasOwnProperty(id)) {
                if ($scope.details.data[id].toggled !== undefined) {
                    toggles[$scope.details.data[id].date] = $scope.details.data[id].rsvp;
                }
            }
        }

        // TODO: Duplicated in get query
        var tdate = new Date($scope.fdate.getTime());
        addDaysToDate(tdate, 7);

        $http.post("details.php?from=" +
                   convertDate($scope.fdate) + "&to=" + convertDate(tdate),
                   toggles).success(
        function(response)
        {
            $scope.details = response;
            $scope.changed = false;
            $scope.toggleCount = 0;

        })
    }

    /*
      filling printout related methods
     */
    function fetchPrintout() {
        $http.get("printout.php").success(
            function(response)
            {
                $scope.printout = response;
            });
    }

    /*
      Tab management
     */
    $scope.selectTab = function(num) {
        if ($cookies.token) {
            $scope.selected = num;
            if (num == 0) {
                fetchDetails();
            } else if (num == 1) {
                fetchPrintout();
            }
        }
    }
    $scope.classTab = function(num) {
        if (!$cookies.token) {
            return "disbld disabled";
        }
        if ($scope.selected == num) {
            return "active";
        }
        return "";
    }
}]);
