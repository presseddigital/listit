<?php
namespace presseddigital\listit\db;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\elements\User;

use presseddigital\listit\db\SubscriptionQuery;

class ElementQuery extends SubscriptionQuery
{
    // Properties
    // =========================================================================

    public $criteria;

    // Public Methods
    // =========================================================================

    public function __set($name, $value)
    {
        switch ($name)
        {
            case 'criteria':
            {
                $this->$name($value);
                break;
            }
            default:
            {
                parent::__set($name, $value);
            }
        }
    }

    public function criteria(array $criteria = null)
    {
        $this->criteria = $criteria;
        return $this;
    }

    // Execution functions
    // -------------------------------------------------------------------------

    public function ids($db = null, $column = null)
    {
        if($this->criteria)
        {
            $query = $this->_getElementQuery($db);
            return $query ? $query->ids() : [];
        }
        return $this->_getElementIds($db);
    }

    public function all($db = null)
    {
        $query = $this->_getElementQuery($db);
        return $query ? $query->all() : [];
    }

    public function one($db = null)
    {
        $query = $this->_getElementQuery($db);
        return $query ? $query->one() : null;
    }

    public function exists($db = null)
    {
        $query = $this->_getElementQuery($db);
        return $query ? $query->exists() : false;
    }

    public function nth(int $n, $db = null)
    {
        $query = $this->_getElementQuery($db);
        return $query ? $query->nth() : null;
    }

    public function count($q = '*', $db = null)
    {
        $query = $this->_getElementQuery($db);
        return $query ? $query->count() : 0;
    }

    public function column($db = null)
    {
        $query = $this->_getElementQuery($db);
        return $query ? $query->column() : [];
    }

    public function scalar($db = null)
    {
        $query = $this->_getElementQuery($db);
        return $query ? $query->scalar() : false;
    }

    public function sum($q, $db = null)
    {
        $query = $this->_getElementQuery($db);
        return $query ? $query->sum() : null;
    }

    public function average($q, $db = null)
    {
        $query = $this->_getElementQuery($db);
        return $query ? $query->average() : null;
    }

    public function min($q, $db = null)
    {
        $query = $this->_getElementQuery($db);
        return $query ? $query->min() : null;
    }

    public function max($q, $db = null)
    {
        $query = $this->_getElementQuery($db);
        return $query ? $query->max() : null;
    }

    // Private Methods
    // -------------------------------------------------------------------------

    private function _getElementIds($db = null)
    {
        return array_values(array_filter(parent::ids($db, 'elementId')));
    }

    private function _getElementQuery($db = null)
    {
        $ids = $this->_getElementIds($db);
        if($ids)
        {
            // Get the supplied type
            $type = $this->type;
            if(!$type)
            {
                $type = (new Query())
                    ->select('type')
                    ->from(CraftTable::ELEMENTS)
                    ->where(['id' => $ids[0]])
                    ->scalar();
            }

            $query = (new $type)::find();
            if($this->criteria)
            {
                Craft::configure($query, $this->criteria);
            }
            $query->id($ids);
            return $query;
        }
        return null;

        // TODO: @sam - This could be a mix of element types, or maybe not even have an element
        //            - Need to handle and return the ids here!
        //            - Below is how we handled it in the original version of listit

        // $elementsToReturn = $elementIds;
        //
        // $elements = (new Query())
        //     ->select(['id', 'type'])
        //     ->from([ElementRecord::tableName()])
        //     ->where([
        //         'id' => $elementIds,
        //     ])
        //     ->all();
        //
        // $elementIdsByType = [];
        // foreach ($elements as $element)
        // {
        //     $elementIdsByType[$element['type']][] = $element['id'];
        // }
        // foreach ($elementIdsByType as $elementType => $ids)
        // {
        //     $criteria = ['id' => $ids];
        //     $elements = $this->_getElementQuery($elementType, $criteria)->all();
        //
        //     foreach ($elements as $element)
        //     {
        //         $key = array_search($element->id, $elementIds);
        //         $elementsToReturn[$key] = $element;
        //     }
        // }
        //
        // return $elementsToReturn;


    }

}
