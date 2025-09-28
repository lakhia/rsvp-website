/* Printout controller */
app.controller("printController", ["$scope", '$rootScope',
function($scope, $rootScope) {

    $scope.init = function() {
        $rootScope.init($scope, "print.php", handleResponse);
        $scope.sortColumn = 'thaali';
        $scope.sortAscending = true;
        $scope.filterNames = {'thaali': "", 'area': "", 'rice': "", 'here': "", 'size': "", 'filled': ""};
    }

    $scope.setSortColumn = function(column) {
        if ($scope.sortColumn === column) {
            // If clicking the same column, toggle direction
            $scope.sortAscending = !$scope.sortAscending;
        } else {
            // New column, default to ascending
            $scope.sortColumn = column;
            $scope.sortAscending = true;
        }
        // Update sort indicator icon
        updateSortIndicator();
    }

    function updateSortIndicator() {
        var indicators = document.getElementsByClassName('sort-indicator');
        for (var i = 0; i < indicators.length; i++) {
            if (indicators[i].parentElement.classList.contains('sort-active')) {
                indicators[i].classList.remove('glyphicon-chevron-up', 'glyphicon-chevron-down');
                indicators[i].classList.add($scope.sortAscending ? 'glyphicon-chevron-up' : 'glyphicon-chevron-down');
            }
        }
        // Update column highlighting
        highlightSortedColumn();
    }

    function highlightSortedColumn() {
        // Remove previous highlighting
        var cells = document.getElementsByClassName('sorted-column');
        while(cells.length > 0) {
            cells[0].classList.remove('sorted-column');
        }
        
        // Add highlighting to the sorted column
        var columnIndex = -1;
        var headers = document.querySelectorAll('table thead th');
        for (var i = 0; i < headers.length; i++) {
            var input = headers[i].querySelector('input');
            if (input && input.getAttribute('ng-model').split('.')[1] === $scope.sortColumn) {
                columnIndex = i;
                headers[i].classList.add('sorted-column');
                break;
            }
        }
        
        if (columnIndex !== -1) {
            var rows = document.querySelectorAll('table tbody tr');
            rows.forEach(function(row) {
                row.cells[columnIndex].classList.add('sorted-column');
            });
        }
    }

    function handleResponse(response) {
        $scope.raw = response.data;
        $scope.data = [];
        initStats();  // Initialize stats when data is loaded
    }

    $scope.onCheckboxClick = function(item, field) {
        // Track old values for the changed field
        var oldHere = item.here;
        var oldFilled = item.filled;
        
        // If field is specified, it means we're toggling a specific field
        if (field === 'here') {
            item.here = !item.here;
        } else if (field === 'filled') {
            item.filled = !item.filled;
        }
        
        // Update the data array for saving
        $scope.data.push({
            "thaali": item.thaali, 
            "filled": item.filled ? 1 : 0,
            "here": item.here ? 1 : 0
        });
        
        // Only update stats if the item is visible in current filter
        if ($scope.filterFunc(item)) {
            if (field === 'here' && oldHere !== item.here) {
                pulseStatBadge('tiffin');
                pulseStatBadge('togo');
            } else if (field === 'filled' && oldFilled !== item.filled) {
                pulseStatBadge('done');
                pulseStatBadge('remains');
            }
        }
        
        // Recalculate all stats to ensure consistency with filters
        calculateFilteredStats();
        
        $scope.onChange();
    }
    
    // Validate stats and show error if invalid
    function validateStats() {
        var isValid = true;
        var errors = [];
        
        if ($scope.stats.tiffin + $scope.stats.togo !== $scope.stats.total) {
            isValid = false;
            errors.push('TIFFIN + TOGO must equal total RSVP');
            console.error('Stats mismatch: TIFFIN(' + $scope.stats.tiffin + ') + TOGO(' + 
                        $scope.stats.togo + ') ≠ RSVP(' + $scope.stats.total + ')');
        }
        
        if ($scope.stats.done + $scope.stats.remains !== $scope.stats.total) {
            isValid = false;
            errors.push('DONE + REMAINS must equal total RSVP');
            console.error('Stats mismatch: DONE(' + $scope.stats.done + ') + REMAINS(' + 
                        $scope.stats.remains + ') ≠ RSVP(' + $scope.stats.total + ')');
        }
        
        $scope.statsError = isValid ? null : errors.join('; ');
        return isValid;
    }

    // Calculate stats based on filtered view
    function calculateFilteredStats() {
        var stats = {
            total: 0,
            tiffin: 0,
            togo: 0,
            done: 0,
            remains: 0
        };
        
        // Apply all active filters and count
        $scope.raw.forEach(function(item) {
            // Reset filtered flag before checking
            item.filtered = false;
            
            // Only count if item passes all filters
            if ($scope.filterFunc(item)) {
                stats.total++;
                if (item.here) stats.tiffin++;
                else stats.togo++;
                
                if (item.filled) stats.done++;
                else stats.remains++;
            }
        });
        
        $scope.stats = stats;
        validateStats();
        
        // Also update size distribution
        calculateSizeStats();
    }
    
    // Initialize stats when data is loaded
    function initStats() {
        calculateFilteredStats();
    }
    
    // Add visual feedback for stat changes
    function pulseStatBadge(type) {
        var badge = document.querySelector('.stat-badge-' + type);
        if (badge) {
            badge.style.animation = 'none';
            badge.offsetHeight; // Trigger reflow
            badge.style.animation = 'pulse 0.3s ease-in-out';
        }
    }

    $scope.filterFunc = function(item) {
        if ($scope.filterNames.thaali) {
            if (item.thaali.toString().indexOf($scope.filterNames.thaali) < 0) {
                item.filtered = true;
                return false;
            }
        }
        if ($scope.filterNames.name) {
            if (item.name.indexOf($scope.filterNames.name) < 0) {
                item.filtered = true;
                return false;
            }
        }
        if ($scope.filterNames.size) {
            if (item.size != $scope.filterNames.size) {
                item.filtered = true;
                return false;
            }
        }
        if ($scope.filterNames.rice) {
            if (($scope.filterNames.rice.startsWith("N") && !item["bread+rice"]) ||
                ($scope.filterNames.rice.startsWith("Y") && item["bread+rice"])) {
              item.filtered = true;
              return false;
            }
        }
        if ($scope.filterNames.here) {
            if ((!item.here && $scope.filterNames.here == 'Y') ||
                (item.here && $scope.filterNames.here == 'N')) {
                item.filtered = true;
                return false;
            }
        }
        if ($scope.filterNames.filled) {
            if ((!item.filled && $scope.filterNames.filled == 'Y') ||
                (item.filled && $scope.filterNames.filled == 'N')) {
                item.filtered = true;
                return false;
            }
        }
        if ($scope.filterNames.area) {
            if (item.area != $scope.filterNames.area) {
                item.filtered = true;
                return false;
            }
        }
        item.filtered = false;
        return true;
    }

    $scope.sorterFunc = function(item) {
        var value = item[$scope.sortColumn];
        var sortValue;
        
        switch($scope.sortColumn) {
            case 'size':
                // Size order: XL > LG > MD > SM > XS
                var sizeOrder = { 'XL': 5, 'LG': 4, 'MD': 3, 'SM': 2, 'XS': 1 };
                sortValue = sizeOrder[value] || 0;
                break;
                
            case 'area':
                // Area/Group: alphabetical, case-insensitive but preserving
                sortValue = value.toLowerCase();
                break;
                
            case 'thaali':
                // Thaali: numeric, positive whole numbers
                sortValue = parseInt(value) || 0;
                break;
                
            case 'here':
            case 'filled':
            case 'bread+rice':
                // Boolean values
                sortValue = value ? 1 : 0;
                break;
                
            case 'name':
                // Names: alphanumeric, case-insensitive but preserving
                sortValue = value.toLowerCase();
                break;
                
            default:
                sortValue = value;
        }
        
        // Handle ascending/descending for all types
        if (typeof sortValue === 'string') {
            // For strings, we need to handle reverse sort differently
            return $scope.sortAscending ? sortValue : 
                   String.fromCharCode(65535) // High Unicode value
                   .repeat(sortValue.length) // Same length as original
                   .split('')
                   .map((c, i) => String.fromCharCode(65535 - sortValue.charCodeAt(i)))
                   .join('');
        } else {
            // For numeric values and booleans
            return $scope.sortAscending ? sortValue : -sortValue;
        }
    }

    $scope.firstLine = function(other) {
        if (!other) return "";
        if (other.niyaz) {
            return "Adults: " + other.adults + ", Kids: " + other.kids;
        } else {
            var sum = $scope.raw.reduce(function(prev, elem) {
                if (!elem.filtered) {
                    if (!elem.filled) {
                        prev[0]++;
                    }
                    if (!elem.here) {
                        prev[1]++;
                    }
                    prev[2]++;
                }
                return prev;
            }, [0, 0, 0]);
            return "Not here: " + sum[1] + ", not filled: " + sum[0] + ", total: " + sum[2];
        }
    }

    // Calculate size distribution based on filtered view
    function calculateSizeStats() {
        var sizeStats = {
            'XS': 0, 'SM': 0, 'MD': 0, 'LG': 0, 'XL': 0,
            total: 0
        };
        
        $scope.raw.forEach(function(item) {
            // Only count if item passes all filters
            if ($scope.filterFunc(item)) {
                sizeStats[item.size]++;
                sizeStats.total++;
            }
        });
        
        $scope.sizeStats = sizeStats;
    }

    $scope.secondLine = function(other) {
        if (!other) return "";
        if (other.niyaz) {
            return "Thaals: " + (other.adults / 8 + other.kids / 10).toFixed(1);
        } else {
            // Use the pre-calculated size stats
            var sizes = $scope.sizeStats || { 'XS': 0, 'SM': 0, 'MD': 0, 'LG': 0, 'XL': 0 };
            return "XS: " + sizes['XS']
               + ", SM: " + sizes['SM']
               + ", MD: " + sizes['MD']
               + ", LG: " + sizes['LG']
               + ", XL: " + sizes['XL'];
        }
    }

    $scope.reset = function(nodes) {
        $scope.msg = "";
        $scope.changed = false;
        $scope.data = [];
        
        // Reset to original values from DB
        handleResponse({ data: $scope.raw });
    }

    $scope.onFilterChange = function() {
        $scope.filterNames.area = $scope.filterNames.area.toUpperCase();
        $scope.filterNames.size = $scope.filterNames.size.toUpperCase();
        $scope.filterNames.here = $scope.filterNames.here.toUpperCase();
        $scope.filterNames.filled = $scope.filterNames.filled.toUpperCase();
        $scope.filterNames.rice = $scope.filterNames.rice.toUpperCase();
        
        // Recalculate stats based on new filter
        calculateFilteredStats();
    }

    $scope.toggleAllHere = function() {
        var filtered = $scope.raw.filter($scope.filterFunc);
        var allHere = filtered.every(function(item) { return item.here; });
        var newValue = !allHere;
        
        // Calculate count changes
        var changeCount = 0;
        filtered.forEach(function(item) {
            if (item.here !== newValue) changeCount++;
        });
        
        // Update items
        filtered.forEach(function(item) {
            if (item.here !== newValue) {
                item.here = newValue;
                $scope.data.push({
                    "thaali": item.thaali,
                    "filled": item.filled ? 1 : 0,
                    "here": newValue ? 1 : 0
                });
            }
        });
        
        if (changeCount > 0) {
            pulseStatBadge('tiffin');
            pulseStatBadge('togo');
            // Recalculate stats to reflect filtered view
            calculateFilteredStats();
        }
        
        $scope.onChange();
    }

    $scope.toggleAllFilled = function() {
        var filtered = $scope.raw.filter($scope.filterFunc);
        var allFilled = filtered.every(function(item) { return item.filled; });
        var newValue = !allFilled;
        
        // Calculate count changes
        var changeCount = 0;
        filtered.forEach(function(item) {
            if (item.filled !== newValue) changeCount++;
        });
        
        // Update items
        filtered.forEach(function(item) {
            if (item.filled !== newValue) {
                item.filled = newValue;
                $scope.data.push({
                    "thaali": item.thaali,
                    "filled": newValue ? 1 : 0,
                    "here": item.here ? 1 : 0
                });
            }
        });
        
        if (changeCount > 0) {
            pulseStatBadge('done');
            pulseStatBadge('remains');
            // Recalculate stats to reflect filtered view
            calculateFilteredStats();
        }
        
        $scope.onChange();
    }
}]);