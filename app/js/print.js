/* Printout controller */
app.controller("printController", ["$scope", "$http", '$rootScope',
function($scope, $http, $rootScope) {
    $scope.fdate;

    $scope.init = function() {
        $scope.fdate = new Date();
        fetchData();
    }

    function handleResponse(response) {
        $scope.data = response.data;
        $scope.message = response.message;
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
