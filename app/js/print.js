/* Printout controller */
app.controller("printController", ["$scope", '$rootScope',
function($scope, $rootScope) {

    $scope.init = function() {
        $rootScope.init($scope, "print.php", null);
        $scope.sortColumn = 'thaali';
    }

    $scope.sorterFunc = function(item) {
        if ($scope.sortColumn == 'thaali') {
            return parseInt(item.thaali);
        } else {
            return item[$scope.sortColumn];
        }
    }

    $scope.firstLine = function(other) {
        if (!other) return "";
        if (other.niyaz == "1") {
            return "Adults: " + other.adults + ", Kids: " + other.kids;
        } else {
            var sum = $scope.data.reduce(function(prev, elem) {
                if (elem.filled == '0') {
                    prev[0]++;
                    if (elem.here == '0') {
                        prev[1]++;
                    }
                }
                return prev;
            }, [0, 0]);
            return "Not filled: " + sum[0] + ", total: " + $scope.data.length;
        }
    }
    $scope.secondLine = function(other) {
        if (!other) return "";
        if (other.niyaz == "1") {
            return "Thaals: " + (other.adults / 8 + other.kids / 16).toFixed(1);
        } else {
            var sizes = $scope.data.reduce(function(prev, elem) {
                if (elem.filled == '0') {
                    if (prev[elem.size]) {
                        prev[elem.size]++;
                    } else {
                        prev[elem.size] = 1;
                    }
                }
                return prev;
            }, {});
            return "Sizes: " + JSON.stringify(sizes, null, 1).replace(/"/g, '')
                .replace('{', '').replace('}', '');
        }
    }

    $scope.reset = function(nodes) {
        $scope.changed = true;
        angular.forEach($scope.data, function(item) {
            item.here = 0;
            item.filled = 0;
        });
    }
}]);
