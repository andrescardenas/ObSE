var controllers = angular.module('soyexperto.controllers', []);

controllers.controller('menuCTRL', function($scope, $http, $location, $modal){
    $scope.perfil = false;

    $scope.$on('$routeChangeSuccess', function(){
        var url = $location.path();
        if ( url.indexOf("perfil") == '1' ){
            var url_split = url.split('/');
            $scope.usuario = url_split[2];
            $scope.usuario = $scope.usuario.charAt(0).toUpperCase() + $scope.usuario.slice(1);
            $scope.perfil = true;
        }

        if($scope.perfil) $scope.menu = 'views/menu_usuario.html';
        else $scope.menu = 'views/menu_general.html';
    });

    $scope.cerrarSesion = function(){
        $scope.perfil = false;
        $location.path("index");
    };

    $scope.login = function() {
        $modal.open({
            templateUrl: 'views/MD_login.html',
            controller: 'MD_loginCTRL',
            resolve: {
            }
        });
    };
});

controllers.controller('indexCTRL', function($http, $location, $scope, $cookieStore, $modal){

    //mostrar video o no
    if ( $cookieStore.get('video') ){
    }else {
        $modal.open({
            templateUrl: 'views/MD_video.html',
            controller: 'MD_videoCTRL',
            resolve: {
            }
        });
    }

    //reseteando frase búsqueda
    $cookieStore.remove('criterio');

    /* http://jsfiddle.net/CXnKD/2/ */
    $http.get('json/busquedas.php').then(function(result) {
        $scope.p = {};
        $scope.busqueda = {};
        $scope.p.opciones = result.data.busquedas;

        // Auto complete preload saved value
        //$scope.busqueda.opciones = $scope.p.opciones[0];
    });

    $scope.buscar = function() {
        $scope.criterio = {'accion':'buscar', 'nueva':0, 'frase':'','id':-1};
        //Cómo se si es una nueva o se eligió del listado
        if ( $scope.busqueda.opciones.id ) {
            console.log('VIEJAAA');
            $scope.criterio.id = $scope.busqueda.opciones.id;
            $scope.criterio.frase = $scope.busqueda.opciones.frase.toLowerCase();
        }else {
            console.log('NUEVAAA');
            $scope.criterio.nueva = 1;
            $scope.criterio.frase = $scope.busqueda.opciones.toLowerCase();
        }
        $cookieStore.put('criterio', $scope.criterio);

        $location.path("/resultados/10/1");
    };
});

controllers.controller('MD_videoCTRL',function($http, $scope, $modalInstance, $cookieStore, $route){

    $cookieStore.put('video', true);

    $scope.cancelar = function() {
        $modalInstance.dismiss();
    };
});

////ENTREGA ETAPA 1
controllers.controller('registroRapidoCTRL',function($http, $scope, $location, $routeParams, $modal, $cookieStore){
    //$scope.usuario_libre = 'has-success';
    $scope.formulario = {};
    /*$scope.formulario.tipo_identificacion = -1;
    $scope.formulario.pais = -1;
    $scope.formulario.ciudad = -1;
    $scope.formulario.telefono = {};
    $scope.formulario.telefono.tipo = -1;*/
    $scope.formulario.clave1 = '';
    $scope.formulario.clave2 = '';
    
    $scope.mensaje = {};
    $scope.mensaje.formulario = "";
    $scope.mensaje.usuario = "";
    $scope.mensaje.descripcion = "";

    //Si llega referente y referido, traigo el listado de referentes para que el usuario elija
    if (typeof $routeParams.referente !== 'undefined' && typeof $routeParams.referido !== 'undefined' ) {
        $scope.formulario.accion = 'referentes';
        $scope.formulario.referente = $routeParams.referente;
        $scope.formulario.referido = $routeParams.referido;
        $scope.formulario.email = $routeParams.referido;
        $http({
            url: 'json/registro.php',
            method: 'POST',
            data: $scope.formulario,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function(result){
            $scope.referentes = result.data.referentes;
            $scope.info_referente = result.data.info_referente;
            $scope.formulario.referente = $scope.referentes[$scope.posicionUsuario($scope.referentes, result.data.info_referente.usuario)];
            $scope.paises = result.data.paises;
            $scope.ciudades = result.data.ciudades;
            $scope.tipos_identificacion = result.data.tipos_identificacion;
            $scope.tipos_telefono = result.data.tipos_telefono;
        });
    }else{
        //DATOS INICIALES
        $http({
            url: 'json/registro.php',
            method: 'POST',
            data: $scope.formulario,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function(result){
            $scope.paises = result.data.paises;
            $scope.ciudades = result.data.ciudades;
            $scope.tipos_identificacion = result.data.tipos_identificacion;
            $scope.tipos_telefono = result.data.tipos_telefono;
            $scope.contrato = result.data.contrato;
        });
    }

    //ENVIANDO DATOS A PHP
    $scope.registrar = function(){
        $scope.formulario.accion='registro';
        $http({
            url: 'json/registro.php',
            method: 'POST',
            data: $scope.formulario,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function(result){
            if(result.data.status=='OK'){
                $cookieStore.put('user', {id: result.data.user.id, nombre: result.data.user.nombre, usuario: result.data.user.usuario});
                $location.path('/perfil/'+$scope.formulario.usuario);
            }else if(result.data.status=='error_usuario'){
                $scope.mensaje.usuario = "error";
                $scope.mensaje.descripcion = result.data.mensaje;
            } else if(result.data.status=='error_cedula'){
                $scope.mensaje.formulario = "error";
                $scope.mensaje.descripcion = result.data.mensaje;
            }
        });
    };

    //TERMINOS Y CONDICIONES
    $scope.terminos = function(){
        $modal.open({
            templateUrl: 'views/MD_terminos.html',
            controller: 'MD_terminosCTRL',
            resolve: {
            }
        });
    };

    $scope.posicionUsuario = function(_vector, _usuario){
        for (var posicion in _vector) {
            if(_vector[posicion].usuario == _usuario) {
                return posicion;
            }
        }
    };

    $scope.es_real = true;
    $scope.existe_bd = false;
    $scope.validarCorreo = function(_mail){
        $scope.formulario.email = _mail;
        $scope.verificando_mail = true;
        $http({
            url: 'json/verificar_correo.php',
            method: 'POST',
            data: $scope.formulario,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function(result){
            $scope.es_real = result.data.es_real;
            $scope.existe_bd = result.data.existe_bd;
            if ( $scope.es_real===false || $scope.existe_bd===true ){
                $scope.fondoInput = {'background-color':'rgba(242, 203, 203, 0.38)', 'border-color': '#b94a48'};
            }else{
                $scope.fondoInput = {};
            }
            $scope.verificando_mail = false;
        });
    };

    //correo y usuario
    $scope.validarClaves = function(){
        //contraseñas iguales
        if ( $scope.formulario.clave1 == $scope.formulario.clave2 && $scope.formulario.clave1 !== '' && $scope.formulario.clave2 !== '' ){
            $scope.estilo_claves = {'background-color':'rgba(206, 235, 206, 0.58)'};
        }else{
            $scope.estilo_claves = {'background-color':'rgba(242, 203, 203, 0.38)'};
        }
    };
});

controllers.controller('MD_terminosCTRL', function($http, $scope, $location){

    $scope.formulario = {};
    $scope.formulario.accion = "contrato";
    $scope.formulario.id_contrato = 1;

    $http({
        url: 'json/registro.php',
        method: 'POST',
        data: $scope.formulario,
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).then(function(result){
        $scope.contrato = result.data.contrato;
    });

    $scope.cerrarAlerta = function(index) {
        $scope.alertas.splice(index, 1);
    };
    $scope.cancelar = function() {
        $modalInstance.dismiss();
    };
});

controllers.controller('resultadosCTRL', function($http, $scope, $route, $cookieStore, $routeParams, $location){

    $scope.criterio = $cookieStore.get('criterio');

    /* http://jsfiddle.net/CXnKD/2/ */
    $http.get('json/busquedas.php').then(function(result) {
        $scope.p = {};
        $scope.busqueda = {};
        $scope.busqueda.opciones = $scope.criterio.frase;
        $scope.p.opciones = result.data.busquedas;

        // Auto complete preload saved value
        //$scope.busqueda.opciones = $scope.p.opciones[0];
    });

    $scope.formulario = {};
    $scope.formulario.criterio = $scope.criterio;
    $scope.formulario.items = $routeParams.items;
    $scope.formulario.pagina = $routeParams.pagina;

    $http({
        url: 'json/resultados.php',
        data: $scope.formulario,
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).success(function(result) {
        $scope.resultados = result.resultados;
        $scope.totalResultados = result.resultados_total;
        $scope.items = $routeParams.items;
        $scope.pagina = $routeParams.pagina;
        //$log.log('Se eliminó en ' + $scope.modelo + ' el id:' + _entidad.id);
        //$route.reload();
    });

    $scope.cambioPagina = function(_pagina){
        alert("/resultados/"+_pagina+"/"+$routeParams.items);
        //$location.path("/resultados/"+_pagina+"/"+$routeParams.items);
    };

    $scope.navegar = function(){
        $location.path("/resultados/"+$scope.items+"/"+$scope.pagina);
    };

    $scope.buscar = function() {
        $scope.criterio = {'accion':'buscar', 'nueva':0, 'frase':'','id':-1};
        //Cómo se si es una nueva o se eligió del listado
        if ( $scope.busqueda.opciones.id ) {
            console.log('VIEJAAA');
            $scope.criterio.id = $scope.busqueda.opciones.id;
            $scope.criterio.frase = $scope.busqueda.opciones.frase.toLowerCase();
        }else {
            console.log('NUEVAAA');
            $scope.criterio.nueva = 1;
            $scope.criterio.frase = $scope.busqueda.opciones.toLowerCase();
        }
        $cookieStore.put('criterio', $scope.criterio);

        //$location.path("/resultados/10/1");
        $route.reload();
    };
});

controllers.controller('usuarioServicioCTRL', function($http, $scope, $routeParams, $cookieStore){

    $scope.dias_semana = {1:'Lunes', 2:'Martes', 3:'Miércoles', 4:'Jueves', 5:'Viernes', 6:'Sábado', 7:'Domingo'};

    $scope.formulario = {};
    $scope.formulario.accion = "visita";
    $scope.formulario.usuario = $routeParams.usuario;
    $scope.formulario.id_servicio = $routeParams.servicio;

    $http({
        url: 'json/servicio.php',
        method: 'POST',
        data: $scope.formulario,
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).then(function(result){
        $scope.servicio = result.data.servicio;
        $scope.calificacion = result.data.calificacion;
        $scope.calificacion_valor = result.data.calificacion_valor;
        $scope.comentarios = result.data.comentarios;
    });

    $scope.comentar = function() {
        $modal.open({
            templateUrl: 'views/MD_comentar.html',
            controller: 'MD_comentarCTRL',
            resolve: {
                parametros: function() {
                        return {id_servicio: $routeParams.servicio, id_autor: $cookieStore.get("user").id};
                    }
            }
        });
    };
});

controllers.controller('MD_comentarCTRL',function($http, $scope, $modalInstance, $cookieStore, $location, $route){
});

controllers.controller('MD_loginCTRL',function($http, $scope, $modalInstance, $cookieStore, $location, $route){

    $cookieStore.put('user', false);

    $scope.enviar = function (){
        $http({
            url: 'json/usuario.php',
            method: 'POST',
            data: $scope.formulario,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function(result){
            console.log(result.data);
            $scope.message = result.data.message;
            if(result.data.status=='OK'){
                $modalInstance.dismiss();
                $cookieStore.put('user', {id: result.data.id, nombre: result.data.nombre, usuario: result.data.usuario});
                $location.path('perfil/'+result.data.usuario);
                $route.reload();
            }
        });
    };

    $scope.cerrarAlerta = function(index) {
        $scope.alertas.splice(index, 1);
    };
    $scope.cancelar = function() {
        $modalInstance.dismiss();
    };
});

controllers.controller('perfilCTRL',function($http, $scope, $cookieStore, $routeParams, $modal){

    $scope.usuario = $routeParams.usuario;
    $scope.user = $cookieStore.get('user');
    $scope.formulario = {};
    $scope.formulario.accion = 'datos_iniciales';
    $scope.formulario.user = $scope.user;
    $scope.formulario.telefonos = [{telefono:'',tipo:0}];
    $scope.tipos_telefono = [{id:'0',nombre:'Seleccione una opción'},{id:'1',nombre:'fijo'},{id:'2',nombre:'celular'}];

    $scope.mensaje = {};
    $scope.mensaje.formulario = "";
    $scope.mensaje.usuario = "";
    $scope.mensaje.descripcion = "";

    $http({
        url: 'json/perfil.php',
        method: 'POST',
        data: $scope.formulario,
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).then(function(result){
        $scope.formulario = result.data.usuario; 
        $cookieStore.put('avatar', $scope.formulario.imagen);
    });

    $scope.editarCampos = function(){
        $formulario.accion = "editar_campos";
        $http({
            url: 'json/perfil.php',
            method: 'POST',
            data: $scope.formulario,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function(result){
            console.log("OK Campos editados!");
        });
    };

    $scope.cambiar_clave = function() {
        $modal.open({
            templateUrl: 'views/MD_cambiar_clave.html',
            controller: 'MD_cambiar_claveCTRL',
            resolve: {
            }
        });
    };

    $scope.agregarTelefono = function(){
        $scope.formulario.telefonos.push({telefono:'',tipo:0});
    };

    $scope.agregarEmail = function(){
        $scope.formulario.emails.push({id:'',email:''});
    };
});

controllers.controller('MD_editarPerfilCTRL',function($http, $scope, $cookieStore, $modalInstance){
    $scope.cerrarAlerta = function(index) {
        $scope.alertas.splice(index, 1);
    };
    $scope.cancelar = function() {
        $modalInstance.dismiss();
    };
});

controllers.controller('MD_cambiar_claveCTRL',function($http, $scope, $modalInstance){
    $scope.cerrarAlerta = function(index) {
        $scope.alertas.splice(index, 1);
    };
    $scope.cancelar = function() {
        $modalInstance.dismiss();
    };
});

controllers.controller('perfilEspecializadaCTRL',function($http, $scope, $cookieStore, $routeParams, $modal, $route){
    $scope.usuario = $routeParams.usuario;
    $scope.formulario = {};
    $scope.formulario.user = $cookieStore.get('user');
    $scope.imagen = $cookieStore.get('avatar');

    $http({
        url: 'json/servicios.php',
        method: 'POST',
        data: $scope.formulario,
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).then(function(result){
        $scope.servicios = result.data.servicios;
    });

    $scope.eliminar = function(_id){
        $scope.formulario.accion = 'eliminar';
        $scope.formulario.id_servicio = _id;
        $http({
            url: 'json/servicio.php',
            method: 'POST',
            data: $scope.formulario,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function(result){
            $route.reload();
        });
    };

    $scope.nuevo = function() {
        $modal.open({
            templateUrl: 'views/MD_servicio.html',
            controller: 'MD_servicioCTRL',
            resolve: {
            }
        });
    };

    $scope.editar = function() {
        $modal.open({
            templateUrl: 'views/MD_servicio.html',
            controller: 'MD_servicioCTRL',
            resolve: {
            }
        });
    };
});

controllers.controller('MD_servicioCTRL',function($http, $scope, $modalInstance, $cookieStore, $route){


    $scope.dias = [{id:1, nombre:'Lunes'},{id:2, nombre:'Martes'},{id:3, nombre:'Miércoles'},{id:4, nombre:'Jueves'},{id:5, nombre:'Viernes'},{id:6, nombre:'Sábado'},{id:7, nombre:'Domingo'}];
    $scope.formulario = {};
    $scope.formulario.experiencias = [{donde:'', inicio:'', fin:'',descripcion:''}];
    $scope.formulario.ubicaciones = [{pais:'', ciudad: '', barrio:'', codigozip:'', descripcion:''}];
    $scope.formulario.horarios = [{dias:'', inicio:'', fin:''}];
    $scope.user = $cookieStore.get('user');
    $scope.formulario.accion = "valores_iniciales";
    $scope.formulario.id_usuario = $scope.user.id;

    $http({
        url: 'json/servicio.php',
        method: 'POST',
        data: $scope.formulario,
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).then(function(result){
        //http://vitalets.github.io/checklist-model/
        $scope.telefonos = result.data.telefonos;
        $scope.emails = result.data.emails;
        $scope.paises = result.data.paises;
        $scope.ciudades = result.data.ciudades;
        $scope.caracteristicas = result.data.caracteristicas;
        $scope.etiquetas = result.data.etiquetas;
    });

    $scope.cerrarAlerta = function(index) {
        $scope.alertas.splice(index, 1);
    };
    $scope.cancelar = function() {
        $modalInstance.dismiss();
    };
    $scope.agregarExperiencia = function(){
        $scope.formulario.experiencias.push({donde:'', inicio:'', fin:'',descripcion:''});
    };
    $scope.agregarUbicacion = function(){
        $scope.formulario.ubicaciones.push({pais:'', ciudad: '', barrio:'', codigozip:'', descripcion:''});
    };
    $scope.agregarHorario = function(){
        $scope.formulario.horarios.push({dias:'', inicio:'', fin:''});
    };
    $scope.crearServicio = function(){
        $scope.formulario.accion = "crear";
        $http({
            url: 'json/servicio.php',
            method: 'POST',
            data: $scope.formulario,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function(result){
            $modalInstance.dismiss();
            $route.reload();
        });
    };
});

controllers.controller('perfilColmenaCTRL',function($http, $scope, $cookieStore, $routeParams){

    $scope.formulario = {};
    $scope.formulario.accion = "valores_iniciales";

    $http({
        url: 'json/colmena.php',
        method: 'POST',
        data: $scope.formulario,
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).then(function(result){
        $scope.plan = result.data.plan;
    });

    $scope.usuario = $routeParams.usuario;
    $scope.imagen = $cookieStore.get('avatar');

    // Build the chart
    $scope.barras = {
        chart: {
            zoomType: 'xy'
        },
        title: {
            text: ''
        },
        subtitle: {
            text: ''
        },
        xAxis: [{
            categories: ['Jan', 'Feb', 'Mar', 'Apr'],
            crosshair: true
        }],
        yAxis: [{ // Primary yAxis
            labels: {
                format: '{value}°C',
                style: {
                    color: Highcharts.getOptions().colors[1]
                }
            },
            title: {
                text: 'Temperature',
                style: {
                    color: Highcharts.getOptions().colors[1]
                }
            }
        }, { // Secondary yAxis
            title: {
                text: 'Rainfall',
                style: {
                    color: Highcharts.getOptions().colors[0]
                }
            },
            labels: {
                format: '{value} mm',
                style: {
                    color: Highcharts.getOptions().colors[0]
                }
            },
            opposite: true
        }],
        tooltip: {
            shared: true
        },
        legend: {
            layout: 'vertical',
            align: 'left',
            x: 120,
            verticalAlign: 'top',
            y: 100,
            floating: true,
            backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'
        },
        series: [{
            name: 'Rainfall',
            type: 'column',
            yAxis: 1,
            data: [0,0],
            tooltip: {
                valueSuffix: ' mm'
            }

        }, {
            name: 'Temperature',
            type: 'spline',
            data: [0,0],
            tooltip: {
                valueSuffix: '°C'
            }
        }]
    };

    $scope.torta = {
        title: {
            text: 'Referidos'
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: false
                },
                showInLegend: true
            }
        },
        series: [{
            type: 'pie',
            name: '',
            data: [
                ['No Abonados',       100.0],
                {
                    name: 'Abonados',
                    y: 0.0,
                    sliced: true,
                    selected: true
                }
            ]
        }]
    };

    $scope.lineas = {
        chart: {
            type: 'spline'
        },
        title: {
            text: 'Referidos por Día'
        },
        subtitle: {
            text: ''
        },
        xAxis: {
            type: 'datetime',
            dateTimeLabelFormats: { // don't display the dummy year
                month: '%e. %b',
                year: '%b'
            },
            title: {
                text: 'Date'
            }
        },
        yAxis: {
            title: {
                text: 'Snow depth (m)'
            },
            min: 0
        },
        tooltip: {
            headerFormat: '<b>{series.name}</b><br>',
            pointFormat: '{point.x:%e. %b}: {point.y:.2f} m'
        },

        plotOptions: {
            spline: {
                marker: {
                    enabled: true
                }
            }
        },

        series: [{
            name: 'Winter 2007-2008',
            // Define the data points. All series have a dummy year
            // of 1970/71 in order to be compared on the same x axis. Note
            // that in JavaScript, months start at 0 for January, 1 for February etc.
            data: [
                [Date.UTC(2015,  1, 27), 0],
                [Date.UTC(2015, 2, 10), 0 ],
                [Date.UTC(2015, 3, 18), 0 ]
            ]
        }]
    };
});

controllers.controller('perfilFinanzasCTRL',function($http, $scope, $cookieStore, $routeParams, $modal){
    $scope.usuario = $routeParams.usuario;
    $scope.imagen = $cookieStore.get('avatar');

    $scope.formulario = {};
    $scope.formulario.usuario = $cookieStore.get('user');

    $http({
        url: 'json/finanzas.php',
        method: 'POST',
        data: $scope.formulario,
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).then(function(result){
        $scope.plan = result.data.plan;
        $scope.monto = result.data.monto.monto;
    });

    $scope.abonar = function() {
        $modal.open({
            templateUrl: 'views/MD_abonar.html',
            controller: 'MD_abonarCTRL',
            resolve: {
            }
        })
    }; 

    $scope.retirar = function() {
        $modal.open({
            templateUrl: 'views/MD_retirar.html',
            controller: 'MD_retirarCTRL',
            resolve: {
            }
        })
    }; 

    $scope.transferir = function() {
        $modal.open({
            templateUrl: 'views/MD_transferir.html',
            controller: 'MD_transferirCTRL',
            resolve: {
            }
        })
    };  

    $scope.estados = function() {
        $modal.open({
            templateUrl: 'views/MD_estados.html',
            controller: 'MD_estadosCTRL',
            resolve: {
            }
        })
    }; 

    $scope.debito = function() {
        $modal.open({
            templateUrl: 'views/MD_debito.html',
            controller: 'MD_debitoCTRL',
            resolve: {
            }
        })
    }; 

    $scope.adquirirPlan = function(){
        $modal.open({
            templateUrl: 'views/MD_adquirir_plan.html',
            controller: 'MD_adquirir_planCTRL',
            resolve: {
            }
        });
    }

    /*$scope.pagar = function(){
        $scope.formulario.monto = $scope.monto;
        $http({
            url: 'json/pagar.php',
            method: 'POST',
            data: $scope.formulario,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function(result){
        });
    };*/

    // Build the chart
    $scope.barras = {
        chart: {
            zoomType: 'xy'
        },
        title: {
            text: ''
        },
        subtitle: {
            text: ''
        },
        xAxis: [{
            categories: ['Jan', 'Feb', 'Mar', 'Apr'],
            crosshair: true
        }],
        yAxis: [{ // Primary yAxis
            labels: {
                format: '{value}°C',
                style: {
                    color: Highcharts.getOptions().colors[1]
                }
            },
            title: {
                text: 'Temperature',
                style: {
                    color: Highcharts.getOptions().colors[1]
                }
            }
        }, { // Secondary yAxis
            title: {
                text: 'Rainfall',
                style: {
                    color: Highcharts.getOptions().colors[0]
                }
            },
            labels: {
                format: '{value} mm',
                style: {
                    color: Highcharts.getOptions().colors[0]
                }
            },
            opposite: true
        }],
        tooltip: {
            shared: true
        },
        legend: {
            layout: 'vertical',
            align: 'left',
            x: 120,
            verticalAlign: 'top',
            y: 100,
            floating: true,
            backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'
        },
        series: [{
            name: 'Rainfall',
            type: 'column',
            yAxis: 1,
            data: [0, 0],
            tooltip: {
                valueSuffix: ' mm'
            }

        }, {
            name: 'Temperature',
            type: 'spline',
            data: [1,1],
            tooltip: {
                valueSuffix: '°C'
            }
        }]
    };

    $scope.torta = {
        title: {
            text: 'Referidos'
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: false
                },
                showInLegend: true
            }
        },
        series: [{
            type: 'pie',
            name: '',
            data: [
                ['No Abonados',       100.0],
                {
                    name: 'Abonados',
                    y: 0.0,
                    sliced: true,
                    selected: true
                }
            ]
        }]
    };

    $scope.lineas = {
        chart: {
            type: 'spline'
        },
        title: {
            text: 'Referidos por Día'
        },
        subtitle: {
            text: ''
        },
        xAxis: {
            type: 'datetime',
            dateTimeLabelFormats: { // don't display the dummy year
                month: '%e. %b',
                year: '%b'
            },
            title: {
                text: 'Date'
            }
        },
        yAxis: {
            title: {
                text: 'Snow depth (m)'
            },
            min: 0
        },
        tooltip: {
            headerFormat: '<b>{series.name}</b><br>',
            pointFormat: '{point.x:%e. %b}: {point.y:.2f} m'
        },

        plotOptions: {
            spline: {
                marker: {
                    enabled: true
                }
            }
        },

        series: [{
            name: '??',
            // Define the data points. All series have a dummy year
            // of 1970/71 in order to be compared on the same x axis. Note
            // that in JavaScript, months start at 0 for January, 1 for February etc.
            data: [
                [Date.UTC(2015,  1, 27), 0],
                [Date.UTC(2015, 2, 10), 0 ]
            ]
        }]
    };
});

controllers.controller('MD_adquirir_planCTRL',function($http, $scope, $cookieStore, $routeParams){
    $scope.usuario = $cookieStore.get('user');

    $http.get('json/planes.php').then(function(result) {
        //alert( JSON.stringify(result.data) ); // mostrar en venta alert
        $scope.planes = result.data.planes;
    });

    $scope.pagar = function(_id, _monto){
        //console.log(_id+" "+_monto+$scope.usuario.id);

        $scope.formulario = {};
        $scope.formulario.monto = _monto;
        $scope.formulario.id_plan = _id;
        $scope.formulario.usuario = $scope.usuario;

        $http({
            url: 'json/pagar.php',
            method: 'POST',
            data: $scope.formulario,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function(result){

        });
    };

    $scope.cerrarAlerta = function(index) {
        $scope.alertas.splice(index, 1);
    };
    $scope.cancelar = function() {
        $modalInstance.dismiss();
    };
});

controllers.controller('perfilReferirCTRL',function($http, $scope, $cookieStore, $routeParams, $modal){
    $scope.usuario = $routeParams.usuario;
    $scope.imagen = $cookieStore.get('avatar');

    $scope.formulario = {};
    $scope.formulario.correos = {};
    $scope.formulario.url = "http://soyexperto.net/sitio/#!/registro/"+$scope.usuario.usuario+"/";

    $scope.referir_mail = function(){
        $http({
            url: 'json/referir_mail.php',
            method: 'POST',
            data: $scope.formulario,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function(result){
            //$scope.formulario = result.data.usuario; 
            //$cookieStore.put('avatar', $scope.formulario.imagen);
        });
    };
});

controllers.controller('MD_abonarCTRL',function($http, $scope, $modalInstance){
    $scope.cerrarAlerta = function(index) {
        $scope.alertas.splice(index, 1);
    };
    $scope.cancelar = function() {
        $modalInstance.dismiss();
    };
});

controllers.controller('MD_retirarCTRL',function($http, $scope, $modalInstance){
    $scope.cerrarAlerta = function(index) {
        $scope.alertas.splice(index, 1);
    };
    $scope.cancelar = function() {
        $modalInstance.dismiss();
    };
});

controllers.controller('MD_transferirCTRL',function($http, $scope, $modalInstance){
    $scope.cerrarAlerta = function(index) {
        $scope.alertas.splice(index, 1);
    };
    $scope.cancelar = function() {
        $modalInstance.dismiss();
    };
});

controllers.controller('MD_estadosCTRL',function($http, $scope, $cookieStore, $modalInstance){

    $scope.usuario = $cookieStore.get('user');

    $scope.formulario = {};
    $scope.formulario.accion = 'listar';
    $scope.formulario.usuario = $scope.usuario;
    $scope.formulario.bl_acreditada = 1;
    $scope.formulario.items = 20;
    $scope.formulario.pagina = 1;

    $http({
        url: 'json/estados.php',
        method: 'POST',
        data: $scope.formulario,
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).then(function(result){
    });

    $scope.filtrar = function(_mes, _anio, _acreditada){
        $scope.formulario.accion = 'listar';
        $scope.formulario.usuario = $scope.usuario;
        $scope.formulario.bl_acreditada = 1;
        $scope.formulario.items = 20;
        $scope.formulario.pagina = 1;
        //...
    }

    $scope.cerrarAlerta = function(index) {
        $scope.alertas.splice(index, 1);
    };
    $scope.cancelar = function() {
        $modalInstance.dismiss();
    };
});

controllers.controller('MD_debitoCTRL',function($http, $scope, $modalInstance){
    $scope.cerrarAlerta = function(index) {
        $scope.alertas.splice(index, 1);
    };
    $scope.cancelar = function() {
        $modalInstance.dismiss();
    };
});

//////////
controllers.controller('dateCTRL', function($scope, $timeout){
    $scope.open = function($event) {
        $event.preventDefault();
        $event.stopPropagation();
        $scope.opened = true;
    };
});

controllers.controller('TimepickerDemoCtrl', function($scope, $log){

  $scope.mytime = new Date();

  $scope.hstep = 1;
  $scope.mstep = 15;

  $scope.options = {
    hstep: [1, 2, 3],
    mstep: [1, 5, 10, 15, 25, 30]
  };

  $scope.ismeridian = true;
  $scope.toggleMode = function() {
    $scope.ismeridian = ! $scope.ismeridian;
  };

  $scope.update = function() {
    var d = new Date();
    d.setHours( 0 );
    d.setMinutes( 0 );
    $scope.mytime = d;
  };

  $scope.changed = function () {
    $log.log('Time changed to: ' + $scope.mytime);
  };

  $scope.clear = function() {
    $scope.mytime = null;
  };
});

////
controllers.controller('andresCTRL', function($scope){});
