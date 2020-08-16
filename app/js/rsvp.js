/* RSVP controller */
app.controller("rsvpController", ["$scope", "$rootScope",
function($scope, $rootScope) {
    $scope.sizes = ["XS", "S", "M", "L", "XL"];

    $scope.init = function() {
        $scope.greet = localStorage.getItem('greet');
        $rootScope.init($scope, "rsvp.php", handleResponse);
    }

    $scope.getRawDate = function(input) {
        return $scope.getDisplayDate($scope.raw[input].date);
    }

    function handleResponse(response) {
        $scope.raw = response.data;
        $scope.data = {};
    }

    function onPreChange(id) {
        // Clear message
        $scope.msg = '';

        // Create empty dict if needed
        var date = $scope.raw[id].date;
        if (! $scope.data[date]) {
            $scope.data[date] = {};
        }
        return $scope.data[date];
    }

    function onPostChange(id) {
        var date = $scope.raw[id].date;
        if (Object.keys($scope.data[date]) == 0) {
            delete $scope.data[date];
        }
        $scope.changed = Object.keys($scope.data).length;
    }

    $scope.onRiceChange = function(id) {
        var dateData = onPreChange(id);
        if (dateData.lessRice) {
            delete dateData.lessRice;
        } else {
            dateData.lessRice = $scope.raw[id].lessRice;
        }
        onPostChange(id);
    }

    $scope.onCountChange = function(id, key) {
        var dateData = onPreChange(id);
        var raw = $scope.raw[id];
        dateData.adults = raw.adults;
        dateData.kids = raw.kids;
        localStorage.setItem('adults', raw.adults);
        localStorage.setItem('kids', raw.kids);
        onPostChange(id);
    }

    $scope.onSizeChange = function(id) {
        var dateData = onPreChange(id);
        var raw = $scope.raw[id];
        dateData.size = raw.size;
        onPostChange(id);
    }

    $scope.rsvpLabel = function(rsvp) {
        if (rsvp) {
            return "Yes";
        } else {
            return "No"
        }
    }

    $scope.onRSVPChange = function(id) {
        var raw = $scope.raw[id];
        var dateData = onPreChange(id);
        raw.rsvp = !raw.rsvp;
        if (raw.niyaz) {
            if (raw.rsvp) {
                raw.adults = localStorage.getItem('adults') || 0;
                raw.kids = localStorage.getItem('kids') || 0;
            } else {
                dateData.kids = dateData.adults = raw.kids = raw.adults = null;
            }
        }
        if (dateData.rsvp) {
            delete dateData.rsvp;
        } else {
            dateData.rsvp = raw.rsvp;
            if (raw.niyaz) {
                dateData.adults = raw.adults;
                dateData.kids = raw.kids;
            }
        }
        onPostChange(id);
    }
}]);
