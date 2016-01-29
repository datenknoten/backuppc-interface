function BackupController($scope,data_service) {
    $scope.DataService = data_service;

    $scope.setActiveElement = function(name) {
        data_service.setActiveElement(name);
    };
}

function NavigationController($scope,data_service) {
    $scope.DataService = data_service;

    $scope.setActiveElement = function(name) {
        data_service.setActiveElement(name);
    };
}

function DataService($interval,$http) {
    var data = {};
    var active_element = '';
    $http({
        method: 'GET',
        url: '/host/'
    }).then(function success(response){
        data['hosts'] = response.data;
    });

    return {
        'data' : data,
        'active_element' : active_element,
        'setActiveElement': function(element) {
            this.active_element = element;
            var data = this.data;
            $http({
                method: 'GET',
                url: '/host/'+element
            }).then(function success(response){
                data['hosts'][element]['jobs'] = response.data;
            });
        }
    };
}

function BytesFilter() {
	return function(bytes, precision) {
		if (isNaN(parseFloat(bytes)) || !isFinite(bytes)) return '-';
		if (typeof precision === 'undefined') precision = 1;
		var units = ['bytes', 'kB', 'MB', 'GB', 'TB', 'PB'],
			number = Math.floor(Math.log(bytes) / Math.log(1024));
		return (bytes / Math.pow(1024, Math.floor(number))).toFixed(precision) +  ' ' + units[number];
	}
}

function BytesPerSecondFilter() {
	return function(bytes, precision) {
		if (isNaN(parseFloat(bytes)) || !isFinite(bytes)) return '-';
		if (typeof precision === 'undefined') precision = 1;
		var units = ['bytes', 'kB', 'MB', 'GB', 'TB', 'PB'],
			number = Math.floor(Math.log(bytes) / Math.log(1024));
		return (bytes / Math.pow(1024, Math.floor(number))).toFixed(precision) +  ' ' + units[number] + '/s';
	}
}


(function (angular) {
    'use strict';
    angular.module('BackupApp',['ngPrettyJson','angularMoment'])
        .factory('DataService',['$interval','$http',DataService])
        .controller('BackupController',['$scope','DataService',BackupController])
        .controller('NavigationController',['$scope','DataService',NavigationController])
        .filter('bytes',BytesFilter)
        .filter('bytesPerSeconds',BytesPerSecondFilter)
    ;
})(window.angular);


$(document).ready(function(){
    $('.ui.sidebar')
        .sidebar('setting', 'transition', 'overlay')
        .sidebar()
    ;

    $('#show_sidebar').click(function(){
        $('.ui.sidebar')
            .sidebar('toggle')
        ;
    });
});
