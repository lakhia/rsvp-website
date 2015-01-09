var app = angular.module("rsvp", ['ui.router', 'ngCookies']);

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
        controller: 'loginController',
      })
      .state('login.out', {
        params: ['out'],
        templateUrl: 'login.html',
        controller: 'loginController',
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

/* Menu tab management */
app.controller("menuController", ["$scope", "$http", "$cookies",
function($scope, $http, $cookies) {
    $scope.menuClass = function() {
        if (!$cookies.token) {
            return "disbld";
        }
    }
}])

/* Add references to rootScope so that you can access them from any scope */
app.run(['$rootScope', '$state', '$stateParams',
    function ($rootScope, $state, $stateParams) {
        $rootScope.$state = $state;
        $rootScope.$stateParams = $stateParams;
    }
])
