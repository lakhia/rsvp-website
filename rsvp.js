var app = angular.module("rsvp", ['ngRoute', 'ngCookies']);
app.config(['$routeProvider', '$locationProvider',
  function($routeProvider, $locationProvider) {
    $routeProvider
      .when('/', {
        templateUrl: 'rsvp.html',
        controller: 'rsvpController',
        resolve: {
          isLogged: app.isLoggedIn
        }
      })
      .when('/login', {
        templateUrl: 'login.html',
        controller: 'loginController',
      })
      .when('/login/:out', {
        templateUrl: 'login.html',
        controller: 'loginController',
      })
      .when('/print', {
        templateUrl: 'print.html',
        controller: 'printController',
        resolve: {
          isLogged: app.isLoggedIn
        }
      })
      .when('/ni', {
        templateUrl: 'ni.html',
        controller: 'rsvpController',
      })
      .otherwise({
        redirectTo: '/ni'
      });
}])


/* Menu tab management */
var appCtrl = app.controller("menuController", ["$scope", "$http", "$cookies",
function($scope, $http, $cookies) {

    $scope.selectTab = function(num) {
        if ($cookies.token) {
            $scope.selected = num;
        }
    }
    $scope.classTab = function(num) {
        if (!$cookies.token) {
            return "disbld disabled";
        }
        if ($scope.selected == num) {
            return "active";
        }
        return "";
    }
}]);

appCtrl.isLoggedIn = function ($q, $timeout, $cookies, $location) {
    var defer = $q.defer();
    $timeout(function () {
        if ($cookies.token) {
            defer.resolve("loadData");
        } else {
            defer.reject("not logged in");
            $location.path("/login");
            $location.replace();
        }
    }, 1000);
    return defer.promise;
};

app.controller("loginController", ["$scope", "$http", "$cookies",
                                   "$routeParams", "$location",
function($scope, $http, $cookies, $routeParams, $location) {

    $scope.init = function() {
        if ($routeParams) {
            logout();
        } else {
            $scope.message = "";
        }
        $scope.email = $cookies.email;
        $scope.selected = 10;
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
                $scope.details = response;

                if (!$scope.details.message) {
                    $scope.name = response;
                    $location.path("/");
                }
                $scope.login = 0;
            });
    }
    function logout() {
        $scope.message = "You have been logged out";
        $scope.selected = 10;
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
                $scope.printout = response;
            });
    }
}]);

/* RSVP controller */
app.controller("rsvpController", ["$scope", "$http", "$cookies", 
function($scope, $http, $cookies) {
    $scope.changed = false;
    $scope.toggleCount = 0;
    $scope.details = {};
    $scope.fdate;
    $scope.selected = 10;        // TODO: Fix default

    $scope.init = function() {
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

        postLogin($cookies.token);
    }

   function postLogin(loggedIn) {
        if (loggedIn) {
            $scope.selected = 0;
            fetchDetails();
        } else {
            $scope.selected = 10;
        }
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
                $scope.details = response;
            });
    }

    $scope.getDisplayDate = function(input) {
        input = $scope.details.data[input].date;
        var parts = input.split('-');
        var d = new Date(parts[0], parts[1]-1, parts[2]);
        input = input.replace(new Date().getFullYear() + "-", "");
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

    $scope.disabledRSVP = function(id) {
        return !$scope.details.data[id].enabled;
    }

    $scope.getDetails = function(id) {
        return $scope.details.data[id].details;
    }

    $scope.getRsvp = function(id) {
        if ($scope.details.data[id]) {
            return $scope.details.data[id].rsvp;
        }
        return "No";
    }

    $scope.editRSVP = function(id) {
        // Clear message
        $scope.details.message = '\u00A0';

        // Create a "No" default entry
        if (!$scope.details.data[id]) {
            $scope.details.data[id] = { rsvp:"No" };
        }

        // Toggle response to RSVP
        if ($scope.details.data[id].rsvp == "Yes") {
            $scope.details.data[id].rsvp = "No";
        } else {
            $scope.details.data[id].rsvp = "Yes";
        }

        // Keep track of toggle count to enable button
        if ($scope.details.data[id].toggled) {
            $scope.details.data[id].toggled = 0;
            $scope.toggleCount--;
        } else {
            $scope.details.data[id].toggled = 1;
            $scope.toggleCount++;
        }
        $scope.changed = ($scope.toggleCount > 0);
    }

    $scope.submit = function() {
        var toggles = {};
        for (var id in $scope.details.data) {
            if ($scope.details.data.hasOwnProperty(id)) {
                if ($scope.details.data[id].toggled !== undefined) {
                    toggles[$scope.details.data[id].date] = $scope.details.data[id].rsvp;
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
            $scope.details = response;
            $scope.changed = false;
            $scope.toggleCount = 0;

        })
    }
}]);
