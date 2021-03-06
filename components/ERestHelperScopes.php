<?php
class ERestHelperScopes extends CActiveRecordBehavior
{

  public function limit($limit)
  {
    $this->Owner->getDbCriteria()->mergeWith(array(
      'limit'=>$limit
    ));
    return $this->Owner;
  }

  public function offset($offset)
    {
    $this->Owner->getDbCriteria()->mergeWith(array(
      'offset'=>$offset
    ));
    return $this->Owner;
  }

  public function orderBy($field, $dir='ASC')
  {
    if(empty($field)) return $this->Owner;

    if(!is_array($orderListItems = CJSON::decode($field)))
    {
       $this->Owner->getDbCriteria()->mergeWith(array(
        'order'=>$this->getSortSQL($field, $dir)
      ));
      return $this->Owner;
    }
    else
    {
      $orderByStr = "";
      foreach($orderListItems as $orderListItem)
        $orderByStr .= ((!empty($orderByStr))? ", " : "") . $this->getSortSQL($orderListItem['property'], $orderListItem['direction']);
      
      $this->Owner->getDbCriteria()->mergeWith(array(
        'order'=>$orderByStr
      ));
      return $this->Owner;
    }
  }

  public function filter($filter)
  {
    if(empty($filter)) return $this->Owner;
    
    if(!is_array($filter))
      $filterItems = CJSON::decode($filter);
    else
      $filterItems = $filter;
  
    $query = "";
    $params = array();
    foreach($filterItems as $filterItem)
    {
      if(!is_null($filterItem['property']))
      {
        $cType = $this->getFilterCType($filterItem['property']);
        
        if($cType == 'text' || $cType == 'string')
        {
          
          $compare = " LIKE :" . $filterItem['property'];
          $params[(":" . $filterItem['property'])] = '%' . $filterItem['value'] . '%';
        }
        else
        {
          $compare = " = :" . $filterItem['property'];
          $params[(":" . $filterItem['property'])] = $filterItem['value'];
        }

        $query .= (empty($query)? "(": " AND ") . $this->getFilterAlias($filterItem['property']) . '.' . $filterItem['property'] . $compare;
      }
    }
    if(empty($query)) return $this->Owner;

    $query .= ")";
      

    $this->Owner->getDbCriteria()->mergeWith(array(
        'condition'=>$query, 'params'=>$params
      ));
    return $this->Owner;
  }

  private function getFilterCType($property)
  {
    if($this->Owner->hasAttribute($property))
      return $this->Owner->metaData->columns[$property]->type;
    
    return 'text';
  }

  private function getFilterAlias($property)
  {
    return $this->Owner->getTableAlias(false, false);
  }

  private function getSortSQL($field, $dir='ASC')
  {
    return $this->Owner->getTableAlias(false, false) . ".$field $dir";
	}	

}
