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
        $scope.o = response.other;
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

    $scope.firstLine = function(other) {
        if (!$scope.data) return "";
        if (other.niyaz == "1") {
            return "Adults: " + other.adults + ", Kids: " + other.kids;
        } else {
            var sum = $scope.data.reduce(function(prev, elem) {
                if (elem.here == '0' && elem.filled == '0') {
                    return prev + 1;
                } else {
                    return prev;
                }
            }, 0);
            return "Not here: " + sum + " / " + $scope.data.length;
        }
    }
    $scope.secondLine = function(other) {
        if (other.niyaz == "1") {
            return "Thaals: " + (other.adults / 8 + other.kids / 16).toFixed(1);
        } else {
            var sum = $scope.data.reduce(function(prev, elem) {
                if (elem.filled == '0') {
                    return prev + 1;
                } else {
                    return prev;
                }
            }, 0);
            return "Not filled: " + sum + " / " + $scope.data.length;
        }
    }

    $scope.getDisplayDate = $rootScope.getDisplayDate;

    $scope.reset = function(nodes) {
        $scope.changed = true;
        angular.forEach($scope.data, function(item) {
            item.here = 0;
            item.filled = 0;
        });
    }
}]);
