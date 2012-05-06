<?php
/**
 * This class contains all queries executed by the model
 *
 * @package The-Datatank/model
 * @copyright (C) 2011 by iRail vzw/asbl
 * @license AGPLv3
 * @author Jens Segers
 * @author Jan Vansteenlandt
 * @author Pieter Colpaert
 */

class DBQueries {

    /**
     * Gets the associated Resource.id from a generic resource
     */
    static function getAssociatedResourceId($generic_resource_id){
        return R::getCell(
            "SELECT resource_id 
             FROM generic_resource
             WHERE id = :gen_res_id",
            array(":gen_res_id" => $generic_resource_id)
        );
    }
    
    /**
     * Retrieve the amount of requests done for 
     * a certain package
     */
    static function getRequestsForPackage($package,$start,$end){
        /**
         * prepare argument array
         */
        $arguments = array();
        $arguments[":package"] = $package;
        $clause = "package=:package";
        
        if($start != ""){
            $arguments[":start"] = $start;
            $clause  =$clause. " and time >= :start";
        }
        
        if($end != ""){
            $arguments[":end"] = $end;
            $clause = $clause . " and time <= :end";
        }
        
        return R::getAll(
            "SELECT count(1) as amount, time 
             FROM requests 
             WHERE $clause
             GROUP BY from_unixtime(time,'%D %M %Y')",
            $arguments
        );
    }

    /**
     * Retrieve the amount of requests done for 
     * a certain resource
     */
    static function getRequestsForResource($package,$resource,$start,$end){
        /**
         * prepare argument array
         */
        $arguments = array();
        $arguments[":package"] = $package;
        $arguments[":resource"] = $resource;
        $clause = "package=:package and resource =:resource";
        
        if($start != ""){
            $arguments[":start"] = $start;
            $clause = $clause." and time >= :start";
        }
        
        if($end != ""){
            $arguments[":end"] = $end;
            $clause = $clause. " and time <= :end";
        }
        
        return R::getAll(
            "SELECT count(1) as amount, time 
             FROM  requests 
             WHERE $clause
             GROUP BY from_unixtime(time,'%D %M %Y')",
            $arguments
        );
    }    

    /**
     * Retrieve the amount of errors done for 
     * a certain package
     * NOTE: because it's an error table, we can't just store package, resource and what not
     * because those parameters might just be the cause of the error. So in the errors case
     * we only store the wrong url and we have to regex the url.
     */
    static function getErrors($url,$start,$end){
        $arguments = array();
        
        $clause = "url_requests regexp :url";
        $arguments[":url"] = $url;
        if($start != ""){
            $arguments[":start"] = $start;
            $clause = $clause ." and time >= :start";
        }
        
        if($end != ""){
            $arguments[":end"] = $end;
            $clause = $clause. " and time <= :end";
        }
        
        return R::getAll(
            "SELECT count(1) as amount,time 
             FROM  errors
             WHERE $clause
             GROUP BY from_unixtime(time,'%D %M %Y')",
            $arguments
        );
    }

    /**
     * Retrieve a package by its id
     */
    static function getPackageById($package_id){
        return R::getCell(
            "SELECT package_name
             FROM package
             WHERE id = :package_id",
            array(":package_id" => $package_id)
        );
    }
    
    /**
     * Retrieve a resource by its id
     */
    static function getResourceById($resource_id){
        return R::getCell(
            "SELECT resource_name
             FROM resource
             WHERE id = :resource_id",
            array(":resource_id" => $resource_id)
        );
    }


    /**
     * Retrieve a specific resource's documentation
     */
    static function getGenericResourceDoc($package,$resource) {
        return R::getRow(
            "SELECT generic_resource.documentation as doc, creation_timestamp as creation_timestamp,
                        last_update_timestamp as last_update_timestamp, generic_resource.type as type, generic_resource.id as id 
                 FROM package,generic_resource,resource 
                 WHERE package.package_name=:package and resource.resource_name =:resource
                       and package.id=resource.package_id and resource.id = generic_resource.resource_id",
            array(':package' => $package, ':resource' => $resource)
        );
    }
    
    /**
     * Get a specific generic resource's type
     */
    static function getGenericResourceType($package, $resource) {
        return R::getRow(
            "SELECT generic_resource.type as type 
             FROM package,generic_resource,resource
             WHERE package.package_name=:package and resource.resource_name=:resource
                   and resource_id = resource.id
                   and package.id= resource.package_id",
            array(':package' => $package, ':resource' => $resource)
        );
    }
    
    /**
     * Retrieve all resources names and their package name
     */
    static function getAllGenericResourceNames() {
        return R::getAll(
            "SELECT resource.resource_name as res_name, package.package_name
             FROM package,generic_resource,resource 
             WHERE resource.package_id=package.id and generic_resource.resource_id=resource.id"
        );
    }

    /**
     * Retrieve all packages
     */
    static function getAllPackages(){
        $results =  R::getAll(
            "SELECT package_name, timestamp
             FROM package"
        ); 
        
        $packages = array();
        foreach($results as $result){
            $package = new stdClass();
            $package->package_name = $result["package_name"];
            $package->timestamp = (int)$result["timestamp"];
            array_push($packages,$package);
        }

        return $packages;
    }
    
    
    /**
     * Check if a specific package has a specific resource
     */
    static function hasGenericResource($package,$resource) {
        return R::getRow(
    	    "SELECT count(1) as present 
             FROM package,generic_resource,resource 
             WHERE package.package_name=:package and resource.resource_name=:resource
             and resource.package_id=package.id and generic_resource.resource_id=resource.id",
    	    array(':package' => $package, ':resource' => $resource)
    	);
    } 
    
    /**
     * Store a generic resource
     */
    static function storeGenericResource($resource_id, $type, $documentation) {
        $genres = R::dispense("generic_resource");
        $genres->resource_id = $resource_id;
        $genres->type = $type;
        $genres->documentation = $documentation;
        $genres->timestamp = time();
        return R::store($genres);
    }
    
    /**
     * Delete a specific generic resource
     */
    static function deleteGenericResource($package,$resource) {
        return R::exec(
            "DELETE FROM generic_resource
                      WHERE resource_id IN 
                        (SELECT resource.id 
                         FROM package,resource
                         WHERE package.package_name=:package and resource.id = generic_resource.resource_id
                         and resource.resource_name =:resource and resource.package_id = package.id)",
            array(":package" => $package, ":resource" => $resource)
        );
    }
    
    /**
     * Retrieve a specific remote resource
     */
    static function getRemoteResource($package, $resource) {
        return R::getRow(
            "SELECT rem_rec.base_url as url ,rem_rec.package_name as package,
                    rem_rec.resource_name as resource
             FROM   package,remote_resource as rem_rec,resource
             WHERE  package.package_name=:package and resource.resource_name =:resource
                    and package.id = package_id and resource_id = resource.id",
            array(':package' => $package, ':resource' => $resource)
        );
    }
    
    /**
     * Get all remote resource names with their package name
     */
    static function getAllRemoteResourceNames() {
        return R::getAll(
            "SELECT resource.resource_name as res_name, package.package_name
              FROM package,remote_resource,resource 
              WHERE resource.package_id=package.id 
                    and remote_resource.resource_id=resource.id"
        );
    }
    
    /**
     * Store a remote resource
     */
    static function storeRemoteResource($resource_id, $package_name,$resource_name, $base_url) {
        $remres = R::dispense("remote_resource");
        $remres->resource_id = $resource_id;
        $remres->package_name = $package_name;
        $remres->resource_name = $resource_name;
        $remres->base_url = $base_url;
        return R::store($remres);
    }
    
    /**
     * Deletes all remote resources from a specific package
     */
    static function deleteRemotePackage($package) {
        return R::exec(
            "DELETE FROM remote_resource
                    WHERE package_id IN 
                                    (SELECT package.id 
                                     FROM package,resource 
                                     WHERE package.package_name=:package 
                                     and resource_id = resource.id
                                     and package_id = package.id)",
            array(":package" => $package)
        );
    }
    
    /**
     * Deletes a specific remote resource
     */
    static function deleteRemoteResource($package, $resource) {
        return R::exec(
            "DELETE FROM remote_resource
                    WHERE resource_id IN (SELECT resource.id 
                                   FROM package,resource 
                                   WHERE package.package_name=:package and package_id = package.id
                                   and resource_id = resource.id and resource.resource_name =:resource
                                   )",
            array(":package" => $package, ":resource" => $resource)
        );
    }
    
    /**
     * Retrieve a specific package's is
     */
    static function getPackageId($package) {
        return R::getRow(
            "SELECT package.id as id 
             FROM package 
             WHERE package_name=:package_name",
            array(":package_name"=>$package)
        );
    }
    
    /**
     * Store a package
     */
    static function storePackage($package) {
        $newpackage = R::dispense("package");
        $newpackage->package_name = $package;
        $newpackage->timestamp = time();
        return R::store($newpackage);
    }
    
    /**
     * Delete all resources from a package
     */
    static function deletePackageResources($package) {
        return R::exec(
            "DELETE FROM resource 
                    WHERE package_id IN
                    (SELECT id FROM package WHERE package_name=:package)",
            array(":package" => $package)
        );
    }
    
    /**
     * Delete a specific package
     */
    static function deletePackage($package) {
        return R::exec(
            "DELETE FROM package WHERE package_name=:package",
            array(":package" => $package)
        );
    }
    
    /**
     * Retrieve a specific resource's id
     */
    static function getResourceId($package_id, $resource) {
        return R::getRow(
            "SELECT resource.id
             FROM resource, package
             WHERE :package_id = package.id and resource_name =:resource and package_id = package.id",
            array(":package_id" => $package_id, ":resource" => $resource)
        );
    }

    /**
     * Retrieve a resource's id by packagename and resourcename
     */
    static function getResourceIdByName($package,$resource){
        return R::getCell(
            "SELECT resource.id
             FROM resource,package
             WHERE package_name =:package and resource_name =:resource",
            array(":package" => $package, ":resource" => $resource)
        );
    }

    /**
     * Retrieve a specific generic resource id
     */
    static function getGenericResourceId($package,$resource){
        return R::getRow(
            "SELECT generic_resource.id as gen_resource_id
             FROM package,resource,generic_resource
             WHERE package.id = package_id and package_name =:package and resource_name =:resource and resource.id = resource_id",
            array(":package" => $package, ":resource" => $resource)
        );
    }

    /**
     * Get the creation timestamp from a resource
     */
    static function getCreationTime($package,$resource){

        $timestamp = R::getCell(
            "SELECT resource.creation_timestamp as timestamp
             FROM package,resource
             WHERE package.id = resource.package_id and package_name=:package and resource_name=:resource",
            array(":package" => $package,":resource" => $resource)
        );
        if(!$timestamp){
            return 0;
        }
        return (int)$timestamp;
    }

    /**
     * Get the creation timestamp from a package
     */
    static function getPackageCreationTime($package){
        $timestamp = R::getCell(
            "SELECT timestamp
             FROM package
             WHERE package_name =:package",
            array(":package" => $package)
        );
        if(!$timestamp){
            return 0;
        }
        return (int)$timestamp;
    }
    

    /**
     * Get the modification timestamp from a resource
     */
    static function getModificationTime($package,$resource){

        $timestamp = R::getCell(
            "SELECT resource.last_update_timestamp as timestamp
             FROM package,resource
             WHERE package.id = resource.package_id and package_name=:package and resource_name=:resource",
            array(":package" => $package,":resource" => $resource)
        );
        if(!$timestamp){
            return 0;
        }
        return (int)$timestamp;
    }
    

    /**
     * Store a resource
     */
    static function storeResource($package_id, $resource_name, $type) {
        $newResource = R::dispense("resource");
        $newResource->package_id = $package_id;
        $newResource->resource_name = $resource_name;
        $newResource->creation_timestamp = time();
        $newResource->last_update_timestamp = time();
        $newResource->type = $type;
        return R::store($newResource);
    }
    

    /**
     * Delete a specific resource
     */
    static function deleteResource($package, $resource) {
        return R::exec(
            "DELETE FROM resource 
             WHERE resource.resource_name=:resource and package_id IN
                   (SELECT id FROM package WHERE package_name=:package)",
            array(":package" => $package, ":resource" => $resource)
        );
    }
    
    /**
     * Get all published columns of a generic resource
     */
    static function getPublishedColumns($generic_resource_id) {
        return R::getAll(
            "SELECT column_name, is_primary_key,column_name_alias
             FROM published_columns
             WHERE generic_resource_id=:id",
            array(":id" => $generic_resource_id)
        );
    }
   
    /**
     * Store a published column
     */
    static function storePublishedColumn($generic_resource_id, $column_name,$column_alias, $is_primary_key) {
        $db_columns = R::dispense("published_columns");
        $db_columns->generic_resource_id = $generic_resource_id;
        $db_columns->column_name = $column_name;
        $db_columns->is_primary_key = $is_primary_key;
        $db_columns->column_name_alias = $column_alias;
        return R::store($db_columns);
    }

    /**
     * Delete published columns for a certain generic resource
     */
    static function deletePublishedColumns($package,$resource){
        return R::exec(
            "DELETE FROM published_columns
                    WHERE generic_resource_id IN
                    ( 
                      SELECT generic_resource.id 
                      FROM   generic_resource,resource,package
                      WHERE  package_name = :package 
                             and package_id = package.id
                             and resource_name = :resource 
                             and resource_id = resource.id
                    )",
            array(":package" => $package, ":resource" => $resource)
            
        );
    }
   
    /**
     * Get the type of the resource
     */
    static function getResourceType($package,$resource){
        return R::getCell(
            "SELECT type
             FROM resource,package
             WHERE package_name =:package AND resource_name=:resource",
            array(":package" => $package, ":resource" => $resource)
        );
    }

    /**
     * Get all the properties of a strategy
     */
    static function getStrategyProperties($generic_resource_id,$strategy_table){
        return R::getAll(
            "SELECT * 
             FROM $strategy_table
             WHERE gen_resource_id=:gen_res_id",
            array(":gen_res_id" => $generic_resource_id)
        );
    }

    /**
     * Store the metadata provided
     * @param $resource_id int The resource id of the resource
     * @param $resource object The resource object ( in which the meta data is set )
     * @param $metadata array The array in which all allowed meta data properties are contained.
     */
    static function storeMetaData($resource_id,$resource,$metadataArray){
        $metadata = R::dispense("metadata");
        $add = false;
        foreach($metadataArray as $key){
            if(isset($resource->$key) && $resource->$key != ""){
                $metadata->$key = $resource->$key;
                $add = true;
            }
        }
        $metadata->resource_id = $resource_id;
        if($add){
            return R::store($metadata);   
        }
    }

    /**
     * Get the metadata for a certain package resource pair
     */
    static function getMetaData($package,$resource){
        return R::getRow(
            "SELECT *
             FROM metadata
             WHERE resource_id IN (
                       SELECT resource.id 
                       FROM resource,package
                       WHERE resource.resource_name = :resource AND package.package_name = :package
             )",
            array(":package"=>$package,":resource"=>$resource)
        );
    }

    /**
     * Delete the metadata for a certain package resource pair
     */
    static function deleteMetaData($package,$resource){
        return R::exec(
            "DELETE 
             FROM metadata
             WHERE resource_id IN ( 
                       SELECT resource.id 
                       FROM resource,package
                       WHERE resource.resource_name = :resource AND package.package_name = :package 
             )",
            array(":package" => $package, ":resource" => $resource)
        );
    }

    /**
     * Get the API id, with a key who's associated with an ACTIVE status
     * expects a key to be passed 
     */
    static function getAPIId($key){
        return R::getCell(
            "SELECT id 
             FROM api_key 
             WHERE api_key.api_key = :key and api_key.status = 'active'",
            array(":key" => $key)
        );
    }

    /**
     * Get the status from an api key
     */
    static function getApikeyStatus($api_key_id){
        return R::getCell(
            "SELECT status
             FROM api_key
             WHERE id = :api_key_id",
            array(":api_key_id" => $api_key_id)
        );
    }
    
    /**
     * Query the access entry
     */
    static function getAccessEntry($resource_id,$api_key_id){
        return R::getCell(
            "SELECT id
             FROM access_list
             WHERE resource_id =:resource_id and api_key_id = :api_key_id",
            array(":resource_id" => $resource_id , ":api_key_id" => $api_key_id)
        );
    }

    /**
     * Delete all of the access entries according to the resource_id
     */
    static function deleteAccessEntries($package,$resource){
        return R::exec(
            "DELETE 
             FROM access_list
             WHERE resource_id IN (
                   SELECT resource.id 
                   FROM resource,package
                   WHERE resource.resource_name =:resource and package.package_name=:package 
            )",
            array(":package" => $package, ":resource" => $resource)
        );
    }

    /**
     * Get the amount of resource requests to a resource, given an api_key
     */
    static function getRequestsForApiKey($key){
        return R::getAll(
            "SELECT count(1) as requests,package,resource,api_key 
             FROM requests 
             WHERE api_key= :api_key
             GROUP BY package,resource",
            array(":api_key" => $key)
        );
    }
    

    /**
     * Get all of the api_keys for a certain user
     */
    static function getApiKeysForUser($username){
        return R::getAll(
            "SELECT api_key 
             FROM api_key, user_profile 
             WHERE user_profile_id = user_profile.id and name = :username",
            array(":username" => $username)
        );
    }

    /**
     * Get all of the users
     */
    static function getAllApiUsers(){
        return R::getAll(
            "SELECT name
             FROM user_profile"
        );
    }   

    /**
     * Get a user for a certain api key
     */
    static function getApiKeyUser($key){
        return R::getRow(
            "SELECT name
             FROM user_profile,api_key
             WHERE user_profile_id = user_profile.id and api_key =:key",
            array(":key" => $key)
        );
    }
}
?>
