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
        $rootScope.addDaysToDate($scope.fdate, (1 - day));

        if ($cookies.name !== undefined) {
            $scope.name = $cookies.name.replace(/\+/g, " ");
        }

        fetchRsvps();
    }

    function convertDate(date) {
        return date.getFullYear() + "-" + (date.getMonth()+1) + "-" + date.getDate();
    }

    function handleResponse(response) {
        $scope.message = response.message;
        $scope.rsvp = response.data;
        $scope.changed = false;
        $scope.toggleCount = 0;
    }

    function fetchRsvps() {
        var tdate = new Date($scope.fdate.getTime());
        $rootScope.addDaysToDate(tdate, 7);
        $rootScope.fetchData($scope.fdate, tdate, "rsvp.php", handleResponse)
    }

    $scope.getDisplayDate = function(input) {
        return $rootScope.getDisplayDate($scope.rsvp[input].date);
    }

    /*
      RSVP related methods
     */

    $scope.nextWeek = function() {
        $rootScope.addDaysToDate($scope.fdate, 7);
        fetchRsvps();
    }

    $scope.prevWeek = function() {
        $rootScope.addDaysToDate($scope.fdate, -7);
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
        $rootScope.addDaysToDate(tdate, 7);

        $http.post("rsvp.php?from=" +
                   convertDate($scope.fdate) + "&to=" + convertDate(tdate),
                   toggles)
             .success(handleResponse);
    }
}]);
