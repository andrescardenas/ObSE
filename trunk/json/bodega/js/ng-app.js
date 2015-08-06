var app = angular.module('bodega', ['ngRoute', 'ngCookies', 'ui.bootstrap', 'ui.select2','ngGrid', 'angularFileUpload', 'bodega.controllers', 'bodega.directives','bodega.services']);

app.config(function($routeProvider) {
    $routeProvider
    	.when('/login', {controller: 'loginCTRL', templateUrl: 'views/login.html'})
    	.when('/:table/:page', {controller: 'listaCTRL', templateUrl: 'views/lista.html'})
        .when('/:table/:results/:page', {controller: 'listaCTRL', templateUrl: 'views/lista.html'})
    	.when('/:table/:relation/:id/:page', {controller: 'listaCTRL', templateUrl: 'views/lista.html'})
    	.otherwise({redirectTo: '/login'})
});

app.run(function($rootScope, $location, $cookieStore) {
    $rootScope.debug = false;

    $rootScope.secciones = new Array('usuarios','tiposidentificacion', 'contratos', 'tiposcontratos', 'busquedas', 'moneda', 'tiposmovimientos','tipostelefono','planes','diccionario', 'etiquetas', 'caracteristicas', 'servicios_comentarios');

    $rootScope.$on('$routeChangeStart', function(_event, _page) {
    	if($cookieStore.get('user')){
    		if(!_page.params.table) $location.path('usuarios/1');
    	}else{
    		$location.path('login');
    	}
    });
});