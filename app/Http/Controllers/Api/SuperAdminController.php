<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminController
{
    use ApiResponses;
    public function getAllUsers()
    {
        $user_role = Auth::user()->role_id;

        if ($user_role == 1) { // Assuming 1 is the role ID for Superadmin
            $users = User::all()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'email' => $user->email,
                    'role_id' => $user->role_id,
                    'city' => $user->city,
                    'country' => $user->country,
                    'approved' => (bool)$user->approved,
                ];
            });

//            return response()->json(['success' => true, 'message' => 'Users fetched successfully', 'data' => $users]);
            return $this->success(trans('messages.superadmin.users_found'), $users);
        }
        else {
            return $this->error(trans('messages.superadmin.no_users_found'), null);
//            return response()->json(['success' => false, 'message' => 'Not Authorized', 'data' => null], 401);
        }
    }

//return $this->success(trans('messages.user.registered'), $newUser);
//} catch (\Exception $e) {
//    return $this->error(trans('messages.errors.registration_failed'), null);
    public function approve(Request $request, int $userId)
    {
        // Retrieve the user by their ID
        $user = User::find($userId);

        // Check if the user exists
        if (!$user) {
//            return response()->json(['success' => false, 'message' => 'User not found', 'data' => null], 404);
            return $this->error(trans('messages.superadmin.failure_approve'), null);
        }

        if ($request->has('approved')) {
            if ($request->has('role_id')) {
                $user->role_id = $request->input('role_id');
                $user->approved = true;
                $user->save();
            }
//            return response()->json(['success' => true, 'message' => 'User approved and Role assigned', 'data' => null]);
            return $this->success(trans('messages.superadmin.approved'), null);
        }
//        return response()->json(['success' => false, 'message' => 'Invalid request', 'data' => null],401);
        return $this->error(trans('messages.errors.invalid_request'), null);
    }

    public function disapprove(Request $request, int $userId)
    {
        $user = User::find($userId);

        if ($user->approved) {
            $user->approved = false;
            $user->role_id = 5; // Reset role to NoRole (ID 5)
            $user->save();
//            return response()->json(['message' => 'User disapproved']);
//            return response()->json(['success' => true, 'message' => 'User disapproved', 'data' => null]);
            return $this->success(trans('messages.superadmin.disapproved'), null);
        } else {
//            return response()->json(['message' => 'User is not approved'], 400);
            return $this->error(trans('messages.superadmin.failure_disapprove'), null);
//            return response()->json(['success' => false, 'message' => 'User is not approved', 'data' => null], 400);
        }
    }
}
