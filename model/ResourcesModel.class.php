<?php
/**
 * Will combine all other factories in 1 factory! This is the model for Controller.class.php.
 *
 * @package The-Datatank/model
 * @copyright (C) 2011 by iRail vzw/asbl
 * @license AGPLv3
 * @author Pieter Colpaert
 */

include_once("model/GenericResourceFactory.class.php");
include_once("model/InstalledResourceFactory.class.php");
include_once("model/RemoteResourceFactory.class.php");
include_once("model/CoreResourceFactory.class.php");

class ResourcesModel extends AResourceFactory{

    private static $uniqueinstance;    

    private $factories;//array of factories

    private function __construct(){
	$this->factories = array(); //(ordening does matter here! Put the least expensive on top)
	$this->factories["generic"] = new GenericResourceFactory();
        $this->factories["core"] = new CoreResourceFactory();
	$this->factories["remote"] = new RemoteResourceFactory();
	$this->factories["installed"] = new InstalledResourceFactory();
    }
    
    public static function getInstance(){
	if(!isset(self::$uniqueinstance)){
	    self::$uniqueinstance = new ResourcesModel();
	}
	return self::$uniqueinstance;
    }

    /**
     * @return returns a string containing the documentation about the resource. It returns an empty string when the resource could not be found
     */
    public function getResourceDoc($package, $resource){
	foreach($this->factories as $factory){
	    if($factory->hasResource($package,$resource)){
		return $factory->getResourceDoc($package,$resource);
	    }
	}
	//if not really any factory has the resource, throw an exception
	throw new ResourceOrPackageNotFoundTDTException($package . "/" .$resource);
    }

    /**
     * @return returns an associative array with the documentation for each parameter for a specific resource 
     */
    public function getResourceParameters($package, $resource){
	foreach($this->factories as $factory){
	    if($factory->hasResource($package,$resource)){
		return $factory->getResourceParameters($package,$resource);
	    }
	}
	//if not really any factory has the resource, throw an exception
	throw new ResourceOrPackageNotFoundTDTException($package . "/" .$resource);
    }

    /**
     * @return returns an array with all required parameters
     */
    public function getResourceRequiredParameters($package,$resource){
	foreach($this->factories as $factory){
	    if($factory->hasResource($package,$resource)){
		return $factory->getResourceRequiredParameters($package,$resource);
	    }
	}
	//if not really any factory has the resource, throw an exception
	throw new ResourceOrModuleNotFoundTDTException($package . "/" .$resource);
    }
    
    public function getAllowedPrintMethods($package,$resource){
	foreach($this->factories as $factory){
	    if($factory->hasResource($package,$resource)){
		return $factory->getAllowedPrintMethods($package,$resource);
	    }
	}
	//if not really any factory has the resource, throw an exception
	throw new ResourceOrModuleNotFoundTDTException($package . "/" .$resource);
    }

    /**
     * @return an array containing all the resourcenames available
     */
    public function getAllResourceNames(){
	$rn = array();
	foreach($this->factories as $factory){
	    foreach($factory->getAllResourceNames() as $package => $resourcenames){
		if(isset($rn[$package])){
		    $rn[$package] = array_merge($rn[$package],$resourcenames);
		}else{
		    $rn[$package] = $resourcenames;
		}	
	    }
	}
	return $rn;
    }


    public function hasResource($package,$resource){
	foreach($this->factories as $factory){
	    if($factory->hasResource($package,$resource)){
		return true;
	    }
	}
	return false;
    }    

    /**
     * @return gets an instance of a AResource class.
     */
    public function getResource($package,$resource){
	//find the one who has the resource!
	foreach($this->factories as $factory){
	    if($factory->hasResource($package,$resource)){
		return $factory->getResource($package,$resource);
	    }
	}
	throw new ResourceOrPackageNotFoundTDTException($package . "/" . $resource);
    }

    /*****************************************SETTERS****************************************/
    public function deleteResource($package,$resource){
        foreach($this->factories as $factory){
            if($factory->hasResource($package,$resource)){
                $factory->deleteResource($package,$resource);
                break;
            }
        }
        
    }
    
    public function deletePackage($package){
        //delete all resources in every factory
        foreach($this->factories as $factory){
            $factory->deletePackage($package);
        }
        //now also delete the package-entry in the db
        
        $deletePackage = R::exec(
            "DELETE from package WHERE package_name=:package",
            array(":package" => $package)
        );
    }
    
    public function addResource($package,$resource, $content){
        //validation of add parameters
        if(!isset($content["resource_type"])){
            throw new ParameterTDTException("resource_type");
        }

        $resource_type = $content["resource_type"];
        if(!isset($this->factories[$resource_type])){
            throw new ResourceAdditionTDTException("Resource type $resource_type does not exist");
        }

        $factory = $this->factories[$resource_type];
        $factory->addResource($package,$resource,$content);
    }


    public function makePackageId($package){
        //will return the ID of the package. If package does not exists, it will be added
        //if we're requesting an installed or core package, it doesn't matter since the package is not resourcetype dependant
        //so adding it won't do any harm
        $result = R::getAll(
            "select id from package where package_name=:package_name",
            array(":package_name"=>$package)
        );
        if(sizeof($result) == 0){
            $newpackage = R::dispense("package");
            $newpackage->package_name = $package;
            $newpackage->timestamp = time();
            $id = R::store($newpackage);
            return $id;
        }else{
            return $result[0]["id"];
        }
    }
    
    public function updateResource($package,$resource,$content){
        foreach($this->factories as $factory){
            if($factory->hasResource($package,$resource)){
                $factory->updateResource($package,$resource,$content);
                break;
            }
        }
        
    }
}
?>
