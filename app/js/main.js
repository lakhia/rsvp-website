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
      .state('help', {
        url: "/help",
        templateUrl: 'ni.html',
        controller: 'adminController',
      })
      .state('admin', {
         url: "/admin",
         templateUrl: 'admin.html',
         controller: 'adminController',
      });
      // .state('stats', {
      //   url: "/stats",
      //   templateUrl: 'stats.html',
      //   controller: 'statsController',
      // }),
      // .state('settings', {
      //    url: "/settings",
      //    templateUrl: 'settings.html',
      //    controller: 'settingsControler',
      // });

      $urlRouterProvider.otherwise('/');
}])

/* Menu and login management */
app.controller("mainController", ["$scope", "$http", "$cookies", "$rootScope",
                                  "$state",
function($scope, $http, $cookies, $rootScope, $state) {

    $scope.small = 0;

    $scope.init = function() {
        // Logout param present?
        if ($rootScope.$stateParams.out) {
            logout();
            return;
        }
        $scope.cookies = $cookies;
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

    $scope.menuToggle = function() {
        $scope.small = !$scope.small;
    }

    function logout() {
        delete $rootScope.name;
        delete $cookies.adv;
        delete $cookies.token;
        delete $cookies.name;
        delete $cookies.thaali;
        $scope.message = "You have been logged out";
    }
}])

/* Add references to rootScope so that you can access them from any scope */
app.run(['$rootScope', '$state', '$stateParams',
    function ($rootScope, $state, $stateParams) {
        $rootScope.$state = $state;
        $rootScope.$stateParams = $stateParams;
    }
])
