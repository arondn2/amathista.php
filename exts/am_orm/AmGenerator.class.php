<?php

/**
 * Clase para generar clases de los modeles del ORM
 */

final class AmGenerator{

  // Generar clase base para un model
  public final static function classModelBase(AmTable $table){

    $existMethods = get_class_methods("AmModel");
    $fields = array_keys((array)$table->getFields());
    $newMethods = array();
    $lines = array();

    // Agregar métodos GET para cada campos

    $lines[] = "class {$table->getClassNameModelBase()} extends AmModel{\n";

    $lines[] = "  protected static";
    $lines[] = "    \$sourceName = \"{$table->getSource()->getName()}\",";
    $lines[] = "    \$tableName  = \"{$table->getTableName()}\";\n";

    // Add getters methods
    $lines[] = "  // GETTERS";
    foreach($fields as $attr){
      $methodName = "get_{$attr}";
      $prefix = in_array($methodName, $existMethods)? "//" : "";
      $existMethods[] = $methodName;
      $lines[] = "  {$prefix}public function {$methodName}(){ return \$this->{$attr}; }";
    }
    $lines[] = "";

    // Add setters methods
    $lines[] = "  // SETTERS";
    foreach($fields as $attr){
      $methodName = "set_{$attr}";
      $prefix = in_array($methodName, $existMethods)? "//" : "";
      $existMethods[] = $methodName;
      $lines[] = "  {$prefix}public function {$methodName}(\$value){ \$this->$attr = \$value; return \$this; }";
    }
    $lines[] = "";

    // Add references to this class
    $lines[] = "  // REFERENCES BY";
    foreach(array_keys((array)$table->getReferencesBy()) as $relation){
      $prefix = in_array($relation, $existMethods)? "//" : "";
      $existMethods[] = $relation;
      $lines[] = "  {$prefix}public function $relation(){ return \$this->getTable()->getReferencesTo()->{$relation}->getQuery(\$this); }";
    }
    $lines[] = "";

    // Add references to other class
    $lines[] = "  // REFERENCES TO";
    foreach(array_keys((array)$table->getReferencesTo()) as $relation){
      $prefix = in_array($relation, $existMethods)? "//" : "";
      $existMethods[] = $relation;
      $lines[] = "  {$prefix}public function $relation(){ return \$this->getTable()->getReferencesTo()->{$relation}->getQuery(\$this)->getRow(); }";
    }
    $lines[] = "";

    // Method for customization model
    $lines[] = "  // METHOD FOR INIT MODEL";
    $lines[] = "  protected function initModel(){";
    $lines[] = "    parent::initModel();\n";

    // Add vaidators for fields
    foreach($table->getFields() as $f){
      if($f->isAutoIncrement()) continue;

      $validators = array();
      $type       = $f->getType();
      $len        = $f->getLen();
      $fieldName  = $f->getName();

      // Integer validator, dates, times and Bit validator
      if(in_array($type, array("integer", "bit", "date", "datetime", "time")))
        $validators[] = "    \$this->setValidator(\"{$fieldName}\", \"{$type}\");";

      // If have validate strlen of value.
      if(in_array($type, array("char", "varchar", "bit")))
        $validators[] = "    \$this->setValidator(\"{$fieldName}\", \"max_length\",".
                              "array(\"max\" => $len));";

      // To integer fields add range validator
      if($type == "integer"){
        $max = pow(256, $len);
        $min = $f->isUnsigned() ? 0 : -($max>>1);
        $max = $min + $max - 1;
        $prefix = is_int($min) && is_int($max)? "" : "// ";
        // if the max limit is integer yet then add validator
        $validators[] = "    {$prefix}\$this->setValidator(\"{$fieldName}\", \"range\", ".
                            " array(\"min\" => $min, \"max\" => $max));";

      // To text fiels add strlen validator
      }elseif($type == "text"){
        $len = pow(256, $len) - 1;
        $validators[] = "    \$this->setValidator(\"{$fieldName}\", \"max_length\", ".
                              "array(\"max\" => $len));";

      // To decimal fiels add float validator
      }elseif($type == "decimal"){
        $precision = $f->getPrecision();
        $decimals = $f->getScale();
        $validators[] = "    \$this->setValidator(\"{$fieldName}\", \"float\", ".
                              "array(\"precision\" => $precision, \"decimals\" => $decimals));";
      }

      // If is a PK not auto increment adde unique validator
      if($f->isPrimaryKey() && count($table->getPks()) == 1)
        $validators[] = "    \$this->setValidator(\"{$fieldName}\", \"unique\");";

      // Add notnull validator if no added any validators
      if(empty($validators) && !$f->allowNull())
        $validators[] = "    \$this->setValidator(\"{$fieldName}\", \"null\");";

      // Add validators if has any
      if(!empty($validators)){
        $lines[] = "    // {$fieldName}";
        $lines = array_merge($lines, $validators);
        $lines[] = "";
      }

      switch ($type){
        case "year":
        break;
      }

    }

    // Add validator of relations
    $validators = array();
    foreach($table->getReferencesTo() as $r){
      $cols = $r->getColumns();
      if(count($cols) == 1){
        $colName = array_keys($cols);
        $f = $table->getField($colName[0]);
        if(!$f->allowNull())
          $validators[] = "    \$this->setValidator(\"{$f->getName()}\", \"in_query\", array(\"query\" => AmORM::table(\"{$r->getTable()}\", \"{$table->getSource()->getName()}\")->all(), \"field\" => \"{$cols[$colName[0]]}\"));";
      }
    }

    // Add validators if has any
    if(!empty($validators)){
      $lines[] = "    // RELATIONS";
      $lines = array_merge($lines, $validators);
      $lines[] = "";
    }

    // Add validator of uniques group values
    $validators = array();
    foreach($table->getUniques() as $constraint => $cols){
      if(count($cols) > 1){
        $cols = implode("\", \"", $cols);
        $validators[] = "    \$this->setValidator(\"{$constraint}\", \"unique\", ".
                              "array(\"fields\" => array(\"$cols\")));";
      }
    }

    // Add validators if has any
    if(!empty($validators)){
      $lines[] = "    // UNIQUE";
      $lines = array_merge($lines, $validators);
      $lines[] = "";
    }

    $lines[] = "  }\n";

    // Method to get table of model
    $lines[] = "  // GET TABLE OF MODEL";
    $lines[] = "  public static function me(){";
    $lines[] = "    return AmORM::table(\"{$table->getTableName()}\", \"{$table->getSource()->getName()}\");";
    $lines[] = "  }\n";

    $lines[] = "}";

    // Preparacion de los metodos Get
    return implode("\n", $lines);

  }

}
