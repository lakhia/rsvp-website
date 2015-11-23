/* RSVP controller */
app.controller("rsvpController", ["$scope", "$http", "$rootScope",
function($scope, $http, $rootScope) {
    $scope.url = "rsvp.php";

    $scope.init = function() {
        $scope.greet = $rootScope.getName();
        $rootScope.init($scope, handleResponse);
    }

    function handleResponse(response) {
        $scope.msg = response.msg;
        $scope.data = response.data;
        $scope.changed = 0;
    }

    $scope.getDisplayDate = function(input) {
        return $rootScope.getDisplayDate($scope.data[input].date);
    }

    $scope.editRSVP = function(id) {
        // Clear message
        $scope.msg = '';

        // Toggle response to RSVP
        if ($scope.data[id].rsvp == "Yes") {
            $scope.data[id].rsvp = "No";
        } else {
            $scope.data[id].rsvp = "Yes";
        }

        // Keep track of toggle count to enable button
        if ($scope.data[id].toggled) {
            $scope.data[id].toggled = 0;
            $scope.changed--;
        } else {
            $scope.data[id].toggled = 1;
            $scope.changed++;
        }
    }

    $scope.submit = function() {
        var toggles = {};
        for (var id in $scope.data) {
            if ($scope.data[id].toggled !== undefined) {
                toggles[$scope.data[id].date] = $scope.data[id].rsvp;
            }
        }

        $http.post("rsvp.php?offset=" +
                   $scope.offset,
                   toggles)
            .success(handleResponse).error($scope.error);
    }
}]);
