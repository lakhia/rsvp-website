/* Printout controller */
app.controller("printController", ["$scope", "$http", '$rootScope',
function($scope, $http, $rootScope) {
    $scope.fdate;

    $scope.init = function() {
        $scope.fdate = new Date();
        $http.get("print.php").success(handleResponse);
    }

    function handleResponse(response) {
        $scope.printout = response.data;
        $scope.message = response.message;
    }

    /*
      Go forward and backwards
     */
    $scope.nextDay = function() {
        $rootScope.addDaysToDate($scope.fdate, 1);
        $rootScope.fetchData($scope.fdate, $scope.fdate,
            "print.php", handleResponse);
    }

    $scope.prevDay = function() {
        $rootScope.addDaysToDate($scope.fdate, -1);
        $rootScope.fetchData($scope.fdate, $scope.fdate,
            "print.php", handleResponse);
    }

}]);
