/* Printout controller */
app.controller("printController", ["$scope", '$rootScope',
function($scope, $rootScope) {

    $scope.init = function() {
        $rootScope.init($scope, "print.php", null);
        $scope.sortColumn = 'thaali';
        $scope.filterNames = {};
    }

    $scope.filterFunc = function(item) {
        if ($scope.filterNames.name) {
            if (item.name.indexOf($scope.filterNames.name) < 0) {
                item.filtered = true;
                return false;
            }
        }
        if ($scope.filterNames.size) {
            if (item.size != $scope.filterNames.size.toUpperCase()) {
                item.filtered = true;
                return false;
            }
        }
        if ($scope.filterNames.here) {
            if ((!item.here && $scope.filterNames.here.toUpperCase() == 'Y') ||
                (item.here && $scope.filterNames.here.toUpperCase() == 'N')) {
                item.filtered = true;
                return false;
            }
        }
        if ($scope.filterNames.filled) {
            if ((!item.filled && $scope.filterNames.filled.toUpperCase() == 'Y') ||
                (item.filled && $scope.filterNames.filled.toUpperCase() == 'N')) {
                item.filtered = true;
                return false;
            }
        }
        if ($scope.filterNames.area) {
            if (item.area != $scope.filterNames.area) {
                item.filtered = true;
                return false;
            }
        }
        item.filtered = false;
        return true;
    }

    $scope.sorterFunc = function(item) {
        return item[$scope.sortColumn];
    }

    $scope.firstLine = function(other) {
        if (!other) return "";
        if (other.niyaz) {
            return "Adults: " + other.adults + ", Kids: " + other.kids;
        } else {
            var sum = $scope.data.reduce(function(prev, elem) {
                if (!elem.filtered) {
                    if (!elem.filled) {
                        prev[0]++;
                        if (!elem.here) {
                            prev[1]++;
                        }
                    }
                }
                return prev;
            }, [0, 0]);
            return "Not here: " + sum[1] + ", not filled: " + sum[0] + ", total: " + $scope.data.length;
        }
    }
    $scope.secondLine = function(other) {
        if (!other) return "";
        if (other.niyaz) {
            return "Thaals: " + (other.adults / 8 + other.kids / 16).toFixed(1);
        } else {
            var sizes = $scope.data.reduce(function(prev, elem) {
                if (!elem.filtered) {
                    if (elem.filled == 0) {
                        if (prev[elem.size]) {
                            prev[elem.size]++;
                        } else {
                            prev[elem.size] = 1;
                        }
                    }
                }
                return prev;
            }, {});
            return "Sizes: " + JSON.stringify(sizes, null, 1).replace(/"/g, '')
                .replace('{', '').replace('}', '');
        }
    }

    $scope.reset = function(nodes) {
        $scope.msg = "";
        $scope.changed = true;
        angular.forEach($scope.data, function(item) {
            item.here = 0;
            item.filled = 0;
        });
    }
}]);
