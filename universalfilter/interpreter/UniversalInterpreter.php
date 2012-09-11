<?php

/**
 * The UniversalInterpreter: 
 * Create an instance of this class and give it a query-tree execute the filter.
 *
 * @package The-Datatank/universalfilter/interpreter
 * @copyright (C) 2012 by iRail vzw/asbl
 * @license AGPLv3
 * @author Jeroen Penninck
 */

include_once("universalfilter/interpreter/IInterpreterControl.class.php");
include_once("universalfilter/interpreter/Environment.class.php");

include_once("universalfilter/interpreter/other/DummyUniversalFilterNode.class.php");
include_once("universalfilter/interpreter/sourceusage/SourceUsageData.class.php");
include_once("universalfilter/sourcefilterbinding/ExpectedHeaderNamesAttachment.class.php");

include_once("universalfilter/interpreter/cloning/FilterTreeCloner.class.php");

include_once("universalfilter/interpreter/executers/UniversalFilterExecuters.php");

include_once("universalfilter/interpreter/optimizer/UniversalOptimizer.class.php");

//debug
include_once("universalfilter/interpreter/debugging/TreePrinter.class.php");


class UniversalInterpreter implements IInterpreterControl{
    
    private $executers;
    private $tablemanager;
    
    /**
     * Are nested querys allowed?
     * true = yes, they are allowed.
     * false = no, throw an exception if you try to use them...
     * 
     * @var boolean 
     */
    public static $ALLOW_NESTED_QUERYS=false;
    
    /**
     * For debugging purposses, would you like to see debug information about execution of querys on the source?
     * @var boolean 
     */
    public static $DEBUG_QUERY_ON_SOURCE_EXECUTION= false;
    
    /**
     * Constructor, fill the executer-class map.
     */
    public function __construct($tablemanager) {
        $this->tablemanager=$tablemanager;
        
        $this->executers = array(
            "IDENTIFIER" => "IdentifierExecuter",
            "CONSTANT" => "ConstantExecuter",
            "FILTERCOLUMN" => "ColumnSelectionFilterExecuter",
            "FILTEREXPRESSION" => "FilterByExpressionExecuter",
            "DATAGROUPER" => "DataGrouperExecuter",
            "TABLEALIAS" => "TableAliasExecuter",
            "FILTERDISTINCT" => "DistinctFilterExecuter",
            "EXTERNALLY_CALCULATED_NODE" => "ExternallyCalculatedFilterNodeExecuter",
            UnaryFunction::$FUNCTION_UNARY_UPPERCASE => "UnaryFunctionUppercaseExecuter",
            UnaryFunction::$FUNCTION_UNARY_LOWERCASE => "UnaryFunctionLowercaseExecuter",
            UnaryFunction::$FUNCTION_UNARY_STRINGLENGTH => "UnaryFunctionStringLengthExecuter",
            UnaryFunction::$FUNCTION_UNARY_ROUND => "UnaryFunctionRoundExecuter",
            UnaryFunction::$FUNCTION_UNARY_ISNULL => "UnaryFunctionIsNullExecuter",
            UnaryFunction::$FUNCTION_UNARY_NOT => "UnaryFunctionNotExecuter",
            UnaryFunction::$FUNCTION_UNARY_SIN => "UnaryFunctionSinExecuter",
            UnaryFunction::$FUNCTION_UNARY_COS => "UnaryFunctionCosExecuter",
            UnaryFunction::$FUNCTION_UNARY_TAN => "UnaryFunctionTanExecuter",
            UnaryFunction::$FUNCTION_UNARY_ASIN => "UnaryFunctionAsinExecuter",
            UnaryFunction::$FUNCTION_UNARY_ACOS => "UnaryFunctionAcosExecuter",
            UnaryFunction::$FUNCTION_UNARY_ATAN => "UnaryFunctionAtanExecuter",
            UnaryFunction::$FUNCTION_UNARY_SQRT => "UnaryFunctionSqrtExecuter",
            UnaryFunction::$FUNCTION_UNARY_ABS => "UnaryFunctionAbsExecuter",
            UnaryFunction::$FUNCTION_UNARY_FLOOR => "UnaryFunctionFloorExecuter",
            UnaryFunction::$FUNCTION_UNARY_CEIL => "UnaryFunctionCeilExecuter",
            UnaryFunction::$FUNCTION_UNARY_EXP => "UnaryFunctionExpExecuter",
            UnaryFunction::$FUNCTION_UNARY_LOG => "UnaryFunctionLogExecuter",
            BinaryFunction::$FUNCTION_BINARY_PLUS => "BinaryFunctionPlusExecuter",
            BinaryFunction::$FUNCTION_BINARY_MINUS => "BinaryFunctionMinusExecuter",
            BinaryFunction::$FUNCTION_BINARY_MULTIPLY => "BinaryFunctionMultiplyExecuter",
            BinaryFunction::$FUNCTION_BINARY_DIVIDE => "BinaryFunctionDivideExecuter",
            BinaryFunction::$FUNCTION_BINARY_COMPARE_EQUAL => "BinaryFunctionEqualityExecuter",
            BinaryFunction::$FUNCTION_BINARY_COMPARE_SMALLER_THAN => "BinaryFunctionSmallerExecuter",
            BinaryFunction::$FUNCTION_BINARY_COMPARE_LARGER_THAN => "BinaryFunctionLargerExecuter",
            BinaryFunction::$FUNCTION_BINARY_COMPARE_SMALLER_OR_EQUAL_THAN => "BinaryFunctionSmallerEqualExecuter",
            BinaryFunction::$FUNCTION_BINARY_COMPARE_LARGER_OR_EQUAL_THAN => "BinaryFunctionLargerEqualExecuter",
            BinaryFunction::$FUNCTION_BINARY_COMPARE_NOTEQUAL => "BinaryFunctionNotEqualExecuter",
            BinaryFunction::$FUNCTION_BINARY_OR => "BinaryFunctionOrExecuter",
            BinaryFunction::$FUNCTION_BINARY_AND => "BinaryFunctionAndExecuter",
            BinaryFunction::$FUNCTION_BINARY_ATAN2 => "BinaryFunctionAtan2Executer",
            BinaryFunction::$FUNCTION_BINARY_LOG => "BinaryFunctionLogExecuter",
            BinaryFunction::$FUNCTION_BINARY_POW => "BinaryFunctionPowExecuter",
            BinaryFunction::$FUNCTION_BINARY_MATCH_REGEX => "BinaryFunctionMatchRegexExecuter",
            BinaryFunction::$FUNCTION_BINARY_CONCAT => "BinaryFunctionConcatExecuter",
            TernaryFunction::$FUNCTION_TERNARY_SUBSTRING => "TernaryFunctionSubstringExecuter",
            TernaryFunction::$FUNCTION_TERNARY_REGEX_REPLACE => "TernaryFunctionRegexReplacementExecuter",
            AggregatorFunction::$AGGREGATOR_AVG => "AverageAggregatorExecuter",
            AggregatorFunction::$AGGREGATOR_COUNT => "CountAggregatorExecuter",
            AggregatorFunction::$AGGREGATOR_FIRST => "FirstAggregatorExecuter",
            AggregatorFunction::$AGGREGATOR_LAST => "LastAggregatorExecuter",
            AggregatorFunction::$AGGREGATOR_MAX => "MaxAggregatorExecuter",
            AggregatorFunction::$AGGREGATOR_MIN => "MinAggregatorExecuter",
            AggregatorFunction::$AGGREGATOR_SUM => "SumAggregatorExecuter",
            CheckInFunction::$FUNCTION_IN_LIST => "CheckInFunctionExecuter"
        );
    }
    
    public function findExecuterFor(UniversalFilterNode $filternode) {
        return new $this->executers[$filternode->getType()]();
    }
    
    public function getTableManager() {
        return $this->tablemanager;
    }
    
    public function interpret(UniversalFilterNode $originaltree){
        if(UniversalInterpreter::$DEBUG_QUERY_ON_SOURCE_EXECUTION){
            $printer = new TreePrinter();
            echo "<h2>Original Query:</h2>";
            $printer->printString($originaltree);
        }
        
        //CLONE QUERY (because we will modify it... and the caller might want to keep the original query)
        $cloner = new FilterTreeCloner();
        $clonedtree = $cloner->deepCopyTree($originaltree);
        
        //OPTIMIZE
        $optimizer = new UniversalOptimizer();
        
        $tree = $optimizer->optimize($clonedtree);
        
        
        //INITIAL ENVIRONMENT... is empty
        $emptyEnv = new Environment();
        $emptyEnv->setTable(new UniversalFilterTable(new UniversalFilterTableHeader(array(), true, false), new UniversalFilterTableContent()));
        
        
        //CALCULATE HEADER FIRST TIME + QUERY SYNTAX DETECTION
        // calculate the header already once on the original query.
        // it can throw errors...
        $executer = $this->findExecuterFor($tree);
        $executer->initExpression($tree, $emptyEnv, $this, false);
        
        
        //EXECUTE PARTS ON SOURCE
        
        // - modify the headers to include column names
        $executer->modififyFiltersWithHeaderInformation();
        
        // - calculate single source usages
        $rootDummyFilter = new DummyUniversalFilterNode($tree);
        $singleSourceUsages = $executer->filterSingleSourceUsages($rootDummyFilter, 0);
        
        // - calculated... now execute them on the sources... AND BUILD A NEW QUERY
        foreach($singleSourceUsages as $singleSource){
            // - unpack data
            $filterSourceNode = $singleSource->getFilterSourceNode();
            $filterParentNode = $singleSource->getFilterParentNode();
            $filterParentSourceIndex = $singleSource->getFilterParentSourceIndex();
            $sourceId = $singleSource->getSourceId();
            
            // debug
            if(UniversalInterpreter::$DEBUG_QUERY_ON_SOURCE_EXECUTION){
                $printer = new TreePrinter();
                echo "<h2>This is given to the source with id \"".$sourceId."\":</h2>";
                $printer->printString($filterSourceNode);
            }
            
            // - do it
            $newQuery = $this->getTableManager()->runFilterOnSource($filterSourceNode, $sourceId);
            $filterParentNode->setSource($newQuery, $filterParentSourceIndex);
        }
        
        
        $tree= $rootDummyFilter->getSource();
        
        
        //EXECUTE (for real this time)
        $executer = $this->findExecuterFor($tree);
        $executer->initExpression($tree, $emptyEnv, $this, false);
        
        //get the table, in two steps
        $header = $executer->getExpressionHeader();
        
        $content = $executer->evaluateAsExpression();
        
        $executer->cleanUp();
        
        //RETURN
        return new UniversalFilterTable($header, $content);
        
        //CLEANUP -> when you don't need the data anymore
        //$content->tryDestroyTable();
    }
}

?>
