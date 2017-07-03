/* Printout controller */
app.controller("printController", ["$scope", '$rootScope',
function($scope, $rootScope) {
    $scope.date = "";
    $scope.url = "print.php";

    $scope.init = function() {
        $rootScope.init($scope, handleResponse);
    }

    function handleResponse(response) {
        $scope.data = response.data;
        $scope.date = response.date;
        $scope.msg = response.msg;
    }

    $scope.getClass = function(index) {
        if (index >= 1) {
            if (parseInt($scope.data[index]["thaali"]) !=
                parseInt($scope.data[index-1]["thaali"]) + 1) {
                return  "msg glyphicon glyphicon-scissors";
            }
        }
        return "";
    }

    $scope.getDisplayDate = function(date) {
        return $rootScope.getDisplayDate(date);
    }
}]);


// Auxillary function used to hide rows
function hideRow(child, delay) {
    node = child.parentNode.parentNode;
    node.classList.toggle("hideRow");
    hideDelay = function(n) {
        n.classList.toggle("gone");
    }
    window.setTimeout(hideDelay, delay, node);
}

function reset(nodes) {
    for (var i=0; i<nodes.length; i++) {
        if (nodes[i].checked) {
            nodes[i].checked = false;
            hideRow(nodes[i], 0);
        }
    }
}
