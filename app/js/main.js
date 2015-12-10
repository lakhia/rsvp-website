var app = angular.module("rsvp", ['ui.router', 'ngCookies',
                                  'angular-loading-bar']);

/* Route configuration */
app.config(['$stateProvider','$urlRouterProvider',
  function($stateProvider, $urlRouterProvider) {
    $stateProvider
      .state("home", {
        url: "/{offset}",
        templateUrl: 'rsvp.html',
        controller: 'rsvpController',
      })
      .state('login', {
        url: "/login/{out}",
        templateUrl: 'login.html',
        controller: 'mainController',
      })
      .state('print', {
        url: "/print/{offset}",
        templateUrl: 'print.html',
        controller: 'printController',
      })
      .state('event', {
        url: "/event/{offset}",
        templateUrl: 'event.html',
        controller: 'eventController',
      })
      .state('family', {
         url: "/family/{offset}",
         templateUrl: 'family.html',
         controller: 'familyController',
      });

      $urlRouterProvider.otherwise('/');
}])

/* Menu and login management */
app.controller("mainController", ["$scope", "$http", "$cookies", "$rootScope",
                                  "$state",
function($scope, $http, $cookies, $rootScope, $state) {

    $scope.init = function() {
        // Logout param present?
        if ($rootScope.stateParams.out) {
            logout();
            return;
        }
        $scope.cookies = $cookies;
        $scope.big = $cookies.menuBig;
    }

    $scope.getClass = function(name) {
        if ($state.current.name == name) {
            return "active";
        }
        return "";
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
                if (response.msg) {
                    $scope.msg = response.msg;
                } else {
                    $rootScope.name = response.data;
                    $rootScope.thaali = $scope.thaali;
                    $state.go("home", {offset:0});
                }
            });
    }

    $scope.menuToggle = function() {
        $scope.big = !$scope.big;
        $cookies.menuBig = $scope.big ? 1 : "";
    }

    function logout() {
        delete $rootScope.name;
        delete $cookies.adv;
        delete $cookies.token;
        delete $cookies.name;
        delete $cookies.thaali;
        delete $cookies.menuBig;
        delete $cookies.email;
        $scope.thaali = "";
        $scope.msg = "You have been logged out";
    }
}])

/* Add references to rootScope so that you can access them from any scope */
app.run(['$rootScope', '$cookies', '$http', '$state', '$stateParams',
    function ($rootScope, $cookies, $http, $state, $stateParams) {
        $rootScope.stateParams = $stateParams;

        // Init scope, setup common functions, fetch data
        $rootScope.init = function(scope, handleResponse) {
            if (!$cookies.token && !$rootScope.name) {
                $state.go("login");
                return;
            }
            scope.changed = 0;
            scope.offset = parseInt($stateParams.offset) || 0;

            // Fetch data using http GET
            function fetchData() {
                $http({
                    url: scope.url,
                    method: "GET",
                    timeout: 8000,
                    params: {offset: scope.offset}
                }).success(handleResponse).error(error);
            }
            // Network request failed
            function error(response, status) {
                scope.msg = "Request failed (" + status + "), try again";
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
                $http.post(scope.url + "?offset=" + scope.offset,
                           scope.data, {timeout:8000})
                    .success(handleResponse).error(error);
            }

            fetchData();
        }
        $rootScope.getDisplayDate = function(input) {
            var parts = input.split('-');
            var d = new Date(parts[0], parts[1]-1, parts[2]);
            input = input.replace(/^\d+-/, "");
            var output = ["Sun","Mon","Tue","Wed","Thr","Fri","Sat"][d.getDay()]
            return output + ", " + input;
        }
        $rootScope.getName = function() {
            if ($cookies.name !== undefined) {
                return $cookies.name.replace(/\+/g, " ") +
                    ", #" + $cookies.thaali;
            } else {
                return $rootScope.name + ", #" + $rootScope.thaali;
            }
        }
    }
])
