<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Work;
use App\Notifications\UserApprovedNotification;
use App\Notifications\UserDisapprovedNotification;
use App\Notifications\UserRoleChangedNotification;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
                    'role' => $user->role,
                    'city' => $user->city,
                    'country' => $user->country,
                    'approved' => (bool)$user->approved,
                ];
            });
            return $this->success(trans('messages.superadmin.users_found'), $users);
        }
        else {
            return $this->error(trans('messages.superadmin.no_users_found'), null);
        }
    }

    public function approve(Request $request, int $userId)
    {
        try{
            // Retrieve the user by their ID
            $user = User::find($userId);

            // Check if the user exists
            if (!$user) {
                return $this->error(trans('messages.superadmin.failure_approve'), null);
            }

            // Check if the user is already approved
            $previousRole = $user->role_id;
            if ($user->role_id != 5) {
                return $this->error(trans('messages.superadmin.role_validation_failure_approve'), null);
            }

            if ($request->has('approved')) {
                if ($request->has('role_id')) {
                    $user->role_id = $request->input('role_id');
                    $user->approved = true;
                    $user->save();
                }

                try {
                    if($previousRole == 5)
                        $user->notify(new UserApprovedNotification($user));
                }
                catch (\Exception $e) {
                    Log::error($this->error(trans('messages.superadmin.failure_notification_email'), $e->getMessage()));
                }
                return $this->success(trans('messages.superadmin.approved'), null);
            }
            return $this->error(trans('messages.errors.invalid_request'), null);
        }
        catch (\Exception $e) {
            return $this->error(trans('messages.server_error'), $e);
        }

    }

    public function disapprove(Request $request, int $userId)
    {
        $user = User::find($userId);

        if ($user->approved) {
            $user->approved = false;
            $user->role_id = 5; // Reset role to NoRole (ID 5)
            $user->save();

            try {
                $user->notify(new UserDisapprovedNotification($user));
            }
            catch (\Exception $e) {
                Log::error($this->error(trans('messages.superadmin.failure_notification_email'), $e->getMessage()));
            }

            return $this->success(trans('messages.superadmin.disapproved'), null);
        }
        else {
            return $this->error(trans('messages.superadmin.failure_disapprove'), null);
        }
    }

    public function changeRole(Request $request, int $userId)
    {
        try{
            // Retrieve the user by their ID
            $user = User::find($userId);

            // Check if the user exists
            if (!$user) {
                return $this->error(trans('messages.superadmin.failure_approve'), null);
            }

            // Check if the user is not approved first
            $previousRole = $user->role_id;
            if ($user->role_id == 5) {
                return $this->error(trans('messages.superadmin.role_validation_failure_disapprove'), null);
            }

            if ($request->has('approved')) {
                if ($request->has('role_id')) {
                    $user->role_id = $request->input('role_id');
                    $user->approved = true;
                    $user->save();
                }

                try {
                    if($previousRole != $user->role_id && $previousRole != 5)
                        $user->notify(new UserRoleChangedNotification($user));
                }
                catch (\Exception $e) {
                    Log::error($this->error(trans('messages.superadmin.failure_notification_email'), $e->getMessage()));
                }
                return $this->success(trans('messages.superadmin.change'), null);
            }
            return $this->error(trans('messages.errors.invalid_request'), null);
        }
        catch (\Exception $e) {
            return $this->error(trans('messages.server_error'), $e);
        }

    }
}
