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
        controller: 'rsvpController',
      });

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


/* Printout controller */
app.controller("printController", ["$scope", "$http",
function($scope, $http) {

    $scope.init = function() {
        $http.get("printout.php").success(
            function(response)
            {
                $scope.printout = response.data;
            });
    }
}]);

/* RSVP controller */
app.controller("rsvpController", ["$scope", "$http", "$cookies", '$state',
                                  "$rootScope",
function($scope, $http, $cookies, $state, $rootScope) {
    $scope.changed = false;
    $scope.toggleCount = 0;
    $scope.details = {};
    $scope.fdate;

    $scope.init = function() {
        // Logged in?
        if (!$cookies.token && !$rootScope.name) {
            $state.go("login");
            return;
        }

        // Saturday is cutoff to show next week
        $scope.fdate = new Date();
        var day = $scope.fdate.getDay();
        if (day == 6) {
            day = -1;
        }
        addDaysToDate($scope.fdate, (1 - day));

        if ($cookies.name !== undefined) {
            $scope.name = $cookies.name.replace(/\+/g, " ");
        }

        fetchDetails();
    }

    function addDaysToDate(date, days) {
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    }

    function convertDate(date) {
        return date.getFullYear() + "-" + (date.getMonth()+1) + "-" + date.getDate();
    }

    function fetchDetails() {
        var tdate = new Date($scope.fdate.getTime());
        addDaysToDate(tdate, 7);

        $http({
            url: "details.php",
            method: "GET",
            params: {from: convertDate($scope.fdate), to: convertDate(tdate)}
        }).success(
            function(response)
            {
                $scope.details = response.data;
            });
    }

    $scope.getDisplayDate = function(input) {
        input = $scope.details[input].date;
        var parts = input.split('-');
        var d = new Date(parts[0], parts[1]-1, parts[2]);
        input = input.replace(/^\d+-/, "").replace(/0/g, "");
        var output = ["Sun","Mon","Tue","Wed","Thr","Fri","Sat"][d.getDay()]
        return output + ", " + input;
    }

    /*
      RSVP related methods
     */

    $scope.nextWeek = function() {
        addDaysToDate($scope.fdate, 7);
        fetchDetails();
    }

    $scope.prevWeek = function() {
        addDaysToDate($scope.fdate, -7);
        fetchDetails();
    }

    $scope.editRSVP = function(id) {
        // Clear message
        $scope.message = '';

        // Create a "No" default entry
        if (!$scope.details[id]) {
            $scope.details[id] = { rsvp:"No" };
        }

        // Toggle response to RSVP
        if ($scope.details[id].rsvp == "Yes") {
            $scope.details[id].rsvp = "No";
        } else {
            $scope.details[id].rsvp = "Yes";
        }

        // Keep track of toggle count to enable button
        if ($scope.details[id].toggled) {
            $scope.details[id].toggled = 0;
            $scope.toggleCount--;
        } else {
            $scope.details[id].toggled = 1;
            $scope.toggleCount++;
        }
        $scope.changed = ($scope.toggleCount > 0);
    }

    $scope.submit = function() {
        var toggles = {};
        for (var id in $scope.details) {
            if ($scope.details.hasOwnProperty(id)) {
                if ($scope.details[id].toggled !== undefined) {
                    toggles[$scope.details[id].date] = $scope.details[id].rsvp;
                }
            }
        }

        // TODO: Duplicated in get query
        var tdate = new Date($scope.fdate.getTime());
        addDaysToDate(tdate, 7);

        $http.post("details.php?from=" +
                   convertDate($scope.fdate) + "&to=" + convertDate(tdate),
                   toggles).success(
        function(response)
        {
            $scope.details = response.data;
            $scope.changed = false;
            $scope.toggleCount = 0;

        })
    }
}]);
