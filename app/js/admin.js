/* Admin controller */
app.controller("adminController", ["$scope", "$http", "$cookies", "$state",
                                   "$rootScope",
function($scope, $http, $cookies, $state, $rootScope)
{
    $scope.init = function() {
        if (!$cookies.adv && !$rootScope.name ) {
            $state.go('login');
        }

        fetchFutureWeeks();
    }

    $scope.insert_week = function() {

        $http.post("admin.php", {'date': $scope.date,
                                 'details': $scope.details,
                                 'action': 'insert'}).success(
            function(response)
            {
                $scope.message = response.message;
                $scope.date = "";
                $scope.details = "";
            });
    }

    $scope.delete_week = function(key) {

        $http.post("admin.php", {'date': key,
                                 'action': 'delete'}).success(
            function(response)
            {
                $scope.message = response.message;
            });
    }

    $scope.edit_week = function(key) {

        $http.post("admin.php", {'date': key,
                                 'details': $scope.details, 
                                 'action': 'edit'}).success(
            function(response)
            {
                $scope.message = response.message;
            });
    }


    function fetchFutureWeek()
    {
        $http({
            url: "admin.php",
            method: "GET",
            params: {future: true}
        }).success(
            function(response)
            {
                $scope.future_weeks = response.data;
            });
    }
}]);
