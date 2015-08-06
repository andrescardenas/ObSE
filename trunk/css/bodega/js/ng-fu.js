/**!
 * AngularJS file upload/drop directive with http post and progress
 * @author  Danial  <danial.farid@gmail.com>
 * @version 1.1.8
 */
(function(){var l=angular.module("angularFileUpload",[]);l.service("$upload",["$http","$rootScope",function(g,k){this.upload=function(a){a.method=a.method||"POST";a.headers=a.headers||{};a.headers["Content-Type"]=void 0;a.transformRequest=a.transformRequest||g.defaults.transformRequest;var c=new FormData;if(a.data)for(var f in a.data){var e=a.data[f];if(a.formDataAppender)a.formDataAppender(c,f,e);else{if("function"==typeof a.transformRequest)e=a.transformRequest(e);else for(var h=0;h<a.transformRequest.length;h++){var m=
a.transformRequest[h];"function"==typeof m&&(e=m(e))}c.append(f,e)}}a.transformRequest=angular.identity;c.append(a.fileFormDataName||"file",a.file,a.file.name);c.__setXHR_=function(b){a.__XHR=b;b.upload.addEventListener("progress",function(b){a.progress&&(a.progress(b),k.$$phase||k.$apply())},!1);b.upload.addEventListener("load",function(b){b.lengthComputable&&(a.progress(b),k.$$phase||k.$apply())},!1)};a.data=c;var d=g(a);d.progress=function(b){a.progress=b;return d};d.abort=function(){a.__XHR&&
a.__XHR.abort();return d};d.then=function(b,d){return function(c,h,e){a.progress=e||a.progress;d.apply(b,[c,h,e]);return b}}(d,d.then);return d}}]);l.directive("ngFileSelect",["$parse","$http",function(g,k){return function(a,c,f){var e=g(f.ngFileSelect);c.bind("change",function(c){var f=[],d,b;d=c.target.files;if(null!=d)for(b=0;b<d.length;b++)f.push(d.item(b));a.$apply(function(){e(a,{$files:f,$event:c})})});c.bind("click",function(){this.value=null})}}]);l.directive("ngFileDropAvailable",["$parse",
"$http",function(g,k){return function(a,c,f){if("draggable"in document.createElement("span")){var e=g(f.ngFileDropAvailable);a.$$phase?e(a):a.$apply(function(){e(a)})}}}]);l.directive("ngFileDrop",["$parse","$http",function(g,k){return function(a,c,f){if("draggable"in document.createElement("span")){var e=g(f.ngFileDrop);c[0].addEventListener("dragover",function(a){a.stopPropagation();a.preventDefault();c.addClass(f.ngFileDragOverClass||"dragover")},!1);c[0].addEventListener("dragleave",function(a){c.removeClass(f.ngFileDragOverClass||
"dragover")},!1);c[0].addEventListener("drop",function(h){h.stopPropagation();h.preventDefault();c.removeClass(f.ngFileDragOverClass||"dragover");var g=[],d=h.dataTransfer.files,b;if(null!=d)for(b=0;b<d.length;b++)g.push(d.item(b));a.$apply(function(){e(a,{$files:g,$event:h})})},!1)}}}])})();
