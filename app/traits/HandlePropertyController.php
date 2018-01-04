<?php

namespace App;

use App\Category;
use App\Property;
use App\PropGroup;
use phpDocumentor\Reflection\Types\Object_;

/**
 * Set and get property array
 *
 * Trait HandlePropertyController
 * @package App
 */
trait HandlePropertyController
{
    public $arProps = [];
    public $propList = [];
    public $insertCount = 0;

    /**
     * Set properties array and serialize them
     *
     * @param array $properties
     * @param $selectTable
     * @param $elementId
     * @return string
     */
    public function setProperties($properties = [], $selectTable = NULL, $elementId = NULL)
    {
        $strProp = "";
        $obProps = [];
        $oldImages = [];
        $arProps = [];

        if (!is_null($selectTable)) {
            $obProps = unserialize($selectTable
                ->where('id', $elementId)
                ->select('properties')
                ->get()
                ->toArray()[0]['properties']);
        }
        $this->arProps = $obProps;

        if (count($properties) > 0) {
            foreach ($properties as $propId => $property) {
                $propOb = [];
                $propGroupName = PROP_GROUP_NAME_ALL;

                $propOb = Property::where('id', $propId)->get()->toArray()[0];

                if (!is_null($propOb['group_id']))
                    $propGroupName = PropGroup::where('id', $propOb['group_id'])->select(['id', 'title'])->get()->toArray()[0]['title'];

                $this->arProps[$propGroupName][$propId] = $propOb;

                // Image properties
                if ($propOb['type'] == PROP_TYPE_IMG) {
                    if (isset($obProps[$propGroupName][$propId]['value']))
                        $oldImages = $obProps[$propGroupName][$propId]['value'];

                    $this->arProps[$propGroupName][$propId]['value'] = $this->LoadImg($property, $oldImages);
                }
                if($propOb['type'] == PROP_TYPE_FILE) {
                    $this->arProps[$propGroupName][$propId]['value'] = $this->LoadFile($property);
                }
                if(($propOb['type'] !== PROP_TYPE_FILE) && ($propOb['type'] !== PROP_TYPE_IMG)){
                    $this->arProps[$propGroupName][$propId]['value'] = $property;
                }
            }
        }
        $strProp = serialize($this->arProps);
        return $strProp;
    }

    /**
     * Get properties
     *
     * @param $selectTable
     * @param $propKind
     * @param string $strProp
     * @param null $categoryId
     */
    public function getProperties($selectTable, $propKind, $strProp = "", $categoryId = NULL)
    {
        $propValues = [];
        $propGroupName = PROP_GROUP_NAME_ALL;

        $propValues = unserialize($strProp);

        $this->getPropList($categoryId, $selectTable, $propKind);

        if (count($this->propList) > 0) {
            foreach ($this->propList as $key=>$property) {
                if (!is_null($property['group_id']))
                    $propGroupName = PropGroup::where('id', $property['group_id'])->get()->toArray()[0]['title'];

                $this->arProps[$propGroupName][$property["id"]] = $property;

                // Enumeration property
                if ($property['type'] == PROP_TYPE_LIST) {
                    $this->getListValues($property["id"]);
                    $this->arProps[$propGroupName][$property["id"]]['arList'] = $this->propEnums;
                }

                if (!empty($propValues[$propGroupName][$property["id"]]) && isset($propValues[$propGroupName][$property["id"]]['value']))
                    $this->arProps[$propGroupName][$property["id"]]['value'] = $propValues[$propGroupName][$property["id"]]['value'];
            }


        }
    }

    /**
     * Get tree of properties for categories
     *
     * @param $categoryId
     * @param $selectTable
     * @param $propKind
     */
    public function getPropList($categoryId, $selectTable, $propKind)
    {
        $categoryProps = [];

        $categoryProps = Property::orderby('id', 'asc')
            ->where("category_id", $categoryId)
            ->where('prop_kind', $propKind)
            ->get()
            ->toArray();

        foreach ($categoryProps as $key=>$property) {
            if ( (($this->insertCount === 0 && $property['is_insert'] === 0) || ($property['is_insert'] == 1)) && (!in_array($property, $this->propList)) )
                $this->propList[] = $property;
        }

        if ($categoryId) {
            // Get properties for all categories
            $this->getPropList(NULL, $selectTable, $propKind);
            $parentId = Category::where('id', $categoryId)->select(['parent_id'])->get()->toArray()[0]['parent_id'];
        }

        if (isset($parentId))
            $this->getPropList($parentId, $selectTable, $propKind);

        $this->insertCount++;
    }

    /**
     * Delete propertyes of category with destroy category
     *
     * @param $selectTable
     */
    public function deletePropertyWithDestroy($selectTable)
    {
        $propOb = Property::where('category_id', $selectTable->id)->select(['id', 'type'])->get()->toArray();
        $propIds = [];

        foreach ($propOb as $key=>$prop) {
            if ($prop['type'] == PROP_TYPE_LIST)
                $this->deleteListValues($prop['id']);

            $propIds[] = $prop['id'];
        }

        Property::destroy($propIds);
    }

    /**
     * Delete property values from element with change property kind, insert
     *
     * @param $requestData
     * @param $isChangeCategoryId - if change category id
     * @param $categoryId
     */
    public function deletePropertyWithChange($requestData, $categoryId, $isChangeCategoryId = false)
    {
        $arProperties = [];
        $arChildId = [];

        $filterField = "id";

        switch ($requestData['prop_kind']) {
            case PROP_KIND_CATEGORY:
                $selectTable = new Category();
                break;

            case PROP_KIND_ITEM:
                $selectTable = new Item();
                break;
        }

        // Get property array from DB
        if (!is_null($categoryId))
            $arElements = $selectTable->where($filterField, $categoryId)->select(['id', 'properties'])->get()->toArray();
        else
            $arElements = $selectTable->select(['id', 'properties'])->get()->toArray();

        foreach ($arElements as $key=>$arElement) {

            // Get properties
            $arProperties = unserialize($arElement['properties']);

            if (empty($arProperties) || ($isChangeCategoryId && ($categoryId == $arElement['id'])))
                continue;

            // Get child categories
            if (!is_null($categoryId))
                $arChildId = $selectTable->where('parent_id', $arElement['id'])->select(['id'])->get()->toArray();
            else
                $arChildId = $selectTable->select(['id'])->get()->toArray();

            // Delete properties
            foreach ($arProperties as $propGroup=>$arProperty) {
                if (key_exists($requestData['id'], $arProperty)) {

                    // Delete images
                    if ($requestData['old_type'] == PROP_TYPE_IMG) {
                        foreach ($arProperty[$requestData['id']]['value'] as $keyImg=>$arImg) {
                            $this->deleteMultipleImg($arProperty[$requestData['id']]['value'], $arImg['MIDDLE']);
                        }
                    }

                    // Delete files
                    if ($requestData['old_type'] == PROP_TYPE_FILE)
                        $this->deleteFileFromServer($arProperty[$requestData['id']]['value']);

                    // Delete property values from value array
                    unset($arProperties[$propGroup][$requestData['id']]);

                    $selectTable->where('id', $arElement['id'])->update(['properties' => serialize($arProperties)]);
                }
            }

            // Recurce for child categories
            if (!empty($arChildId)) {
                foreach ($arChildId as $childId)
                    $this->deletePropertyWithChange($requestData, $childId['id'], $isChangeCategoryId);
            }
        }
    }

    /**
     * Handle properties for public
     *
     * @param $strProperties
     * @return mixed
     */
    public function handlePropertyForPublic($strProperties)
    {
        $arPropGroup = unserialize($strProperties);

        if (!empty($arPropGroup)) {
            foreach ($arPropGroup as $groupName=>$propGroup) {
                foreach ($propGroup as $propId=>$property) {

                    if ($property['type'] == PROP_TYPE_IMG) {
                        $arPropGroup[$groupName][$propId]['value'] = $this->createPublicImgPath($property['value']);
                    }
                    elseif ($property['type'] == PROP_TYPE_LIST) {
                        // Enumeration property
                        $arPropGroup[$groupName][$propId]['arList'] = $this->getListValue($property["id"], $property['value']);
                    }
                    elseif ($property['type'] == PROP_TYPE_ITEM_LINK) {
                        $arPropGroup[$groupName][$propId]['arItem'] = $this->getLinkItems($property['value']);
                    }
                }
            }
        }

        return $arPropGroup;
    }

    /**
     * Get link items for public
     *
     * @param $arItemId
     * @return array
     */
    public function getLinkItems($arItemId)
    {
        $arItems = [];

        $arItems = Item::with('category')
        ->orderby('order', 'asc')->orderby('updated_at', 'desc');

        foreach ($arItemId as $itemId) {
            $arItems = $arItems->orWhere('id', $itemId);
        }

        $arItems = $arItems->select(['id', 'title', 'preview_img', 'properties', 'slug', 'is_product', 'category_id'])->get()->toArray();

        foreach ($arItems as $key=>$item) {
            if (isset($item['preview_img']))
                $arItems[$key]['preview_img'] = $this->createPublicImgPath(unserialize($item['preview_img']));

            if (isset($item['properties']))
                $arItems[$key]['properties'] = unserialize($item['properties']);

            // Set href
            if ($item['category_id'] != 0) {
                if ($item['is_product'] == 1)
                    $arItems[$key]['slug'] = '/' . CATALOG_SLUG . '/' . $item['category']['slug'] . '/' . $item['slug'];
                elseif ($item['is_product'] == 0)
                    $arItems[$key]['slug'] = '/' . BLOG_SLUG . '/' . $item['category']['slug'] . '/' . $item['slug'];
            }
        }

        return $arItems;
    }

}
