<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Rules\BicRule;
use App\Rules\NoDigitsRule;
use App\Rules\PasswordRule;
use App\Rules\PhoneRule;
use App\Rules\PostalCodeRule;
use App\Rules\SteueridentifikationsnummerRule;
use App\Rules\StrictEmailRule;
use App\Rules\StringLengthRule;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Nembie\IbanRule\ValidIban;

class AuthController extends BaseController
{
    use ApiResponses;
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'firstname' => ['required', 'string' , new StringLengthRule()],
            'lastname' => ['required', 'string' , new StringLengthRule()],
            'city' => ['required', 'string' , new StringLengthRule()],
            'country' => ['required', 'string' , new StringLengthRule(), new NoDigitsRule()],
            'number' => ['required', new PhoneRule()],
            'pzl' => ['required', new PostalCodeRule()],
            'email' => [
                'required',
                new StrictEmailRule,
                new StringLengthRule(),
                'unique:users,email'  // Prüft auf Einzigartigkeit in der users-Tabelle
            ],
            'password' => ['required', 'string' , new PasswordRule()],
            'steueridentifikationsnummer' => ['required', new SteueridentifikationsnummerRule()],
            'street' => ['required', 'string' , new StringLengthRule()],
            'bank_name' => 'required|string',
            'bic' => ['required', new BicRule()],
            'iban' => ['required', new ValidIban()], // modify the validation error in vendors folder (readonly issue)
            'role_id' => 'nullable|exists:roles,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            $errorMessage = implode(' ', $errors);

            // Nutze die Übersetzungen
            if (str_contains($errorMessage, 'email has already been taken')) {
                return $this->error(__('messages.user.email.exists'), null);
            }

            return $this->error($errorMessage, null);
        }

        try {
            $newUser = User::create([
                "firstname" => $request->input("firstname"),
                "lastname" => $request->input("lastname"),
                "email" => $request->input("email"),
                "password" => bcrypt($request->input('password')),
                "steueridentifikationsnummer" => $request->input("steueridentifikationsnummer"),
                "street" => $request->input("street"),
                "number" => $request->input("number"),
                "pzl" => $request->input("pzl"),
                "city" => $request->input("city"),
                "country" => $request->input("country"),
                "bank_name" => $request->input("bank_name"),
                "iban" => $request->input("iban"),
                "bic" => $request->input("bic"),
                'role_id' => 5,
                'approved' => false, // Not approved initially
            ]);

            return $this->success(__('messages.user.registered'), $newUser);
        } catch (\Exception $e) {
            return $this->error(__('messages.errors.registration_failed'), null);
        }
    }
    function register_(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'firstname' => ['required', 'string' , new StringLengthRule()],
            'lastname' => ['required', 'string' , new StringLengthRule()],
            'city' => ['required', 'string' , new StringLengthRule()],
            'country' => ['required', 'string' , new StringLengthRule(), new NoDigitsRule()],
            'number' => ['required', new PhoneRule()],
            'pzl' => ['required', new PostalCodeRule()],
            'email' => [
                'required',
                new StrictEmailRule,
                new StringLengthRule(),
                'unique:users,email'  // Prüft auf Einzigartigkeit in der users-Tabelle
            ],
            'password' => ['required', 'string' , new PasswordRule()],
            'steueridentifikationsnummer' => ['required', new SteueridentifikationsnummerRule()],
            'street' => ['required', 'string' , new StringLengthRule()],
            'bank_name' => 'required|string',
            'bic' => ['required', new BicRule()],
            'iban' => ['required', new ValidIban()], // modify the validation error in vendors folder (readonly issue)
            'role_id' => 'nullable|exists:roles,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            $errorMessage = implode(' ', $errors);

            abort(400, $errorMessage);
        }


        $newUser = User::create([
            "firstname" => $request->input("firstname"),
            "lastname" => $request->input("lastname"),
            "email" => $request->input("email"),
            "password" => bcrypt($request->input('password')),
            "steueridentifikationsnummer" => $request->input("steueridentifikationsnummer"),
            "street" => $request->input("street"),
            "number" => $request->input("number"),
            "pzl" => $request->input("pzl"),
            "city" => $request->input("city"),
            "country" => $request->input("country"),
            "bank_name" => $request->input("bank_name"),
            "iban" => $request->input("iban"),
            "bic" => $request->input("bic"),
            'role_id' => 5,
            'approved' => false, // Not approved initially
        ]);

//        return response()->json(['success' => true,
//            'message' => "User has been successfully registered. Waiting for approval .",
//            'data' => $newUser]);
        return $this->success('Data saved successfully', $newUser);
    }
    public function login(Request $request): JsonResponse
    {
        try {
            $credentials = $request->validate([
                'email' => ['required', new StrictEmailRule, 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            // Validate user existence
            if (!User::where('email', $request->input('email'))->exists()) {
//                return $this->error('Invalid email', null);
                return $this->error(__('messages.errors.invalid_email'), null);
            }

            // Retrieve the user by email
            $user = User::where('email', $request->input('email'))->first();

            // Check if user exists
            if (!$user) {
//                return $this->error('User not found', null);
                return $this->error(__('messages.user.not_found'), null);
            }

            // Check if user is approved
            if (!$user->approved) {
//                return $this->error('User not approved yet!', null);
                return $this->error(__('messages.user.not_approved'), null);
            }

            // Check if the user's role is 'NoRole'
            if ($user->role_id == 5) {
//                return $this->error('User role not assigned. Please wait for approval.', null);
                return $this->error(__('messages.user.no_role'), null);
            }

            // Verify the password
            if (!Hash::check($request->input('password'), $user->password)) {
                return $this->error('Invalid password.', null);
            }

            // Attempt to log in the user
            if (Auth::attempt($credentials)) {
                $user = Auth::user(); // Get the authenticated user
                $roleId = $user->role_id; // Fetch the user's role_id
                $token = $user->createToken("auth_token")->plainTextToken; // Create API token

                // roles and their responses
                $roles = [
                    1 => 'Superadmin',
                    2 => 'Admin',
                    3 => 'UserFest',
                    4 => 'UserHoni',
                    5 => 'NoRole'
                ];

                $roleName = $roles[$roleId] ?? 'Unknown';
                return $this->success(__('messages.success.saved'),  [
                    'user' =>  $user->toArray(),
                    'role' => $roleName,
                    'token' => $token
                ]);
//                return $this->success('Data saved successfully',  [
//                    'user' =>  $user->toArray(),
//                    'role' => $roleName,
//                    'token' => $token
//                ]);
            }
            else {
//                return $this->error('Invalid credentials.', null);
                return $this->error(__('messages.auth.invalid_credentials'), null);
            }

        }
        catch (\Exception $e) {
            return $this->error(__('messages.validation.password.server_error'), null);
//            return $this->error("The password field must be at least 8 characters or something is off on the server side. Pleas try again later! : $e",
//                                    null);
        }
    }
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

//        return response()->json([
//            'message' => 'Logged out successfully'
//        ]);
//        return $this->success('Logged out successfully', null);
        return $this->success(__('messages.auth.logout_success'), null);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        // Sprache explizit setzen
//        app()->setLocale('de');

        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return $this->success('Link zum Zurücksetzen des Passworts wurde per E-Mail gesendet', null);
        }

        // Fehlermeldung aus lang/de/passwords.php via trans() holen
        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->success('Passwort wurde erfolgreich zurückgesetzt', null);
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
    public function forgotPassword_(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
//            return $this->success('Reset password link sent to email', null);
            return $this->success(__('messages.password.reset_link_sent'), null);
//            return response()->json(['message' => 'Reset password link sent to email']);
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
    public function resetPassword_(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->success(__('messages.password.reset_success'), null);
//            return $this->success('Password reset successfully', null);
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
    public function update(Request $request): JsonResponse
    {
        $rules = [
            'firstname' => 'sometimes|required|string|max:255',
            'lastname' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', new StrictEmailRule, 'max:255'],
            'city' => 'sometimes|required|string|max:255',
            'country' => 'sometimes|required|string|max:255|not_regex:/\d/',
            'number' => ['sometimes', 'required', new PhoneRule()],
            'pzl' => 'sometimes|required|integer',
            'password' => 'sometimes|required|string|min:8',
            'steueridentifikationsnummer' =>'sometimes|required|digits:11',
            'street' => 'sometimes|required|string|max:255',
            'bank_name' => 'sometimes|required|string',
            'bic' => ['sometimes', 'required', new BicRule()],
            'iban' => ['sometimes', 'required', new ValidIban()],
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), null);
//            return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'data' => null], 422);
        }

        // Retrieve the user by ID
        $user = User::find($request->user()->id);

        // Check if the user exists
        if (!$user) {
//            return $this->error('User not found', null);
            return $this->error(__('messages.user.not_found'), null);
//            return response()->json(['success' => false, 'message' => 'User not found', 'data' => null], 404);
        }

        // Update the user's information
        try {
            $updatedFields = $request->only(array_keys($rules));
            $user->fill($updatedFields);

            if ($user->isDirty()) {
                $user->save();
//                return response()->json(['success' => true, 'message' => 'User information updated successfully', 'data' => $user]);
//                return $this->success('User information updated successfully', $user);
                return $this->success(__('messages.user.updated'), $user);
//
            } else {
//                return response()->json(['success' => true, 'message' => 'No changes were made', 'data' => $user]);
                return $this->success(__('messages.user.no_changes'), $user);
//                return $this->success('No changes were made', $user);
            }
        } catch (\Exception $e) {
//            return $this->error('Error updating user information', null);
            return $this->error(__('messages.user.update_error'), null);
//            return response()->json(['success' => false, 'message' => 'Error updating user information', 'data' => null], 500);
        }
    }
    public function getProfile(): JsonResponse
    {
        if (!Auth::check()) {
//            return $this->error('Unauthorized', null);
            return $this->error(__('messages.auth.unauthorized'), null);
//            return response()->json(['success' => false, 'message' => 'Unauthorized', 'data' => null], 401);
        }

        $user = Auth::user();

        // Return the user data
//        return $this->success('User found', $user);
        return $this->success(__('messages.user.found'), $user);
//        return response()->json(['success' => true, 'message' => 'User found', 'data' => $user]);
    }
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }
}
