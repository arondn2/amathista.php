<?php
/**
 * Amathista - PHP Framework
 *
 * @author Alex J. Rondón <arondn2@gmail.com>
 * 
 */

// PENDIENTE Documentar
class AmClauseOrderByItem extends AmClause{

  protected
    $field = null,
    $dir = null;

  public function __construct(array $data = array()){
    parent::__construct($data);

    $this->dir = strtoupper($this->dir);

    if(!is_string($this->field) || empty($this->field)){
      throw Am::e('AMSCHEME_FIELD_INVALID', var_export($this->field, true), 'ORDER BY');
    }

    // SQLSQLSQL
    if(!in_array($this->dir, array('ASC', 'DESC'))){
      throw Am::e('AMSCHEME_DIR_INVALID', $this->dir, $this->field, 'ORDER BY');
    }

  }

  public function getDir(){

    return $this->dir;

  }

  public function getField(){

    return $this->field;

  }

  public function sql(){

    $sql = $this->scheme->nameWrapperAndRealScapeComplete($this->field);

    // SQLSQLSQL
    return "{$sql} {$this->dir}";

  }

}