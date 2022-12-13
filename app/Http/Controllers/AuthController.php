<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\BookReview;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed'
        ]);
        if (!$validator->fails()) {
            DB::beginTransaction();
            try {
                //Set data
                $user = new User();
                $user->name = $request->name;
                $user->email = $request->email;
                $user->password = Hash::make($request->password); //encrypt password
                $user->save();
                DB::commit();
                return $this->getResponse201('user account', 'created', $user);
            } catch (Exception $e) {
                DB::rollBack();
                return $this->getResponse500([$e->getMessage()]);
            }
        } else {
            return $this->getResponse500([$validator->errors()]);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        if (!$validator->fails()) {
            $user = User::where('email', '=', $request->email)->first();
            if (isset($user->id)) {
                if (Hash::check($request->password, $user->password)) {
                    //Create new token
                    $token = $user->createToken('auth_token')->plainTextToken;
                    return response()->json([
                        'message' => "Successful authentication",
                        'access_token' => $token,
                    ], 200);
                } else { //Invalid credentials
                    return $this->getResponse401();
                }
            } else { //User not found
                return $this->getResponse401();
            }
        } else {
            return $this->getResponse500([$validator->errors()]);
        }
    }

    public function changePassword(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'password' => 'required'
        ]);
        if($request->password == $request->password_confirmation){
            if(!$validator->fails()){
                DB::beginTransaction();
                try {
                    $user = User::where('id', $id)->first();
                    if($user){
                        $user->password = Hash::make($request->password);
                        $user->update();
                        DB::commit();
                        $request->user()->tokens()->delete();
                        return $this->getResponse201('Password Changed', 'update', "");
                    }else{
                        return $this->getReposnse404();
                    }
                } catch(Exeption $e) {
                    DB::rollback();
                    return $this->getResponse500([$e->getMessage()]);
                }
            }else{
                return $this->getResponse500([$validator->errors()]);
            }
        }else{
            return $this->getResponse500(["password" => "Las contraseñas no coinciden..."]);
        }
    }

    public function userProfile()
    {
        return $this->getResponse200(auth()->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete(); //Revoke all tokens
        return response()->json([
            'message' => "Logout successful"
        ], 200);
    }

    public function addBookReview(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'comment' => 'required'
        ]);
        if(!$validator->fails()){
            DB::beginTransaction();
            try {
                $bookReview = new BookReview();
                $bookReview->comment = $request->comment;
                $bookReview->user_id = auth()->user()->id;
                $bookReview->book_id = $id;
                $bookReview->save();
                DB::commit();
                return $this->getResponse201('review', 'created', $bookReview);
            } catch(Exceotion $e) {
                DB::rollback();
                return $this->getResponse500([$e-getMessage()]);
            }
        }else{
            return $this->getResponse500([$validator->errors()]);
        }
    }

    public function updateBookReview(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'comment' => 'required'
        ]);
        if(!$validator->fails()){
            DB::beginTransaction();
            try {
                $bookReview = BookReview::where('id', $id)->first();
                if(auth()->user()->id == $bookReview->user_id){
                    $bookReview->comment = $request->comment;
                    $bookReview->edited = 1;
                    $bookReview->update();
                    DB::commit();
                    return $this->getResponse201('review', 'updated', $bookReview);
                }else{
                    return $this->getResponse403();
                }
            } catch(Exceotion $e) {
                DB::rollback();
                return $this->getResponse500([$e-getMessage()]);
            }
        }else{
            return $this->getResponse500([$validator->errors()]);
        }
    }
}
