/* Admin controller */
app.controller("familyController", ["$scope", "$http", "$rootScope",
function($scope, $http, $rootScope)
{
    $scope.init = function() {
        $scope.offset = 0;
        fetchData();
    }

    function fetchData() {
        $http({
            url: "family.php",
            method: "GET",
            params: {offset:$scope.offset}
        }).success(handleResponse);
    }

    function handleResponse(response) {
        if (response.data) {
            $scope.data = response.data;
            $scope.changed = false;
        }
        $scope.message = response.message;
    }

    $scope.submit = function() {
        $http.post("family.php?offset=" + $scope.offset, $scope.data)
            .success(handleResponse);
    }

    $scope.onChange = function() {
        $scope.message = "";
        $scope.changed = true;
    }

    $scope.next = function(offset) {
        $scope.offset += offset;
        fetchData();
    }
}]);
