<?php

/**
 * This file contains the RDF/JSON formatter.
 * @package The-Datatank/formatters
 * @copyright (C) 2011 by iRail vzw/asbl
 * @license AGPLv3
 * @author Miel Vander Sande
 */
class Rdf_Json extends AFormatter {

    public function __construct($rootname, $objectToPrint) {
        parent::__construct($rootname, $objectToPrint);
    }

    protected function printBody() {
        
    }

    protected function printHeader() {
        
    }

    public function printAll() {
        $model = $this->objectToPrint;

        // Import Package Syntax
	include_once(RDFAPI_INCLUDE_DIR.PACKAGE_SYNTAX_JSON);
        
        $ser = new JsonSerializer();
        
        //Serializer only works on MemModel class, so we need to retrieve the underlying MemModel
        if (is_a($model, 'ResModel'))
            $model = $model->getModel();
        if (is_a($model, 'DbModel'))
            $model = $model->getMemModel();
        
        $rdf = $ser->serialize($model);
        
        echo $rdf;
    }

}

?>
