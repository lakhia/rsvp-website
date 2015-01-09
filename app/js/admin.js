/* Admin controller */
app.controller("adminController", ["$scope", "$http", "$cookies", "$state",
function($scope, $http, $cookies, $state)
{
    $scope.init = function() {
        if ( !$cookies.admin || !$scope.name ) {
            $state.go('login');
        }
    }

    $scope.insert_week = function() {
        var newdate = $scope.newMealDate; console.log(newdate);
        var newdetails = $scope.newMealDetails; console.log(newdetails);

        $http.post("admin.php",
                   {'date': newdate, 'details': newdetails}).success(
            function(response)
            {
                $scope.message = response.message;
            });
    }
}]);
