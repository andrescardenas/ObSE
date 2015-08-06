/* 
Excepción en formularioCTRL -> condicionar;
Excepción en formularioCTRL -> validar (el nombre de los campos cambia);
*/

var controllers = angular.module('bodega.controllers', []);

controllers.controller('loginCTRL', function($scope, $http, $cookieStore, $location){
    $scope.formulario = new Object();
    $cookieStore.put('user', false);

    $scope.login = function() {
        $http({
            url: 'json/user.php',
            method: 'POST',
            data: $scope.formulario,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function(result){
            if(result.data.status=='OK'){
                $cookieStore.put('user', {id: result.data.id, name: result.data.name});
                $location.path('/usuarios/1');
            }
        });
    };
});

controllers.controller('listaCTRL', function($scope, $rootScope, $http, $routeParams, $modal, $log, $location, $route, $cookieStore, busquedaSVC) {
    $log.log('Cargando lista de ' + $routeParams.table);

    $scope.user = $cookieStore.get('user');
    $scope.items = 20;
    
    if($routeParams.relation) {
        $scope.modelo = $routeParams.relation;
        $scope.breadcrum = 'Nombre de la actividad > ' + $routeParams.relation.split($routeParams.table+'_')[1];
        $scope.envio = {table:$scope.modelo,page:$routeParams.page,items:$scope.items,id:$routeParams.id};
        $scope.url = $routeParams.table + '/' + $routeParams.relation + '/' + $routeParams.id + '/';
        $scope.relaciones = true;
    } else if($routeParams.results) {
        $scope.modelo = $routeParams.table;
        $scope.breadcrum = $routeParams.table;
        $scope.envio = {table:$scope.modelo,page:$routeParams.page,items:$scope.items, formulario:busquedaSVC.getBusqueda()};
        $scope.url = $routeParams.table + '/resultados/';
    } else {
        $scope.modelo = $routeParams.table;
        $scope.breadcrum = $routeParams.table;
        $scope.envio = {table:$scope.modelo,page:$routeParams.page,items:$scope.items};
        $scope.url = $routeParams.table + '/';
    }

    $scope.gridOptions = {
        data: 'rows',
        enableRowSelection: false,
        columnDefs: 'columnDefs'
    };

    $http({
        url: 'json/lista.php',
        method: 'POST',
        data: $scope.envio,
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).success(function(result){
        $scope.page = $routeParams.page;
        $scope.total = result.total;
        $scope.pages = Math.ceil($scope.total/$scope.items);
        $scope.columns = result.columns;
        $scope.rows = result.rows;
        $scope.fields = new Array('cero', 'uno', 'dos', 'tres', 'cuatro', 'cinco');
        $scope.columnDefs = new Array();
        $scope.relations = result.relations;

        for (var i = 1; i < $scope.columns.length; i++) {
            $scope.columnDefs.push({field: $scope.fields[i], displayName: $scope.columns[i]});
        }

        $scope.acciones = new Array();
        for(relation in $scope.relations){
            var x = '"'+$scope.relations[relation]+'"';
            $scope.acciones.push("<a class='btn btn-info btn-xs' ng-click='relation(row.entity,"+x+")'><i class='glyphicon glyphicon-plus-sign'></i></a>");
        }
        $scope.acciones.push("<a class='btn btn-info btn-xs' ng-click='editar(row.entity)'><i class='glyphicon glyphicon-pencil'></i></a>");
        $scope.acciones.push("<a class='btn btn-danger btn-xs' ng-click='eliminar(row.entity)'><i class='glyphicon glyphicon-trash'></i></a>");
        $scope.columnDefs.push({displayName: '', width: 30*$scope.acciones.length, sortable: false, cellTemplate: $scope.acciones.join(' ')});

        $scope.buscar = function() {
            $log.log('Buscar en '+ $scope.modelo);
            $modal.open({
                templateUrl: 'views/formulario.html',
                controller: 'formularioCTRL',
                resolve: {
                    accion: function() {
                        return 'buscar';
                    },
                    modelo: function() {
                        return $scope.modelo;
                    },
                    formulario: function() {
                        return {};
                    }
                }
            });
        };

        $scope.crear = function() {
            $log.log('Crear en '+ $scope.modelo);
            $modal.open({
                templateUrl: 'views/formulario.html',
                controller: 'formularioCTRL',
                resolve: {
                    accion: function() {
                        return 'crear';
                    },
                    modelo: function() {
                        return $scope.modelo;
                    },
                    formulario: function() {
                        return {};
                    }
                }
            });
        };

        $scope.editar = function(_entidad) {
            $log.log('Editar en ' + $scope.modelo + ' el id:' + _entidad.id);
            $http.get('json/detalle.php?modelo=' + $scope.modelo + '&id=' + _entidad.id).then(function(result) {
                $modal.open({
                    templateUrl: 'views/formulario.html',
                    controller: 'formularioCTRL',
                    resolve: {
                        accion: function() {
                            return 'editar';
                        },
                        modelo: function() {
                            return $scope.modelo;
                        },
                        formulario: function() {
                            return result;
                        }
                    }
                });
                $log.log('Mostrando detalles de ' + $scope.modelo + ' id:' + _entidad.id);
            });
        };

        $scope.eliminar = function(_entidad) {
            $log.log('Eliminar en ' + $scope.modelo + ' el id:' + _entidad.id);

            $scope.envio = {
                accion: "eliminar",
                modelo: $scope.modelo,
                id: _entidad.id,
                id_user: $scope.user.id
            };

            $http({
                url: 'json/modelo.php',
                data: $scope.envio,
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).success(function() {
                $log.log('Se eliminó en ' + $scope.modelo + ' el id:' + _entidad.id);
                $route.reload();
            });
        };

        $scope.relation = function(_entidad,_relation) {
            $location.path($scope.modelo+"/"+_relation+"/"+_entidad.id+"/1");
        };

        $scope.navegar = function(_page) {
            $location.path($scope.url+_page);
        };

        $scope.logout = function() {
            $cookieStore.put('user', false);
            location.reload();
        };

        $log.log('Mostrando lista de ' + $scope.modelo);
    });
});

controllers.controller('formularioCTRL', function($scope, $rootScope, $http, $modalInstance, $log, $route, $upload, $routeParams, $location, $cookieStore, busquedaSVC, accion, modelo, formulario) {
    $http.get('json/formulario.php?table=' + modelo).then(function(result) {
        // Configura los campos del formulario
        $scope.user = $cookieStore.get('user');
        $scope.accion = accion;
        $scope.modelo = modelo;
        $scope.enviando = false;
        $scope.porcentaje = 0;
        $scope.formulario = new Object();
        $scope.formulario.files = new Object();
        $scope.campos = result.data.fields;
        $scope.envio = {id_user:$scope.user.id, accion: $scope.accion, modelo: $scope.modelo, formulario: $scope.formulario};
        $scope.alertas = new Array();

        if($scope.modelo.split('_')[0]=='r2') $scope.titulo = accion+' '+$scope.modelo.split('_')[2];
        else $scope.titulo = accion+' '+modelo;

        // Llena el formulario
        if ($scope.accion === 'editar') {
            for (var campo in formulario.data) {
                // Cuando se trata de un campo de fecha se crea el objeto
                if(campo.indexOf('da_')==0) {
                    if(formulario.data[campo] == null) $scope.formulario[campo] = null;
                    else $scope.formulario[campo] = new Date(formulario.data[campo] + ' 00:00:00');
                }
                else if(campo.indexOf('dt_')==0) {
                    if(formulario.data[campo] == null) $scope.formulario[campo] = null;
                    else $scope.formulario[campo] = formulario.data[campo];
                }
                else $scope.formulario[campo] = formulario.data[campo];
            }
        }

        // Cuando se trata de una relación de tablas
        if($routeParams.relation){
            $scope.formulario['hd_'+$routeParams.relation.split('_')[1]] = $routeParams.id;
        }

        $scope.condicionar = function(_campo){
            // Excepciones para la búsqueda
            if($scope.accion == 'buscar' && _campo.split('_')[0]=='ta') return false;
            if($scope.accion == 'buscar' && _campo.split('_')[0]=='da') return false;
            if($scope.accion == 'buscar' && _campo.split('_')[0]=='dt') return false;
            if($scope.accion == 'buscar' && _campo.split('_')[0]=='fi') return false;
            // Excepciones para Dejusticia
            if($scope.modelo == 'actividades' && _campo == 'au_medio' && $scope.formulario.rd_tipo>2 && $scope.formulario.rd_tipo<5) return false;
            if($scope.modelo == 'actividades' && _campo == 'ta_demandado' && $scope.formulario.rd_tipo!=2) return false;
            if($scope.modelo == 'actividades' && _campo == 'da_fecha_inicio' && $scope.formulario.rd_tipo!=3) return false;
            if($scope.modelo == 'actividades' && _campo == 'da_fecha_final' && $scope.formulario.rd_tipo!=3) return false;
            if($scope.modelo == 'actividades' && _campo == 'bl_organiza' && $scope.formulario.rd_tipo!=3) return false;
            return true;
        };

        $scope.autocompletar = function(_model, _options) {
            var actual = '';
            if (_model) {
                angular.forEach(_options, function(_option) {
                    if (_model === _option.id) {
                        actual = _option.name;
                    }
                });
            }
            return actual;
        };

        $scope.adjuntar = function(_file, _model) {
            $scope.resultado = $scope.validar(_model.split('_')[2], _file[0]['type'], _file[0]['size']);

            if($scope.resultado[0]){
                $scope.formulario[_model] = _file[0]['name'];
                $scope.formulario.files[_model] = _file[0];
                $scope.formulario['fi_name_'+_model.split('_')[2]] = _file[0]['name'];
                $scope.formulario['fi_type_'+_model.split('_')[2]] = _file[0]['type'];
                $scope.formulario['fi_size_'+_model.split('_')[2]] = _file[0]['size'];
                $scope.alertas = new Array();
            } else {
                $scope.alertas = new Array();
                $scope.alertas.push({type: 'danger', mensaje: $scope.resultado[1]});
                $scope.eliminar(_model);
            }
        };

        $scope.validar = function(_model, _type, _size){
            if(_model=='recurso' && _type=='application/pdf') {
                if(_size<10000000) return [true,''];
                else return [false,'El campo de '+_model+' sólo acepta tamaños inferiores a 10M.'];
            }else if(_model=='imagen' && _type=='image/jpeg' || _model=='imagen' && _type=='image/gif' || _model=='imagen' && _type=='image/png') {
                if(_size<3000000) return [true,''];
                else return [false,'El campo de '+_model+' sólo acepta tamaños inferiores a 3500K.'];
            }else if(_model=='zoom' && _type=='image/jpeg' || _model=='zoom' && _type=='image/gif' || _model=='zoom' && _type=='image/png') {
                if(_size<3000000) return [true,''];
                else return [false,'El campo de '+_model+' sólo acepta tamaños inferiores a 3500K.'];
            }else return [false,'El campo de '+_model+' no tiene la extensión correcta.'];
        };

        $scope.eliminar = function(_model) {
            delete $scope.formulario.files['fi_name_'+_model.split('_')[2]];
            delete $scope.formulario['fi_name_'+_model.split('_')[2]];
            delete $scope.formulario['fi_type_'+_model.split('_')[2]];
            delete $scope.formulario['fi_size_'+_model.split('_')[2]];
        };

        $scope.cerrarAlerta = function(index) {
            $scope.alertas.splice(index, 1);
        };

        $scope.enviar = function() {
            $log.log('Enviando al modelo');
            $scope.enviando = true;

            if($scope.accion == 'buscar') {
                delete $scope.formulario.files;
                for(var campo in $scope.formulario){
                    if(!$scope.formulario[campo].length) delete $scope.formulario[campo];
                }
                busquedaSVC.setBusqueda($scope.formulario);
                $route.reload();
                $location.path($scope.modelo+'/resultados/1');
                $modalInstance.dismiss();
            }else{
                $http({
                    url: 'json/modelo.php',
                    method: 'POST',
                    data: $scope.envio,
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function(_id) {
                    $log.log('Recibido ID');
                    if(Object.keys($scope.formulario.files).length){
                        $scope.titulo = 'Subiendo archivos'
                        Object.keys($scope.formulario.files).forEach(function(key) {
                            $log.log('Enviando el archivo');

                            var datos = new Object();
                            datos.modelo = modelo;
                            datos.campo = key;
                            datos.accion = accion;
                            if($scope.accion === 'editar') datos.id = $scope.formulario.id;
                            else datos.id = _id;
                            $log.log(datos);
                            $log.log($scope.formulario.files[key]);
                            $scope.upload = $upload.upload({
                                url: 'json/archivo.php', 
                                data: {myObj: datos},
                                file: $scope.formulario.files[key]
                            }).progress(function(e) {
                                $scope.porcentaje = parseInt(100.0 * e.loaded / e.total);
                            }).success(function() {
                                $modalInstance.dismiss();
                                $log.log('Se creó en ' + $scope.modelo);
                                $route.reload();
                            });
                        }); 
                    } else {
                        $modalInstance.dismiss();
                        $log.log('Se creó en ' + $scope.modelo);
                        $route.reload();
                    }
                });
            }
        };

        $scope.cancelar = function() {
            $log.log('Cancelar ' + $scope.accion + ' en ' + $scope.modelo);
            $modalInstance.dismiss();
        };
    });
});

controllers.controller('dateCTRL', function($scope, $timeout){
    $scope.open = function($event) {
        $event.preventDefault();
        $event.stopPropagation();
        $scope.opened = true;
    };
});