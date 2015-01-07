/* Printout controller */
app.controller("printController", ["$scope", "$http",
function($scope, $http) {

    $scope.init = function() {
        $http.get("printout.php").success(
            function(response)
            {
                $scope.printout = response.data;
            });
    }
}]);
