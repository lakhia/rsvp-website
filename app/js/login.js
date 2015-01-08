/* Login controller */
app.controller("loginController", ["$scope", "$http", "$cookies",
                                   '$state', '$rootScope',
function($scope, $http, $cookies, $state, $rootScope) {

    $scope.init = function() {
        if ($rootScope.$stateParams.out) {
            logout();
        }
        $scope.email = $cookies.email;
    }

    $scope.login = function() {
        var request = $http({
            url: "login.php",
            method: "GET",
            params: {thaali: $scope.thaali, email: $scope.email}
        });
        request.success(
            function(response)
            {
                // Display error or redirect to home
                if (response.message) {
                    $scope.message = response.message;
                } else {
                    $scope.name = response.data;
                    $rootScope.name = response.data;
                    $state.go("home");
                }
            });
    }
    function logout() {
        $scope.message = "You have been logged out";
        delete $rootScope.name;
        delete $cookies.admin;
        delete $cookies.token;
        delete $cookies.name;
        delete $cookies.thaali;
    }
}]);
