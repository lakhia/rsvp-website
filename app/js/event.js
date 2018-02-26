/* Event controller */
app.controller("eventController", ["$scope", "$rootScope",
function($scope, $rootScope) {
    $scope.date = "";
    $scope.url = "event.php";

    $scope.init = function() {
        $rootScope.init($scope, handleResponse);
    }

    function handleResponse(response) {
        if (response.data) {
            $scope.data = response.data;
        }
        if (response.date) {
            $scope.date = response.date;
        }
        $scope.msg = response.msg;
        $scope.changed = false;
    }

    $scope.getDisplayDate = $rootScope.getDisplayDate;

    $scope.onChange = function() {
        $scope.msg = "";
        $scope.changed = true;
    }
}]);
