/* RSVP controller */
app.controller("rsvpController", ["$scope", "$rootScope",
function($scope, $rootScope) {
    $scope.url = "rsvp.php";

    $scope.init = function() {
        $scope.greet = localStorage.getItem('greet');
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
        dateData.rsvp = 1;
        localStorage.setItem('adults', raw.adults);
        localStorage.setItem('kids', raw.kids);
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
                raw.adults = null;
                raw.kids = null;
            }
        }
        if (dateData.rsvp) {
            delete dateData.rsvp;
        } else {
            dateData.rsvp = raw.rsvp;
            dateData.adults = raw.adults;
            dateData.kids = raw.kids;
        }
        onPostChange(id);
    }
}]);
