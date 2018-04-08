/* Shopping controller */
app.controller("shopController", ["$scope", '$rootScope',
function($scope, $rootScope) {

    $scope.init = function() {
        $rootScope.init($scope, "shop.php", null);
    }
}]);
