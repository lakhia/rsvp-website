/* Event controller */
app.controller("eventController", ["$scope", "$rootScope",
function($scope, $rootScope) {

    $scope.init = function() {
        $rootScope.init($scope, "event.php", null);
    }
}]);
