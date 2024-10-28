<?php

namespace App\Services;

use App\Models\StoreItem;
use Illuminate\Support\Facades\Log;

class StoreItemService
{
    private $_storeItem;
    public function __construct(StoreItem $storeItem)
    {
        $this->_storeItem = $storeItem;
    }

    public function getStoreItems($userId)
    {
        return $this->_storeItem->where('user_id', $userId)->get();
    }

    public function getStoreItemById($id)
    {
        return $this->_storeItem->where('id', $id)->first();
    }

    public function getStoreItemByName($userId, $name)
    {
        return $this->_storeItem->where('user_id', $userId)->where('item_name', $name)->first();
    }

    public function getStoreItemLikeName($userId, $input)
    {
        return $this->_storeItem->where('user_id', $userId)->where('item_name', 'like', "%$input%")->get();
    }

    public function setStoreItem($userId, $arrStoreItems): bool
    {
        try{
            $this->_storeItem->user_id = $userId;
            $this->_storeItem->item_name = $arrStoreItems['item_name'];
            $this->_storeItem->item_unit = $arrStoreItems['item_unit'];
            $this->_storeItem->item_quantity = $arrStoreItems['item_quantity'];
            $this->_storeItem->dollarPerSet = $arrStoreItems['dollarPerSet'];

            $this->_storeItem->save();
            return true;
        }catch (\Exception $e){
            Log::error('[insertError]=>'.$e->getMessage());
            return false;
        }
    }

    public function updateStoreItem($id, $arrStoreItems)
    {
        try{
            $this->_storeItem->where('id', $id)->update($arrStoreItems);
        }
        catch (\Exception $e){
            Log::error('[updateError]=>'.$e->getMessage());
        }
    }

    public function delStoreItem($id)
    {
        return $this->_storeItem->where('id', $id)->delete();
    }
}

