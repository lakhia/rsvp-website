/* Admin controller */
app.controller("adminController", ["$scope", "$http", "$cookies", "$state",
                                   "$rootScope",
function($scope, $http, $cookies, $state, $rootScope)
{
    $scope.init = function() {
        if (!$cookies.admin && !$rootScope.name ) {
            $state.go('login');
        }
    }

    $scope.insert_week = function() {

        $http.post("admin.php", {'date': $scope.date,
                                 'details': $scope.details}).success(
            function(response)
            {
                $scope.message = response.message;
            });
    }
}]);
