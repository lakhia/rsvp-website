/* RSVP controller */
app.controller("rsvpController", ["$scope", "$rootScope",
function($scope, $rootScope) {
    $scope.url = "rsvp.php";

    $scope.init = function() {
        $scope.greet = $rootScope.getName();
        $rootScope.init($scope, handleResponse);
    }

    function handleResponse(response) {
        $scope.msg = response.msg;
        $scope.raw = response.data;
        $scope.data = {};
        $scope.changed = 0;
    }

    $scope.getDisplayDate = function(input) {
        return $rootScope.getDisplayDate($scope.raw[input].date);
    }

    $scope.onChange = function(id) {
        // Clear message
        $scope.msg = '';

        // Toggle response to RSVP
        if ($scope.raw[id].rsvp == "Yes") {
            $scope.raw[id].rsvp = "No";
        } else {
            $scope.raw[id].rsvp = "Yes";
        }

        // Update change count to enable button, update data to send
        var date = $scope.raw[id].date;
        if ($scope.raw[id].toggled) {
            $scope.raw[id].toggled = 0;
            $scope.changed--;
            delete $scope.data[date];
        } else {
            $scope.raw[id].toggled = 1;
            $scope.changed++;
            $scope.data[date] = $scope.raw[id].rsvp;
        }
    }
}]);
