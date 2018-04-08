/* Measure controller */
app.controller("measureController", ["$scope", '$rootScope', '$http',
function($scope, $rootScope, $http) {

    $scope.init = function() {
        $http({
            url: "ingred.php",
            method: "GET",
            timeout: 8000
        }).success(handleIngredients).error(error);
        $rootScope.init($scope, "measure.php", null);
    }
    $scope.suggestion = {};
    $scope.querySuggest =function(ingred, $event) {
      // confirm suggestion with right arrow or enter
      if (ingred.sug && ($event.keyCode == 39 || $event.keyCode == 13)) {
        ingred.name = ingred.sug;
        ingred.sug = undefined;
        return;
      }
      if (ingred.name) {
        ingred.sug = '';
        for (var i = 0; i < $scope.ingred.length; i++) {
          if ($scope.ingred[i].name.indexOf(ingred.name) === 0) {
            ingred.sug = $scope.ingred[i].name;
            ingred.id = $scope.ingred[i].id;
            break;
          }
        }
      }
    }

    function error(error) {
        scope.msg = "Failed to load list of ingredients, please reload";
    }
    function handleIngredients(response) {
        $scope.ingred = response.data;
    }
}]);
