/* Family controller */
app.controller("familyController", ["$scope", "$rootScope",
function($scope, $rootScope) {
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

    $scope.onChange = function() {
        $scope.msg = "";
        $scope.changed = true;
    }
}]);
