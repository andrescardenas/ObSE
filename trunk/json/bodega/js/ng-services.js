var services = angular.module('bodega.services', []);

services.service('busquedaSVC', function(){
	var busqueda;

	this.setBusqueda = function(_objeto){
		busqueda = _objeto;
	}

	this.getBusqueda = function(){
		return busqueda;
	}
});
