var app = angular.module('soyexperto', ['ngRoute', 'ngCookies', 'ui.select2', 'angularFileUpload','ui.bootstrap','soyexperto.controllers', 'soyexperto.directives']);

app.config(function($routeProvider, $locationProvider) {

    $locationProvider.hashPrefix('!');

    $routeProvider
        .when('/index', {controller: 'indexCTRL', templateUrl: 'views/index.html'})
        .when('/andres', {controller: 'andresCTRL', templateUrl: 'views/andres.html'})
        .when('/registro', {controller: 'registroRapidoCTRL', templateUrl: 'views/registro_rapido.html'})
        .when('/registro/:referente/:referido', {controller: 'registroRapidoCTRL', templateUrl: 'views/registro_rapido.html'})
        .when('/resultados/:items/:pagina', {controller: 'resultadosCTRL', templateUrl: 'views/resultados.html'})
        .when('/perfil/:usuario', {controller: 'perfilCTRL', templateUrl: 'views/perfil.html'})
        .when('/perfil/:usuario/especializada', {controller: 'perfilEspecializadaCTRL', templateUrl: 'views/perfilEspecializada.html'})
        .when('/perfil/:usuario/colmena', {controller: 'perfilColmenaCTRL', templateUrl: 'views/perfilColmena.html'})
        .when('/perfil/:usuario/finanzas', {controller: 'perfilFinanzasCTRL', templateUrl: 'views/perfilFinanzas.html'})
        .when('/perfil/:usuario/referir', {controller: 'perfilReferirCTRL', templateUrl: 'views/perfilReferir.html'})
        .when('/:usuario/:servicio', {controller: 'usuarioServicioCTRL', templateUrl: 'views/usuarioServicio.html'})
        .otherwise({redirectTo: '/index'});
});

app.run(function($rootScope) {
    $rootScope.debug = false;
});


/* para navegar dentro del perfil se debe obtener el usuario pero de la cookie */