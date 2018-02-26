/* Printout controller */
app.controller("printController", ["$scope", '$rootScope',
function($scope, $rootScope) {
    $scope.date = "";
    $scope.url = "print.php";

    $scope.init = function() {
        $rootScope.init($scope, handleResponse);
        $scope.sortColumn = 'thaali';
        $scope.changed = false;
    }

    function handleResponse(response) {
        $scope.data = response.data;
        $scope.date = response.date;
        $scope.msg = response.msg;
        $scope.save = response.other;
        $scope.changed = false;
    }

    $scope.onChange = function() {
        $scope.msg = "";
        $scope.changed = true;
    }

    $scope.sorterFunc = function(item) {
        if ($scope.sortColumn == 'thaali') {
            return parseInt(item.thaali);
        } else {
            return item[$scope.sortColumn];
        }
    }

    $scope.getDisplayDate = $rootScope.getDisplayDate;

    $scope.reset = function(nodes) {
        $scope.availCounter = 0;
        $scope.fillCounter = 0;
        $scope.changed = true;

        angular.forEach($scope.data, function(item) {
            item.avail = 0;
            item.filled = 0;
        });
    }
}]);
