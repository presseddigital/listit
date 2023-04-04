<?php

namespace presseddigital\listit\helpers;

use craft\commerce\elements\Product;
use craft\commerce\elements\Subscription;
use craft\commerce\elements\Variant;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\MatrixBlock;
use craft\elements\Tag;
use craft\elements\User;

class ElementHelper extends \craft\helpers\ElementHelper
{
    // Public Methods
    // =========================================================================

    public static function normalizeClassName(string $value = '')
    {
        switch (true) {
            case in_array(strtolower($value), ['entry', 'entries']):
            {
                return Entry::class;
            }
            case in_array(strtolower($value), ['asset', 'assets']):
            {
                return Asset::class;
            }
            case in_array(strtolower($value), ['user', 'users']):
            {
                return User::class;
            }
            case in_array(strtolower($value), ['category', 'categories']):
            {
                return Category::class;
            }
            case in_array(strtolower($value), ['tag', 'tags']):
            {
                return Tag::class;
            }
            case in_array(strtolower($value), ['globalset', 'global', 'globalsets', 'globals']):
            {
                return GlobalSet::class;
            }
            case in_array(strtolower($value), ['matrixblock', 'matrixblocks', 'matrix']):
            {
                return MatrixBlock::class;
            }
            case in_array(strtolower($value), ['product', 'products']):
            {
                return Product::class;
            }
            case in_array(strtolower($value), ['variant', 'variants']):
            {
                return Variant::class;
            }
            case in_array(strtolower($value), ['subscription', 'subscriptions']):
            {
                return Subscription::class;
            }
            default:
            {
                return $value;
            }
        }
    }
}
