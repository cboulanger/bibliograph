<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2014 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

/**
 * ArrayList class
 * from http://www.phpclasses.org/browse/package/1169.html
 * @author Tim Anlauf <schranzistorradio@gmx.de>
 *
 * adapted for  for qcl library:
 * - small documentation cleanup and additions
 * - PHP 5 upgrade
 *
 * @todo Implement Iterator interface
 * @todo Use PSL Class instead?
 *
 */
class ArrayList
{

  /**
   * Array, representing the arrayList
   * @var array
   */
  protected $arrayList;

  /**
   * Pointer variable. used to detect the last element of the list in hasNext() method.
   * @var int
   */
  protected $pointer=0;

  /**
   * Constructor
   * Constructs a new list. The Parameter $arr is optional. If set an ArrayList
   * with the elements in array is created. Otherwise a empty list is constructed.
   * @param array[optional] $arr - one dimensional array
   */
  public function __construct( $arr=array() )
  {
    qcl_assert_array( $arr );
    $this->arrayList = $arr;
  }

  /**
   * Inserts the specified element at the specified position in this list.
   * @param int $index
   * @param $obj
   */
  public function addToPos($index, $obj)
  {
    qcl_assert_integer( $index );
    $this->arrayList[$index] = $obj;
  }

  /**
   * Appends the specified element to the end of this list.
   * @param mixed $obj
   */
  public function add($obj)
  {
    array_push($this->arrayList, $obj);
  }

  /**
   * Appends all of the elements in the specified Array to the end of this list
   * @param mixed $elem ArrayList or array
   * @throws InvalidArgumentException
   */
  public function addAll($elem)
  {
    if ( is_a($elem,"ArrayList") )
    {
      $arr = $elem->toArray();
    }
    elseif ( is_array($elem) )
    {
      $arr = $elem;
    }
    else
    {
      throw new InvalidArgumentException("Argument must be an ArrayList or array.");
    }
    $this->arrayList = array_merge($this->arrayList, $arr);
  }

  /**
   * Removes all of the elements from this list.
   */
  public function clear()
  {
    $this->arrayList = array();
  }

  /**
   * Returns true if this list contains the specified element.
   * @param mixed $obj
   * @return boolean
   */
  public function contains($obj)
  {
    return in_array($obj, $this->arrayList);
  }

  /**
   * Returns the element at the specified position in this list.
   * @param int $index
   */
  public function get($index)
  {
    qcl_assert_integer( $index );
    return $this->arrayList[$index];
  }

  /**
   * Searches for the first occurence of the given argument.
   * If the element isn´t found, -1 is returned
   * @param obj
   * @return integer
   */
  public function indexOf($obj)
  {
    while (list ($key, $val) = each ($this->arrayList) )
    if ($obj == $val) return $key;
    return -1;
  }

  /**
   * Tests if this list has no elements.
   * @return boolean
   **/
  public function isEmpty()
  {
    if ( count($this->arrayList) == 0) return true;
    else return false;
  }

  /**
   * Returns the index of the last occurrence of the specified object in this list.
   * @param mixed $obj
   * @return int
   **/
  public function lastIndexOf($obj)
  {
    return array_search($obj, $this->arrayList);
  }

  /**
   * removes the element at the specified position in this list.
   * @param index
   **/
  public function remove($index)
  {
    qcl_assert_integer( $index );
    $newArrayList = array();
    for ($i=0; $i < $this->size(); $i++)
    {
      if ($index != $i)
      {
        $newArrayList[] = $this->get($i);
      }
    }
    $this->arrayList = $newArrayList;
  }

  /**
   * Removes from this List all of the elements whose index is between fromIndex, inclusive and toIndex, exclusive.
   * @param $fromIndex
   * @param int $toIndex
   * @internal param int $formIndex
   */
  public function removeRange($fromIndex, $toIndex)
  {
    qcl_assert_integer( $fromIndex );
    qcl_assert_integer( $toIndex );
    $newArrayList = array();

    for ($i=0; $i < $this->size(); $i++)
    {
      if ($i < $fromIndex || $i > $toIndex )
      {
        $newArrayList[] = $this->get($i);
      }
    }
    $this->arrayList = $newArrayList;
  }

  /**
   * Returns the number of elements in this list.
   * return integer
   */
  public function size()
  {
    return count($this->arrayList);
  }

  /**
   * Sorts the list in alphabetical order. Keys are not kept in position.
   */
  public function sort()
  {
    sort($this->arrayList);
  }

  /**
   * Returns an array containing all of the elements in this list in the correct order.
   * @return array
   */
  public function toArray()
  {
    return $this->arrayList;
  }


  /* Iterator Methods */


  /**
   * Returns true if the list has more elements. Advice : excecute reset method before
   * using this method
   * @return boolean
   **/
  public function hasNext()
  {

    if ($this->pointer < $this->size() )
    {
      return false;
    }
    return true;
  }

  /**
   * Set the pointer of the list to the first element
   */
  public function reset()
  {
    reset($this->arrayList);
    $this->pointer=0;
  }

  /**
   * Set the pointer of the next element of the list
   * @return mixed current element
   */
  public function next()
  {
    $cur = current($this->arrayList);
    next($this->arrayList);
    $this->pointer++;
    return $cur;
  }

  /**
   * Joins array elemnt with given glue character(s)
   * @param string $glue
   * @return string
   */
  public function join( $glue )
  {
    return implode($glue,$this->arrayList);
  }
}
?>