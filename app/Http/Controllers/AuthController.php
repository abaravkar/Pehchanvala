<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
class AuthController extends Controller {
    public function form(){return auth()->check()?redirect('/admin'):view('admin.login');}
    public function login(Request $request){
        $data=$request->validate(['email'=>'required|email','password'=>'required|string']);
        if (!Auth::attempt([...$data,'is_admin'=>true])) throw ValidationException::withMessages(['email'=>'The login details are incorrect.']);
        $request->session()->regenerate(); return redirect()->intended('/admin');
    }
    public function logout(Request $request){Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect('/admin/login');}
    public function password(Request $request){$data=$request->validate(['current_password'=>'required|current_password','password'=>['required','confirmed',Password::min(12)->mixedCase()->numbers()]]);$request->user()->update(['password'=>$data['password']]);$request->session()->regenerate();return back()->with('success','Password updated.');}
}
