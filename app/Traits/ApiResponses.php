<?php

namespace App\Traits;

trait ApiResponses
{
    public function success($message, $data,$pagination = null)
    {
        return response()->json(['success' => true,'message' => $message, 'data' => $data, 'pagination' => $pagination],200);
    }
    public function error($message, $data)
    {
        return response()->json(['success' => false,'message' => $message, 'data' => $data],400);
    }
}
