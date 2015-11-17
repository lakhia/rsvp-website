var app = angular.module("rsvp", ['ui.router', 'ngCookies',
                                  'angular-loading-bar']);

/* Route configuration */
app.config(['$stateProvider','$urlRouterProvider',
  function($stateProvider, $urlRouterProvider) {
    $stateProvider
      .state("home", {
        url: "/",
        templateUrl: 'rsvp.html',
        controller: 'rsvpController',
      })
      .state('login', {
        url: "/login",
        templateUrl: 'login.html',
        controller: 'mainController',
      })
      .state('login.out', {
        params: ['out'],
        templateUrl: 'login.html',
        controller: 'mainController',
      })
      .state('print', {
        url: "/print",
        templateUrl: 'print.html',
        controller: 'printController',
      })
      .state('event', {
        url: "/event",
        templateUrl: 'event.html',
        controller: 'eventController',
      })
      .state('family', {
         url: "/family",
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
                    $rootScope.name = response.data;
                    $rootScope.thaali = $scope.thaali;
                    $state.go("home");
                }
            });
    }

    $scope.menuToggle = function() {
        $scope.big = !$scope.big;
        $scope.cookies.menuBig = $scope.big ? 1 : "";
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
        $scope.message = "You have been logged out";
    }
}])

/* Add references to rootScope so that you can access them from any scope */
app.run(['$rootScope', '$cookies', '$http', '$state', '$stateParams',
    function ($rootScope, $cookies, $http, $state, $stateParams) {
        $rootScope.stateParams = $stateParams;

        // Add helper methods here, can be used by any controller
        $rootScope.isLoggedOut = function() {
            if (!$cookies.token && !$rootScope.name) {
                $state.go("login");
                return 1;
            }
            return 0;
        }
        $rootScope.addDaysToDate = function(date, days) {
            return date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        }
        $rootScope.getDisplayDate = function(input) {
            var parts = input.split('-');
            var d = new Date(parts[0], parts[1]-1, parts[2]);
            input = input.replace(/^\d+-/, "");
            var output = ["Sun","Mon","Tue","Wed","Thr","Fri","Sat"][d.getDay()]
            return output + ", " + input;
        }
        $rootScope.fetchData = function(fromDate, toDate, url, handleResponse) {
            var p = {from: $rootScope.convertDate(fromDate)};
            if (toDate) {
                p.to = $rootScope.convertDate(toDate);
            }
            $http({
                url: url,
                method: "GET",
                params: p
            }).success(handleResponse);
        }
        $rootScope.getName = function() {
            if ($cookies.name !== undefined) {
                return $cookies.name.replace(/\+/g, " ") +
                    ", #" + $cookies.thaali;
            } else {
                return $rootScope.name + ", #" + $rootScope.thaali;
            }
        }
        $rootScope.convertDate = function(date) {
            return date.getFullYear() + "-" + (date.getMonth()+1) + "-" + date.getDate();
        }
    }
])
