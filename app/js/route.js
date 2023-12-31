var app = angular.module("rsvp", ['ui.router', 'ngCookies',
                                  'angular-loading-bar']);

/* Route configuration */
app.config(['$stateProvider','$urlRouterProvider',
  function($stateProvider, $urlRouterProvider) {
    $stateProvider
      .state('login', {
        url: "/login/{out}",
        templateUrl: 'login.html',
        controller: 'loginController',
      })
      .state('print', {
        url: "/print/{offset}",
        templateUrl: 'print.html',
        controller: 'printController',
      })
      .state('plan', {
        url: "/plan/",
        templateUrl: 'plan.html',
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
      })
      .state('shop', {
        url: "/shop/{offset}",
        templateUrl: 'shop.html',
        controller: 'shopController',
      })
      .state('measure', {
        url: "/measure/{offset}",
        templateUrl: 'measure.html',
        controller: 'measureController',
      })
      .state("h", {
        url: "/{offset}",
        templateUrl: 'rsvp.html',
        controller: 'rsvpController',
      })
      .state("home", {
        url: "/{offset}/{date}",
        templateUrl: 'rsvp.html',
        controller: 'rsvpController',
      });

      $urlRouterProvider.otherwise('/');
}])
