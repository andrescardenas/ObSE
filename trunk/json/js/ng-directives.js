var directives = angular.module('soyexperto.directives', []);

directives.directive('chart', function () {
    return {
        restrict: 'A',
        scope: { chartData: "=value"},

        link: function (scope, element, attrs) {
            var chartsDefaults = {
                chart: {
                    renderTo: element[0],
                    type: attrs.type || null,
                    height: attrs.height || null,
                    width: attrs.width || null,
                    backgroundColor: 'rgba(255, 255, 255, 0)'
                }
            }

            scope.$watch(function() { return scope.chartData; }, function(value) {
                if(!value) return;
                var deepCopy = true;
                var newSettings = {};
                $.extend(deepCopy, newSettings, chartsDefaults, scope.chartData);
                var chart = new Highcharts.Chart(newSettings, function(chart) {
                    $.each(chart.series, function(i, serie) {
                        $.each(serie.data, function(j, data) {
                            if (data.dataLabel && data.yBottom - data.plotY < 20) data.dataLabel.destroy();
                        });
                    });
                });
            });
        }
    };
});

directives.directive('html', function($sce){
    return {
        scope: { html: '=',},
        template: "<div ng-bind-html='trustedHtml'></div>",
        link: function($scope, iElm, iAttrs, controller) {
            $scope.updateView = function() {
                $scope.trustedHtml = $sce.trustAsHtml($scope.html);
            }

            $scope.$watch('html', function(newVal, oldVal) {
                $scope.updateView(newVal);
            });
        }
    };
});

directives.directive('mrFile', function() {
    return {
        restrict: 'A',

        link: function(scope, element, attrs) {
            element.children('#A').css('display','none');

            element.children('#B').on('click', function(){
                element.children('#B').siblings('#A').click();
            });

            element.children('#C').on('click', function(){
                element.children('#C').siblings('#A').val('');
            });

            element.children('#D').on('click', function(){
                element.children('#D').siblings('#A').click();
            });
        }
    };
});

directives.directive('ngBindHtmlUnsafe', ['$sce', function($sce){
    return {
        scope: {
            ngBindHtmlUnsafe: '=',
        },
        template: "<div ng-bind-html='trustedHtml'></div>",
        link: function($scope, iElm, iAttrs, controller) {
            $scope.updateView = function() {
                $scope.trustedHtml = $sce.trustAsHtml($scope.ngBindHtmlUnsafe);
            }

            $scope.$watch('ngBindHtmlUnsafe', function(newVal, oldVal) {
                $scope.updateView(newVal);
            });
        }
    };
}]);

//http://vitalets.github.io/checklist-model/
directives.directive('checklistModel', ['$parse', '$compile', function($parse, $compile) {
  // contains
  function contains(arr, item, comparator) {
    if (angular.isArray(arr)) {
      for (var i = arr.length; i--;) {
        if (comparator(arr[i], item)) {
          return true;
        }
      }
    }
    return false;
  }

  // add
  function add(arr, item, comparator) {
    arr = angular.isArray(arr) ? arr : [];
      if(!contains(arr, item, comparator)) {
          arr.push(item);
      }
    return arr;
  }  

  // remove
  function remove(arr, item, comparator) {
    if (angular.isArray(arr)) {
      for (var i = arr.length; i--;) {
        if (comparator(arr[i], item)) {
          arr.splice(i, 1);
          break;
        }
      }
    }
    return arr;
  }

  // http://stackoverflow.com/a/19228302/1458162
  function postLinkFn(scope, elem, attrs) {
    // compile with `ng-model` pointing to `checked`
    $compile(elem)(scope);

    // getter / setter for original model
    var getter = $parse(attrs.checklistModel);
    var setter = getter.assign;
    var checklistChange = $parse(attrs.checklistChange);

    // value added to list
    var value = $parse(attrs.checklistValue)(scope.$parent);


  var comparator = angular.equals;

  if (attrs.hasOwnProperty('checklistComparator')){
    comparator = $parse(attrs.checklistComparator)(scope.$parent);
  }

    // watch UI checked change
    scope.$watch('checked', function(newValue, oldValue) {
      if (newValue === oldValue) { 
        return;
      } 
      var current = getter(scope.$parent);
      if (newValue === true) {
        setter(scope.$parent, add(current, value, comparator));
      } else {
        setter(scope.$parent, remove(current, value, comparator));
      }

      if (checklistChange) {
        checklistChange(scope);
      }
    });
    
    // declare one function to be used for both $watch functions
    function setChecked(newArr, oldArr) {
        scope.checked = contains(newArr, value, comparator);
    }

    // watch original model change
    // use the faster $watchCollection method if it's available
    if (angular.isFunction(scope.$parent.$watchCollection)) {
        scope.$parent.$watchCollection(attrs.checklistModel, setChecked);
    } else {
        scope.$parent.$watch(attrs.checklistModel, setChecked, true);
    }
  }

  return {
    restrict: 'A',
    priority: 1000,
    terminal: true,
    scope: true,
    compile: function(tElement, tAttrs) {
      if (tElement[0].tagName !== 'INPUT' || tAttrs.type !== 'checkbox') {
        throw 'checklist-model should be applied to `input[type="checkbox"]`.';
      }

      if (!tAttrs.checklistValue) {
        throw 'You should provide `checklist-value`.';
      }

      // exclude recursion
      tElement.removeAttr('checklist-model');
      
      // local scope var storing individual checkbox model
      tElement.attr('ng-model', 'checked');

      return postLinkFn;
    }
  };
}]);

