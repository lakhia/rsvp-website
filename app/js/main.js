/* Menu and login management */
app.controller("loginController", ["$scope", "$cookies", "$rootScope",
                                  "$state", "$stateParams",
function($scope, $cookies, $rootScope, $state, $stateParams) {

    $scope.init = function() {
        if ($stateParams.out) {
            // Logout
            delete $cookies.adv;
            delete $cookies.token;
            delete $cookies.thaali;
            delete $cookies.email;
            localStorage.clear();
            $scope.msg = "You have been logged out";
        }
        $scope.cookies = $cookies;
        $scope.menuBig = localStorage.getItem('menuBig') == "1";
        $rootScope.init($scope, "login.php", handleResponse);
    }

    function handleResponse(response) {
        if (!response.msg && response.data) {
            localStorage.setItem('greet', response.data);
            $state.go("home", {offset:0});
        }
    }

    $scope.getClass = function(name) {
        if ($state.current.name == name) {
            return "active";
        }
        return "";
    }

    $scope.menuToggle = function() {
        $scope.menuBig = !$scope.menuBig;
        localStorage.setItem('menuBig', $scope.menuBig ? 1 : 0);
    }
}])

/* Add references to rootScope so that you can access them from any scope */
app.run(['$rootScope', '$http', '$state', '$stateParams',
    function ($rootScope, $http, $state, $stateParams) {

        // Init scope, setup common functions, fetch data
        $rootScope.init = function(scope, url, handleResponse) {
            scope.date = "";
            scope.changed = 0;
            scope.offset = parseInt($stateParams.offset) || 0;

            if (!localStorage.length) {
                $state.go("login");
            } else {
                // Fetch data using http GET
                scope.msg = "";
                $http({
                    url: url,
                    method: "GET",
                    timeout: 8000,
                    params: {offset: scope.offset}
                }).success(handleSuccess).error(error);
            }

            scope.onChange = function() {
                scope.msg = "";
                scope.changed = true;
            }
            scope.getDisplayDate = function(input) {
                var parts = input.split('-');
                if (parts.length <3) return input;
                var d = new Date(parts[0], parts[1]-1, parts[2]);
                input = input.replace(/^\d+-/, "");
                var output = ["Sun","Mon","Tue","Wed","Thr","Fri","Sat"][d.getDay()]
                return output + ", " + input;
            }

            // Network request handlers
            function handleSuccess(response) {
                scope.data = response.data;
                scope.date = response.date;
                scope.msg = response.msg;
                scope.o = response.other;
                scope.changed = false;
                if (handleResponse) {
                    handleResponse(response);
                }
            }
            function error(response, status) {
                scope.msg = "Request failed, try again";
            }
            // Warn on navigation change if changes are pending
            function leave(event) {
                if (scope.changed != 0 &&
                    !window.confirm("Unsaved changes, proceed anyway?")) {
                    event.preventDefault();
                }
            }
            scope.$on('$stateChangeStart', leave);
            window.onbeforeunload = leave;

            // Next button adds offset and fetches data
            scope.next = function(offset) {
                $state.go($state.current.name, {offset: scope.offset + offset});
            }
            // Submit
            scope.submit = function() {
                $http.post(url + "?offset=" + scope.offset,
                           scope.data, {timeout:8000})
                    .success(handleSuccess).error(error);
            }
        }
    }
])
