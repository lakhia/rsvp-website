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
                   {'date': newdate, 'details': newdetails, 'action': 'new'}).success(
            function(response)
            {
                $scope.message = response.message;
            });
    }

    $scope.causeDelete = function(key) {
        $http.post("admin.php", 
            {'date': $scope.editDetails[key], 'action': 'delete'}).success(
        function(response)
        {
            $scope.message = response.message();
        });
    }

    $scope.causeEdit = function(key) {
        var editedDate = key;
        var editedDetails = $scope.editDetails[key].details;

        $http.post("admin.php",
                   {'date': editedDate, 'details': editedDetails, 'action': 'edit'}).success(
            function(response)
            {
                $scope.message = response.message;
            });
    }
}]);
