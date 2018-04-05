/* RSVP controller */
app.controller("rsvpController", ["$scope", "$rootScope",
function($scope, $rootScope) {
    $scope.url = "rsvp.php";

    $scope.init = function() {
        $scope.greet = $rootScope.getName();
        $rootScope.init($scope, handleResponse);
    }

    function handleResponse(response) {
        $scope.msg = response.msg;
        $scope.raw = response.data;
        $scope.data = {};
        $scope.changed = 0;
    }

    $scope.getDisplayDate = function(input) {
        return $rootScope.getDisplayDate($scope.raw[input].date);
    }

    function onChange(id) {
        // Clear message
        $scope.msg = '';

        // Create empty dict if needed
        var date = $scope.raw[id].date;
        if (! $scope.data[date]) {
            $scope.data[date] = {};
        }
        return $scope.data[date];
    }

    $scope.onRiceChange = function(id) {
        var dateData = onChange(id);
        if (dateData.lessRice) {
            delete dateData.lessRice;
            if (!dateData.rsvp) {
                delete dateData;
            }
        } else {
            dateData.lessRice = $scope.raw[id].lessRice;
        }
        $scope.changed = Object.keys($scope.data).length;
    }

    $scope.onRSVPChange = function(id) {
        var dateData = onChange(id);
        if ($scope.raw[id].rsvp == "Yes") {
            $scope.raw[id].rsvp = "No";
        } else {
            $scope.raw[id].rsvp = "Yes";
        }
        if (dateData.rsvp) {
            delete dateData.rsvp;
            if (!dateData.lessRice) {
                delete dateData;
            }
        } else {
            dateData.rsvp = $scope.raw[id].rsvp;
        }
        $scope.changed = Object.keys($scope.data).length;
    }
}]);
