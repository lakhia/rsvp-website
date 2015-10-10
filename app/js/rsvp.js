/* RSVP controller */
app.controller("rsvpController", ["$scope", "$http", "$cookies", '$state',
                                  "$rootScope",
function($scope, $http, $cookies, $state, $rootScope) {
    $scope.changed = false;
    $scope.toggleCount = 0;
    $scope.rsvp = {};
    $scope.fdate;

    $scope.init = function() {
        // Logged in?
        if (!$cookies.token && !$rootScope.name) {
            $state.go("login");
            return;
        }

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

        fetchRsvps();
    }

    function addDaysToDate(date, days) {
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    }

    function convertDate(date) {
        return date.getFullYear() + "-" + (date.getMonth()+1) + "-" + date.getDate();
    }

    function fetchRsvps() {
        var tdate = new Date($scope.fdate.getTime());
        addDaysToDate(tdate, 7);

        $http({
            url: "rsvp.php",
            method: "GET",
            params: {from: convertDate($scope.fdate), to: convertDate(tdate)}
        }).success(
            function(response)
            {
                $scope.message = response.message;
                $scope.rsvp = response.data;
            });
    }

    $scope.getDisplayDate = function(input) {
        input = $scope.rsvp[input].date;
        var parts = input.split('-');
        var d = new Date(parts[0], parts[1]-1, parts[2]);
        input = input.replace(/^\d+-/, "");
        var output = ["Sun","Mon","Tue","Wed","Thr","Fri","Sat"][d.getDay()]
        return output + ", " + input;
    }

    /*
      RSVP related methods
     */

    $scope.nextWeek = function() {
        addDaysToDate($scope.fdate, 7);
        fetchRsvps();
    }

    $scope.prevWeek = function() {
        addDaysToDate($scope.fdate, -7);
        fetchRsvps();
    }

    $scope.editRSVP = function(id) {
        // Clear message
        $scope.message = '';

        // Create a "No" default entry
        if (!$scope.rsvp[id]) {
            $scope.rsvp[id] = { rsvp:"No" };
        }

        // Toggle response to RSVP
        if ($scope.rsvp[id].rsvp == "Yes") {
            $scope.rsvp[id].rsvp = "No";
        } else {
            $scope.rsvp[id].rsvp = "Yes";
        }

        // Keep track of toggle count to enable button
        if ($scope.rsvp[id].toggled) {
            $scope.rsvp[id].toggled = 0;
            $scope.toggleCount--;
        } else {
            $scope.rsvp[id].toggled = 1;
            $scope.toggleCount++;
        }
        $scope.changed = ($scope.toggleCount > 0);
    }

    $scope.submit = function() {
        var toggles = {};
        for (var id in $scope.rsvp) {
            if ($scope.rsvp.hasOwnProperty(id)) {
                if ($scope.rsvp[id].toggled !== undefined) {
                    toggles[$scope.rsvp[id].date] = $scope.rsvp[id].rsvp;
                }
            }
        }

        // TODO: Duplicated in get query
        var tdate = new Date($scope.fdate.getTime());
        addDaysToDate(tdate, 7);

        $http.post("rsvp.php?from=" +
                   convertDate($scope.fdate) + "&to=" + convertDate(tdate),
                   toggles).success(
        function(response)
        {
            $scope.message = response.message;
            $scope.rsvp = response.data;
            $scope.changed = false;
            $scope.toggleCount = 0;

        })
    }
}]);
