<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\{
    Purchase
};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Exception;
class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        try {
            $take=$request->input('take',10);
            $query=Purchase::query();
            $query->orderBy("id","DESC");
            $query=$query->paginate($take);
            return $this->pagination($query);
        } catch (Exception $e) {
            \Log::error([
                "message"=>$e->getMessage(),
                "line"=>$e->getLine(),
            ]);
            return $this->error('Ocurrió un error al intentar obtener el listado', 500);
        }
    }

}