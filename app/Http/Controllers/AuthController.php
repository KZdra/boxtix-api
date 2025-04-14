<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;


class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getOwnUser()
    {
        $user = auth()->user();
        $role = DB::table('roles')->where('id', $user->role_id)->value('name');
        $user->role = $role;
        if ($user->profile_picture) {
            $user->profile_picture_url = url('storage/profile_pictures/' . $user->profile_picture_name);
        }
        if ($user) {
            return response()->json(['status' => 1, 'message' => 'data_get', 'data' => $user], 200);
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }
    }

    public function register(Request $request)
    {
        $validate = Validator::make(request()->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
        ]);

        if ($validate->fails()) {
            return response()->json($validate->messages(), 400);
        }
        if ($request->hasFile('picture_profile')) {
            $picture_profile = $request->file('picture_profile')->store('picture_profiles', 'public');
            $file_name = basename($picture_profile);
        } else {
            $picture_profile = null;
            $file_name = null;
        }

        try {
            DB::table('users')->insert([
                'name' => request('name'),
                'role_id' => isset($request->role) ? $request->role : 2,
                'email' => request('email'),
                'profile_picture' => $picture_profile,
                'profile_picture_name' => $file_name,
                'password' => Hash::make(request('password')),
                'created_at' => now()
            ]);

            return response()->json(['message' => 'Registrasi Sukses'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal: ' . $e->getMessage(), 's' => $e], 500);
        }
    }





    public function deleteUser($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        if ($user->delete()) {
            return response()->json(['message' => 'USER DELETED!']);
        } else {
            return response()->json(['message' => 'Failed to Delete User'], 500);
        }
    }
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $user = auth()->user();
        if ($user->profile_picture) {
            $user->profile_picture_url = url('storage/profile_pictures/' . $user->profile_picture_name);
        }
        $role = DB::table('roles')->where('id', $user->role_id)->value('name');
        $user->role = $role;
        return $this->respondWithToken($token, $user);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    // public function me()
    // {
    //     return response()->json(auth()->user());
    // }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }
    public function getUsers()
    {
        try {
            $users = DB::table('users')
                ->join('roles', 'users.role_id', '=', 'roles.id')
                ->select('users.id', 'users.name', 'users.email', 'roles.name as role_name','users.profile_picture_name')
                ->get();
            if ($users) {
                foreach ($users as $u) {
                    if ($u->profile_picture_name) {
                        $u->profile_picture_url = url('storage/profile_pictures/' . $u->profile_picture_name);
                    }
                }
                return response()->json($users);
            } else {
                return response()->json(['message' => 'User not found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['Gagal Get'], 200);
        }
    }

    public function getUser($id)
    {
        $user = DB::table('users')->where('id', $id)->first();

        if ($user) {
            if ($user->profile_picture_name) {
                $user->profile_picture_url = url('storage/profile_pictures/' . $user->profile_picture_name);
            }
            return response()->json($user);
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }
    }

    public function getRoles()
    {
        $role = DB::table('roles')->get();
        if ($role) {
            return response()->json($role);
        } else {
            return response()->json(['message' => 'role not found'], 404);
        }
    }
    public function updateUser(Request $request, $id)
    {
        $validate = Validator::make(request()->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'role_id' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json($validate->messages(), 400);
        }
        if ($request->hasFile('picture_profile')) {
            $picture_profile = $request->file('picture_profile')->store('picture_profiles', 'public');
            $file_name = basename($picture_profile);
        } else {
            $picture_profile = null;
            $file_name = null;
        }


        DB::beginTransaction();

        try {
            $user = DB::table('users')->where('id', $id)->first();

            if (!$user) {
                DB::rollBack();
                return response()->json(['message' => 'User not found'], 404);
            }

            $data = [
                'name' => request('name'),
                'email' => request('email'),
                'role_id' => request('role_id'),
            ];

            if (request('password')) {
                $data['password'] = Hash::make(request('password'));
            }

            if ($request->hasFile('picture_profile')) {
                $picture_profile = $request->file('picture_profile')->store('picture_profiles', 'public');
                $file_name = basename($picture_profile);
                $data['profile_picture'] = $picture_profile;
                $data['profile_picture_name'] = $file_name;
            } else {
                $picture_profile = null;
                $file_name = null;
            }

            $updated = DB::table('users')
                ->where('id', $id)
                ->update($data);

            if ($updated) {
                DB::commit();
                return response()->json(['message' => 'User updated successfully']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update user', 'error' => $e->getMessage()], 500);
        }
    }
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        $user = auth()->user();
        $role = DB::table('roles')->where('id', $user->role_id)->value('name');
        $user->role = $role;
        if ($user->profile_picture) {
            $user->profile_picture_url = url('storage/profile_pictures/' . $user->profil_picture_name);
        }
        return $this->respondWithToken(auth()->refresh(), $user);
    }




    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token, $user)
    {
        return response()->json([
            'access_token' => $token,
            'user' => $user,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 360
        ]);
    }
}
