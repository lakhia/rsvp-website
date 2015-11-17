/* RSVP controller */
app.controller("rsvpController", ["$scope", "$http", "$rootScope",
function($scope, $http, $rootScope) {
    $scope.changed = false;
    $scope.toggleCount = 0;
    $scope.fdate;

    $scope.init = function() {
        // Logged in?
        if ($rootScope.isLoggedOut()) {
            return;
        }

        // Saturday is cutoff to show next week
        $scope.fdate = new Date();
        var day = $scope.fdate.getDay();
        if (day == 6) {
            day = -1;
        }
        $rootScope.addDaysToDate($scope.fdate, (1 - day));

        $scope.greet = $rootScope.getName();

        fetchData();
    }

    function handleResponse(response) {
        $scope.message = response.message;
        $scope.data = response.data;
        $scope.changed = false;
        $scope.toggleCount = 0;
    }

    function fetchData() {
        var tdate = new Date($scope.fdate.getTime());
        $rootScope.addDaysToDate(tdate, 7);
        $rootScope.fetchData($scope.fdate, tdate, "rsvp.php", handleResponse)
    }

    $scope.getDisplayDate = function(input) {
        return $rootScope.getDisplayDate($scope.data[input].date);
    }

    $scope.next = function(offset) {
        if ($scope.toggleCount == 0 ||
            window.confirm("Unsaved changes, proceed anyway?"))
        {
            $rootScope.addDaysToDate($scope.fdate, offset);
            fetchData();
        }
    }

    $scope.editRSVP = function(id) {
        // Clear message
        $scope.message = '';

        // Toggle response to RSVP
        if ($scope.data[id].rsvp == "Yes") {
            $scope.data[id].rsvp = "No";
        } else {
            $scope.data[id].rsvp = "Yes";
        }

        // Keep track of toggle count to enable button
        if ($scope.data[id].toggled) {
            $scope.data[id].toggled = 0;
            $scope.toggleCount--;
        } else {
            $scope.data[id].toggled = 1;
            $scope.toggleCount++;
        }
        $scope.changed = ($scope.toggleCount > 0);
    }

    $scope.submit = function() {
        var toggles = {};
        for (var id in $scope.data) {
            if ($scope.data[id].toggled !== undefined) {
                toggles[$scope.data[id].date] = $scope.data[id].rsvp;
            }
        }

        // TODO: Duplicated in get query
        var tdate = new Date($scope.fdate.getTime());
        $rootScope.addDaysToDate(tdate, 7);

        $http.post("rsvp.php?from=" +
                   $rootScope.convertDate($scope.fdate) +
                   "&to=" + $rootScope.convertDate(tdate),
                   toggles)
             .success(handleResponse);
    }
}]);
