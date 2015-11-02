/* RSVP controller */
app.controller("rsvpController", ["$scope", "$http", "$cookies", '$state',
                                  "$rootScope",
function($scope, $http, $cookies, $state, $rootScope) {
    $scope.changed = false;
    $scope.toggleCount = 0;
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

        $scope.greet = $rootScope.getName();

        fetchData();
    }

    function convertDate(date) {
        return date.getFullYear() + "-" + (date.getMonth()+1) + "-" + date.getDate();
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

    /*
      RSVP related methods
     */

    $scope.next = function() {
        $rootScope.addDaysToDate($scope.fdate, 7);
        fetchData();
    }

    $scope.prev = function() {
        $rootScope.addDaysToDate($scope.fdate, -7);
        fetchData();
    }

    $scope.editRSVP = function(id) {
        // Clear message
        $scope.message = '';

        // Create a "No" default entry
        if (!$scope.data[id]) {
            $scope.data[id] = { rsvp:"No" };
        }

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
            if ($scope.data.hasOwnProperty(id)) {
                if ($scope.data[id].toggled !== undefined) {
                    toggles[$scope.data[id].date] = $scope.data[id].rsvp;
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
