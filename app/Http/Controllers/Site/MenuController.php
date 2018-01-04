<?php

namespace App\Http\Controllers\Site;

use App;
use App\Item;
use App\Category;
use App\Review;
use App\MenuType;
use App\Menu;
use App\MenuItem;
use App\Http\Controllers\Controller;

class MenuController extends Controller
{
    /**
     * Create menu tree
     *
     * @return array
     */
    public static function createMenuTree()
    {
        $uri = '/' . url()->getRequest()->path();
        $menuItems = new MenuItem();
        $resultMenuItems = [];

        $menuItems = $menuItems->with('menu')
        ->orderby('order', 'asc')->orderby('updated_at', 'desc')
        ->get()
        ->toArray();

        // Grouping by menu
        foreach ($menuItems as $key=>$menuItem) {
            $resultMenuItems[$menuItem['menu']['slug']][$menuItem['id']] = $menuItem;

            // Set active item
            if ($menuItem['href'] == $uri)
                $resultMenuItems[$menuItem['menu']['slug']][$menuItem['id']]['active'] = 'Y';
            else
                $resultMenuItems[$menuItem['menu']['slug']][$menuItem['id']]['active'] = 'N';
        }

        // Croup by child
        foreach ($resultMenuItems as $menuTitle=>$menuBlock) {
            foreach ($menuBlock as $menuItemId => $menuItem) {
                if (array_key_exists($menuItem['parent_id'], $menuBlock)) {
                    $resultMenuItems[$menuTitle][$menuItem['parent_id']]['children'][$menuItemId] = $menuItem;
                    unset($resultMenuItems[$menuTitle][$menuItemId]);
                }
            }
        }

        return $resultMenuItems;
    }
}