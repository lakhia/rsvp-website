/* Printout controller */
app.controller("printController", ["$scope", '$rootScope',
function($scope, $rootScope) {
    warnedDate = "";

    $scope.init = function() {
        $rootScope.init($scope, "print.php", handleResponse);
        $scope.sortColumn = 'thaali';
        $scope.filterNames = {'area': "", 'rice': "", 'here': "", 'size': "", 'filled': ""};
    }

    function handleResponse(response) {
        $scope.raw = response.data;
        $scope.data = [];
    }

    $scope.onCheckboxClick = function(item) {
        if (!warnedDate) {
            warnedDate = new Date().toLocaleDateString("en-CA")
            if ($scope.date != warnedDate) {
                alert("Warning: Are you sure you wish to modify this date: " + $scope.date);
            }
        }
        $scope.data.push({"thaali": item.thaali, "filled": item.filled ? 1 : 0, 
            "here": item.here ? 1 : 0});
        $scope.onChange();
    }

    $scope.filterFunc = function(item) {
        if ($scope.filterNames.name) {
            if (item.name.indexOf($scope.filterNames.name) < 0) {
                item.filtered = true;
                return false;
            }
        }
        if ($scope.filterNames.size) {
            if (item.size != $scope.filterNames.size) {
                item.filtered = true;
                return false;
            }
        }
        if ($scope.filterNames.rice) {
            if (($scope.filterNames.rice.startsWith("N") && !item["bread+rice"]) ||
                ($scope.filterNames.rice.startsWith("Y") && item["bread+rice"])) {
              item.filtered = true;
              return false;
            }
        }
        if ($scope.filterNames.here) {
            if ((!item.here && $scope.filterNames.here == 'Y') ||
                (item.here && $scope.filterNames.here == 'N')) {
                item.filtered = true;
                return false;
            }
        }
        if ($scope.filterNames.filled) {
            if ((!item.filled && $scope.filterNames.filled == 'Y') ||
                (item.filled && $scope.filterNames.filled == 'N')) {
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
            var sum = $scope.raw.reduce(function(prev, elem) {
                if (!elem.filtered) {
                    if (!elem.filled) {
                        prev[0]++;
                    }
                    if (!elem.here) {
                        prev[1]++;
                    }
                    prev[2]++;
                }
                return prev;
            }, [0, 0, 0]);
            return "Not here: " + sum[1] + ", not filled: " + sum[0] + ", total: " + sum[2];
        }
    }
    $scope.secondLine = function(other) {
        if (!other) return "";
        if (other.niyaz) {
            return "Thaals: " + (other.adults / 8 + other.kids / 10).toFixed(1);
        } else {
            var sizes = $scope.raw.reduce(function(prev, elem) {
                if (!elem.filtered) {
                    if (prev[elem.size]) {
                        prev[elem.size]++;
                    } else {
                        prev[elem.size] = 1;
                    }
                }
                return prev;
            }, {});
            return "XS: " + (sizes['XS'] || 0)
               + ", S: " + (sizes['S'] || 0)
               + ", M: " + (sizes['M'] || 0)
               + ", L: " + (sizes['L'] || 0)
               + ", XL: " + (sizes['XL'] || 0);
        }
    }

    $scope.reset = function(nodes) {
        $scope.msg = "";
        $scope.changed = true;
        $scope.data = [];
        angular.forEach($scope.raw, function(item) {
            item.here = 0;
            item.filled = 0;
            $scope.onCheckboxClick(item);
        });
    }

    $scope.onFilterChange = function() {
        $scope.filterNames.area = $scope.filterNames.area.toUpperCase();
        $scope.filterNames.size = $scope.filterNames.size.toUpperCase();
        $scope.filterNames.here = $scope.filterNames.here.toUpperCase();
        $scope.filterNames.filled = $scope.filterNames.filled.toUpperCase();
        $scope.filterNames.rice = $scope.filterNames.rice.toUpperCase();
    }

    $scope.generateLabels = function () {
      window.location.href = "generate_labels.php?date=" + encodeURIComponent($scope.date);
    };
}]);
