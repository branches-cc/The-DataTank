<?php

/**
 * Copyright (C) 2011 by iRail vzw/asbl
 *
 * Author: Pieter Colpaert <pieter aŧ iRail.be>
 * License: AGPLv3
 *
 * This file prettyprints autogenerated API Documentation
 */

ini_set("include_path", "../");
include_once("error/Exceptions.class.php");
include_once("Config.class.php");
include_once("TDT.class.php");
include_once("modules/ProxyModules.php");

/**
 * Get all derived classes from another class
 */
function getAllDerivedClasses($classname){
     $result = array();
     foreach(get_declared_classes() as $class){
	  if(get_parent_class($class) == $classname){
	       $result[] = $class;
	  }
     }
     return $result;
}

//print page
include_once("templates/TheDataTank/header.php");
$url = Config::$HOSTNAME . "TDTInfo/Modules/?format=json&proxy=1";
$stats = "";
try{
     $stats = json_decode(TDT::HttpRequest($url)->data);
}
catch(Exception $e){
    echo "what?"
     //...
}

//Test whether HttpRequest succeeded 
if(isset($stats->module)){
     echo "<h1>Modules and methods</h1>";
     foreach($stats->module as $modu){
	  $name = $modu->name;
	  echo "<h2><a href=\"" . $modu->url ."docs/\">$name</a><small>(". $modu->url  .")</small></h2>\n";
	  echo "<ul>";
	  foreach($modu->method as $method){
	       $methodname = $method->name;
	       //echo "<li><a href=\"".$modu->url."docs/$name/$methodname/\">$methodname</a> - ". $method->doc ."</li>";
	       echo "<li><a href=\"/docs/$name/$methodname/\">$methodname</a> - ". $method->doc ."</li>";
	  }
	  echo "</ul>";
     }
}else{
     echo "Error occured: check " . $url;
}

echo "<h1>Errors</h1>";

foreach(getAllDerivedClasses("AbstractTDTException") as $class){
     echo "<h4>".$class::$error." - $class</h4>";
     echo $class::getDoc();
     echo "<br/>";
}
include_once("templates/TheDataTank/footer.php");
?>
