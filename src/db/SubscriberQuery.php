<?php
namespace presseddigital\listit\db;

use Craft;
use craft\elements\User;
use presseddigital\listit\db\SubscriptionQuery;

class SubscriberQuery extends SubscriptionQuery
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
            return $query = $this->_getSubscriberQuery($db) ? $query->ids() : [];
        }
        return $this->_getSubscriberIds($db);
    }

    public function all($db = null)
    {
        $query = $this->_getSubscriberQuery($db);
        return $query ? $query->all() : [];
    }

    public function one($db = null)
    {
        $query = $this->_getSubscriberQuery($db);
        return $query ? $query->one() : null;
    }

    public function exists($db = null)
    {
        $query = $this->_getSubscriberQuery($db);
        return $query ? $query->exists() : false;
    }

    public function nth(int $n, $db = null)
    {
        $query = $this->_getSubscriberQuery($db);
        return $query ? $query->nth() : null;
    }

    public function count($q = '*', $db = null)
    {
        $query = $this->_getSubscriberQuery($db);
        return $query ? $query->count() : 0;
    }

    public function column($db = null)
    {
        $query = $this->_getSubscriberQuery($db);
        return $query ? $query->column() : [];
    }

    public function scalar($db = null)
    {
        $query = $this->_getSubscriberQuery($db);
        return $query ? $query->scalar() : false;
    }

    public function sum($q, $db = null)
    {
        $query = $this->_getSubscriberQuery($db);
        return $query ? $query->sum() : null;
    }

    public function average($q, $db = null)
    {
        $query = $this->_getSubscriberQuery($db);
        return $query ? $query->average() : null;
    }

    public function min($q, $db = null)
    {
        $query = $this->_getSubscriberQuery($db);
        return $query ? $query->min() : null;
    }

    public function max($q, $db = null)
    {
        $query = $this->_getSubscriberQuery($db);
        return $query ? $query->max() : null;
    }

    // Private Methods
    // -------------------------------------------------------------------------

    private function _getSubscriberIds($db = null)
    {
        return array_values(array_filter(parent::ids($db, 'subscriberId')));
    }

    private function _getSubscriberQuery($db = null)
    {
        $ids = $this->_getSubscriberIds($db);
        if($ids)
        {
            $query = User::find();
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
