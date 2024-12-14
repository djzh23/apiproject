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
        try{
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

                return $this->success(trans('messages.user.registered'), $newUser);
            } catch (\Exception $e) {
                return $this->error(trans('messages.errors.registration_failed'), null);
            }
        }
        catch (\Exception $e) {
            Log::error("register() function error-server: $e");
            return $this->error(__('messages.server_error'), null);
        }

    }
    public function login(Request $request): JsonResponse
    {
        try {

            $credentials = Validator::make($request->all(), [
                'email' => ['required', new StrictEmailRule],
                'password' => ['required', new PasswordRule],
            ]);

            if ($credentials->fails()) {
                // Concatenate all error messages into a single string
                $errorMessages = $credentials->errors()->all(); // Get all error messages as an array
                $errorString = implode(' ', $errorMessages); // Join them into a single string

                return $this->error($errorString, null); // Return the concatenated string
            }

            // Validate user existence
            if (!User::where('email', $request->input('email'))->exists()) {
//                return $this->error('Invalid email', null);
                return $this->error(__('messages.validation.email.not_found'), null);
            }

            // Retrieve the user by email
            $user = User::where('email', $request->input('email'))->first();


            // Verify the password
            if (!Hash::check($request->input('password'), $user->password)) {
                return $this->error(__('messages.auth.password.failed'), null);

            }
            // Check if user is approved
            if (!$user->approved) {
                return $this->error(__('messages.user.not_approved'), null);
            }

            // Check if the user's role is 'NoRole'
            if ($user->role_id == 5) {
                return $this->error(__('messages.user.no_role'), null);
            }



            // Attempt to log in the user
            if (Auth::attempt($request->only('email', 'password'))) {
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

//                $roleName = $roles[$roleId] ?? 'Unknown';
                $roleName = $roles[$roleId];
                return $this->success(__('messages.auth.login.success'),  [
                    'user' =>  $user->toArray(),
                    'role' => $roleName,
                    'token' => $token
                ]);
            }
            else {
                return $this->error(__('messages.auth.invalid_credentials'), null);
            }

        }
        catch (\Exception $e) {
            Log::error("login function error-server: $e");
            return $this->error(__('messages.server_error'), null);
        }
    }
    public function logout(Request $request): JsonResponse
    {
        try{
            $request->user()->currentAccessToken()->delete();

            return $this->success(__('messages.auth.logout_success'), null);
        }
        catch (\Exception $e) {
            Log::error("logout function error-server: $e");
            return $this->error(__('messages.server_error'), null);
        }
    }
    public function forgotPassword(Request $request): JsonResponse
    {
        try{
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
        catch (\Exception $e) {
            Log::error("forgotPassword function error-server:: $e");
            return $this->error(__('messages.server_error'), null);
        }

    }
    public function resetPassword(Request $request): JsonResponse
    {
        try{
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
        catch (\Exception $e) {
            Log::error("resetPassword() function error-server:: $e");
            return $this->error(__('messages.server_error'), null);
        }

    }
    public function update(Request $request): JsonResponse
    {
        try{
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

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), null);
            }

            $user = User::find($request->user()->id);


            if (!$user) {
                return $this->error(__('messages.user.not_found'), null);
            }

            // Update the user's information
            try {
                $updatedFields = $request->only(array_keys($rules));
                $user->fill($updatedFields);

                if ($user->isDirty()) {
                    $user->save();
                    return $this->success(__('messages.user.updated'), $user);

                } else {
                    return $this->success(__('messages.user.no_changes'), $user);
                }
            } catch (\Exception $e) {
                return $this->error(__('messages.user.update_error'), null);
            }
        }
        catch (\Exception $e) {
            Log::error("update() Profile function error-server:: $e");
            return $this->error(__('messages.server_error'), null);
        }

    }
    public function getProfile(): JsonResponse
    {
        try{
            if (!Auth::check()) {
                return $this->error(__('messages.auth.unauthorized'), null);
            }

            $user = Auth::user();

            return $this->success(__('messages.user.found'), $user);
        }
        catch (\Exception $e) {
            Log::error("getProfile() function error-server:: $e");
            return $this->error(__('messages.server_error'), null);
        }

    }
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }
}
