/* Printout controller */
app.controller("printController", ["$scope", "$http", '$rootScope',
function($scope, $http, $rootScope) {
    $scope.date = "";
    $scope.url = "print.php";

    $scope.init = function() {
        $rootScope.init($scope, handleResponse);
    }

    function handleResponse(response) {
        $scope.data = response.data;
        $scope.date = response.date;
        $scope.msg = response.msg;
    }

    $scope.getClass = function(index) {
        if (index >= 1) {
            if (parseInt($scope.data[index]["thaali"]) !=
                parseInt($scope.data[index-1]["thaali"]) + 1) {
                return  "msg glyphicon glyphicon-scissors";
            }
        }
        return "";
    }

    $scope.getDisplayDate = function(date) {
        return $rootScope.getDisplayDate(date);
    }
}]);
