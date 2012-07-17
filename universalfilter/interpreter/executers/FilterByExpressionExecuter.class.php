<?php

/**
 * Executes the FilterByExpression filter
 * 
 * @package The-Datatank/universalfilter/interpreter/executers
 * @copyright (C) 2012 We Open Data
 * @license AGPLv3
 * @author Jeroen Penninck
 */
class FilterByExpressionExecuter extends UniversalFilterNodeExecuter {
    
    public function initExpression(UniversalFilterNode $filter, Environment $topenv, IInterpreter $interpreter) {
        throw new Exception("A filterByExpression can not be evaluated.");
    }
    
    public function execute(UniversalFilterNode $filter, IInterpreter $interpreter) {
        //get source environment
        $executer = $interpreter->findExecuterFor($filter->getSource());
        $environment = $executer->execute($filter->getSource(), $interpreter);
        
        //the table generated by the last executer
        $sourcetable = $environment->getTable();
        
        // create a new environment to give each to the expression (once for each row)
        $newEnv=$environment->newModifiableEnvironment();
        
        //find the executer that can execute the root node
        $expr = $filter->getExpression();
        $exprexec = $interpreter->findExecuterFor($expr);
        
        //do it for each row, so create a new header which tells that
        $newHeader = $sourcetable->getHeader()->cloneHeader();
        $newHeader->setIsSingleRowByConstruction(true);
        
        //calculate the header
        $singleRowTable = new UniversalFilterTable($newHeader, new UniversalFilterTableContent());//empty table
        $newEnv->setTable($singleRowTable);
        
        $exprexec->initExpression($expr, $newEnv, $interpreter);
        
        $header = $exprexec->getExpressionHeader();
        
        if(!$header->isSingleCellByConstruction()){
            throw new Exception("Not a valid expression to filter on. It returns more than one value!");
        }
        
        
        $filteredRows = new UniversalFilterTableContent();
        
        // a table with one row
        $singleRowContent = new UniversalFilterTableContent();
        $singleRowContent->addRow(new UniversalFilterTableContentRow());
        $singleRowTable = new UniversalFilterTable($newHeader, $singleRowContent);
        $newEnv->setTable($singleRowTable);//works because header stays the same. Only rows change, but those should not be accessed in init.
        
        //loop all rows
        for ($index = 0; $index < $sourcetable->getContent()->getRowCount(); $index++) {
            $row = $sourcetable->getContent()->getRow($index);
            
            // make a table with only this row
            $singleRowContent->setRow(0, $row);
            
            //request the header and content
            $anwser = $exprexec->evaluateAsExpression();//
            
            //if the expression evaluates to true, then add the row
            if($anwser->getCellValue($header->getColumnId())=="true"){
                $filteredRows->addRow($row);
            }
        }
        
        //the new table
        $newtable = new UniversalFilterTable($sourcetable->getHeader(), $filteredRows);
        
        //add it to the environment
        $environment->setTable($newtable);
        
        return $environment;
    }
}

?>
