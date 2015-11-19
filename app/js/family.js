/* Family controller */
app.controller("familyController", ["$scope", "$http", "$rootScope",
function($scope, $http, $rootScope) {
    $scope.url = "family.php";

    $scope.init = function() {
        $rootScope.init($scope, handleResponse);
    }

    function handleResponse(response) {
        if (response.data) {
            $scope.data = response.data;
            $scope.changed = false;
        }
        $scope.msg = response.msg;
    }

    $scope.submit = function() {
        $http.post("family.php?offset=" + $scope.offset, $scope.data)
            .success(handleResponse);
    }

    $scope.onChange = function() {
        $scope.msg = "";
        $scope.changed = true;
    }
}]);
