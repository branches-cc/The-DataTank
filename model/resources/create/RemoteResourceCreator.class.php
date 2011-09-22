<?php
/**
 * This class creates a remote resource
 *
 * @package The-Datatank/model/resources/create
 * @copyright (C) 2011 by iRail vzw/asbl
 * @license AGPLv3
 * @author Jan Vansteenlandt
 */

include_once("ACreator.class.php");

/**
 * When creating a resource, we always expect a PUT method!
 */
class RemoteResourceCreator extends ACreator{

    public function __construct($resource_type){
        parent::__construct();
        /**
         * Add the parameters
         */
        $this->parameters["base_url"]  = "The base url from the remote resource.";
        $this->parameters["package_name"] = "The remote package name of the remote resource.";
        
        /**
         * Add the required parameters
         */
        $this->requiredParameters["base_url"] = "";
        $this->requiredParameters["package_name"] = "";
        
    }

    /**
     * execution method
     * Preconditions: 
     * parameters have already been set.
     */
    public function create(){

        // format the base url
        $base_url = $this->requiredParameters["base_url"];
        if(substr(strrev($base_url),0,1) != "/"){
            $base_url .= "/";
        }

        $packagename = $this->requiredParameters["package_name"];
        
        // 1. First check if it really exists on the remote server
        $url = $base_url."TDTInfo/Resources/" . $content["package_name"] . "/". $resource .".php";
        $options = array("cache-time" => 1); //cache for 1 second
        $request = TDT::HttpRequest($url, $options);
        if(isset($request->error)){
            throw new HttpOutTDTException($url . " does not exist! Please check the package name and base url");
        }
        $object = unserialize($request->data);
        if(!isset($object["doc"])){
            throw new ResourceAdditionTDTException("$resource does not exist on the remote server");
        }

        // 2. Check if the resource on the server contains an "orginal" resource URI and take that URI instead if exists and reload everything
        if(isset($object["extra"]) && isset($object["extra"]["base_url"])){
            $base_url = $object["extra"]["base_url"];
            $packagename = $object["extra"]["package_name"];
        }

        // 3. store it
        $package_id = parent::makePackageId($package);
        $resource_id = parent::makeResourceId($package_id, $resource, "remote");
        DBQueries::storeRemoteResource($resource_id, $packagename, $base_url);
    }
    
    /**
     * get the documentation about the addition of a resource
     */
    public function getCreateDocumentation(){
        return "This class creates a remote resource.";
    }
    
}
?>