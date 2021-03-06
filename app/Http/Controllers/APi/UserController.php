<?php

namespace App\Http\Controllers\APi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{
        /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $this->authorize('isAdmin');
        
        // if (\Gate::allows('isAdmin') || \Gate::allows('isAuthor') ) {

        //     return User::latest()->paginate(10);
        // }
        if (\Gate::any(['isAdmin', 'isAuthor']) ) {

            return User::latest()->paginate(2);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $this->validate($request,[
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'email', 'max:191', 'unique:users'],
            'password' => ['required', 'string', 'min:6'],
        ]);
        return User::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'type' => $request['type'],
            'bio' => $request['bio'],
            'photo' => $request['photo'],
            'password' => Hash::make($request['password']),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
       
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $this->validate($request,[
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'email', 'max:191'],
        ]);

        $user =  User::find($id);


          $user->name = $request['name'];
          $user->email = $request['email'];
          $user->type = $request['type'];
          $user->bio = $request['bio'];
          $user->photo = $request['photo'];

          if($request->has($request['password'])){
           $user->password =  Hash::make($request['password']);
          }
         
          $user->save();
          return ["user is edited"];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->authorize('isAdmin');
        $user =  User::findOrFail($id);
        $user->delete();
        return ["message" => "User Is Deleted"];
    }

    public function profile(){
       
        return auth('api')->user();
       
    }
    public function updateProfile(Request $request){
        $this->validate($request,[
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'email', 'max:191'],
            'password' => ['required', 'sometimes', 'min:6'],
        ]);

       $user = auth('api')->user();
       $current_photo = $user->photo;

      if($request->photo != $current_photo){

            $name = time().'.' . explode('/', explode(':', substr($request->photo, 0, strpos($request->photo, ';')))[1])[1];

            \Image::make($request->photo)->save(public_path('img/profile/').$name);
            $request->merge(['photo'=>$name]);

            $userPhoto = public_path('img/profile/').$current_photo;
            if(file_exists($userPhoto)){
                @unlink($userPhoto);
            }
        
        }

        if(!empty($request->password)){
            $request->merge(['password'=>Hash::make($request['password'])]);
        }

      $user->update($request->all());
      return ['msg'=>' Success'];

    }

public function findUser(Request $request)
{
//   if( $search = $request->q){
//         $users = \DB::table('users')
//             ->paginate(2);
//             ->where('name', 'like', "%$search%")
//             ->orWhere('email','like',"%$search%")
//             ->get()

//             return  $users ;
//     }

        if ($search = \Request::get('q')) {
            $users = User::where(function($query) use ($search){
                $query->where('name','LIKE',"%$search%")
                        ->orWhere('email','LIKE',"%$search%");
            })->paginate(20);
        }else{
            $users = User::latest()->paginate(5);
        }

        return $users;
   
}
}
