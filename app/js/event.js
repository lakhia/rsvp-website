/* Event controller */
app.controller("eventController", ["$scope", "$http", "$rootScope",
function($scope, $http, $rootScope) {
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

    $scope.getDisplayDate = function(date) {
        return $rootScope.getDisplayDate(date);
    }

    $scope.submit = function() {
        $http.post("event.php", $scope.data)
            .success(handleResponse);
    }

    $scope.onChange = function() {
        $scope.msg = "";
        $scope.changed = true;
    }
}]);
