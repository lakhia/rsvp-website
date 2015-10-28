/* Event controller */
app.controller("eventController", ["$scope", "$http", "$rootScope",
function($scope, $http, $rootScope) {
    $scope.fdate;

    $scope.init = function() {
        $scope.fdate = new Date();
        var day = $scope.fdate.getDay();
        if (day == 6) {
            day = -1;
        }
        $rootScope.addDaysToDate($scope.fdate, (1 - day));
        fetchData();
    }

    function fetchData() {
        var tdate = new Date($scope.fdate.getTime());
        $rootScope.addDaysToDate(tdate, 7);
        $rootScope.fetchData($scope.fdate, tdate, "event.php", handleResponse)
    }

    function handleResponse(response) {
        if (response.data) {
            $scope.data = response.data;
        }
        $scope.message = response.message;
        $scope.changed = false;
    }

    $scope.getDisplayDate = function(date) {
        dateString = String(date);
        if (dateString.indexOf("T") > -1) {
            date = $rootScope.convertDate(date);
        }
        return $rootScope.getDisplayDate(date);
    }

    $scope.submit = function() {
        $http.post("event.php", $scope.data)
            .success(handleResponse);
    }

    $scope.onChange = function() {
        $scope.message = "";
        $scope.changed = true;
    }

    /*
      Go forward and backwards
     */
    $scope.nextWeek = function() {
        $rootScope.addDaysToDate($scope.fdate, 7);
        fetchData();
    }

    $scope.prevWeek = function() {
        $rootScope.addDaysToDate($scope.fdate, -7);
        fetchData();
    }

}]);
