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
    
    $scope.onChange = function(id, field) {
        // Clear message
        $scope.msg = '';

        // Toggle response to RSVP
        var date = $scope.raw[id].date;
        
        if (field == "RSVP" ){
            if ($scope.raw[id].rsvp == "Yes") {
                $scope.raw[id].rsvp = "No";
            } else {
                $scope.raw[id].rsvp = "Yes";
            }

            $scope.changed = true;
            $scope.data[date] = [ $scope.raw[id].rsvp,
                    $scope.raw[id].lessRice ];
                  
        }
        else {
            $scope.changed = true;
            $scope.data[date] = [ $scope.raw[id].rsvp,
                    $scope.raw[id].lessRice ];

        }
    }
}]);
