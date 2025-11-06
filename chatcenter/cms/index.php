<?php 

/*=============================================
Depurar Errores
=============================================*/

define('DIR',__DIR__);

ini_set("display_errors", 1);
ini_set("log_errors", 1);
ini_set("error_log", DIR."/php_error_log");

date_default_timezone_set("America/Bogota");

/*=============================================
Requerimientos
=============================================*/

require_once "controllers/template.controller.php";
require_once "controllers/curl.controller.php";
require_once "extensions/vendor/autoload.php";

/*=============================================
Plantilla
=============================================*/

$index = new TemplateController();
$index -> index();

?>