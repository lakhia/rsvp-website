/* Printout controller */
app.controller("printController", ["$scope", "$http", '$rootScope',
function($scope, $http, $rootScope) {
    $scope.fdate;

    $scope.init = function() {
        // Logged in?
        if ($rootScope.isLoggedOut()) {
            return;
        }
        $scope.fdate = new Date();
        fetchData();
    }

    function handleResponse(response) {
        $scope.data = response.data;
        $scope.message = response.message;
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
        dateString = String(date);
        if (dateString.indexOf("T") > -1) {
            date = $rootScope.convertDate(date);
        }
        return $rootScope.getDisplayDate(date);
    }

    function fetchData() {
        $rootScope.fetchData($scope.fdate, null,
            "print.php", handleResponse);
    }

    $scope.next = function(offset) {
        $rootScope.addDaysToDate($scope.fdate, offset);
        fetchData();
    }

}]);
