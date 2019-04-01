/* Family controller */
app.controller("familyController", ["$scope", "$rootScope",
function($scope, $rootScope) {

    $scope.init = function() {
        $rootScope.init($scope, "family.php", null);
    }
}]);
