<?php
namespace presseddigital\listit\db;

use presseddigital\listit\Listit;
use presseddigital\listit\db\SubscriptionQuery;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\db\QueryAbortedException;
use craft\elements\User;

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

    // Query
    // -------------------------------------------------------------------------

    public function prepare($builder)
    {
        // Need to ensure there is a list to get elements (if we are sticking to single elements per request)
        if(!$this->list || $this->list == '*')
        {
            throw new QueryAbortedException();
        }
        return parent::prepare($builder);
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
            if(!$this->type)
            {
                $list = Listit::$plugin->getLists()->getListByHandle($this->list);
                $this->type = $list->elementType ?? null;
            }

            if(!$this->type) return null;

            $query = $this->type::find();
            if($this->criteria)
            {
                Craft::configure($query, $this->criteria);
            }
            $query->id($ids);
            return $query;
        }
        return null;
    }

}
