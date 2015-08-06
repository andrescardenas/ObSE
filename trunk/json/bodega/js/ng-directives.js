var directives = angular.module('bodega.directives', []);

directives.directive('mrHour', function() {
    return {
        restrict: 'A',
        scope: { model: '=ngModel' },

        link: function(scope, element, attrs) {
            element.datetimepicker({
                format: "dd MM yyyy - hh:ii",
                autoclose: true,
                todayBtn: true,
                pickerPosition: "bottom-left"
            }).on('changeDate', function(event){
                scope.model = event.date;
                scope.$apply();
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
