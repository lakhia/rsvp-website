/* Admin controller */
app.controller("familyController", ["$scope", "$http", "$rootScope",
function($scope, $http, $rootScope)
{
    $scope.init = function() {
        fetchData();
    }

    function fetchData() {
        $rootScope.fetchData(null, null, "family.php",
            handleResponse)
    }

    function handleResponse(response) {
        if (response.data) {
            $scope.data = response.data;
        }
        $scope.message = response.message;
        $scope.changed = false;
    }

}]);
