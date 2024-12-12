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


//trait ApiResponses
//{
//    public function success($message, $data)
//    {
//        return response()->json([
//            'success' => true,
//            'message' => $message,
//            'data' => $data
//        ], 200); // OK
//    }
//
//    public function error($message, $errors = [], $code = 400)
//    {
//        return response()->json([
//            'success' => false,
//            'message' => $message,
//            'errors' => $errors
//        ], $code);
//    }
//
//    public function validationError($message, $errors)
//    {
//        return response()->json([
//            'success' => false,
//            'message' => $message,
//            'errors' => $errors
//        ], 422);
//    }
//
//    public function unauthorized($message = 'Unauthorized')
//    {
//        return response()->json([
//            'success' => false,
//            'message' => $message
//        ], 401);
//    }
//
//    public function notFound($message = 'Not Found')
//    {
//        return response()->json([
//            'success' => false,
//            'message' => $message
//        ], 404);
//    }
//}
