<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;

class UserController extends Controller {

    public function pruebas(Request $request) {
        return "accion de pruebas de USER controller";
    }

    //
    // para codigos ver 
    // https://es.wikipedia.org/wiki/Anexo:C%C3%B3digos_de_estado_HTTP
    //
    public function register(Request $request) {
        // recoger los datos del usuario por el post
        $json = $request->input('json', null);
        $params = json_decode($json); //objeto
        $params_array = json_decode($json, true);

        //Limpiar datos
        $params_array = array_map('trim', $params_array);

        if (!empty($params) && !empty($params_array)) {
            // Validad datos
            $validate = \Illuminate\Support\Facades\Validator::make($params_array, [
                        'name' => 'required|alpha',
                        'surname' => 'required|alpha',
                        'email' => 'required|email|unique:users',
                        'password' => 'required'
            ]);
            if ($validate->fails()) {
                // Validacion incorrecta
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'El usuario no se ha validado',
                    'errors' => $validate->errors()
                );
            } else {
                // Validacion correcta                
                // Cifrar la contraseña - el metodo comentado genera una pass
                // que cambia en cada llamado. ojo
                // password_hash($params->password, PASSWORD_BCRYPT,['cost'=>4]);

                $pwd = hash('sha256', $params->password);

                //Comprobar si el usuario ya existe (duplicado)
                //se agrega con unique:users en la validacion de email
                // 
                // Crear el usuario
                $user = new User();
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->password = $pwd;
                $user->role = 'ROLE_USER';

                // Guardar el usuario
                $user->save();

                // Devuelve mensaje ok
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'El usuario se ha creado correctamente',
                    'user' => $user
                );
            };
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'Los datos ingresados no son correctos'
            );
        }
        return response()->json($data, $data['code']);
    }

    public function login(Request $request) {
        $jwtAuth = new \JwtAuth();

        // Recibir datos por POST
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        // Validar esos datos

        $validate = \Illuminate\Support\Facades\Validator::make($params_array, [
                    'email' => 'required|email',
                    'password' => 'required'
        ]);

        if ($validate->fails()) {
            // Validacion incorrecta
            $signup = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'El usuario no se ha podido identificar',
                'errors' => $validate->errors()
            );
        } else {
            // Cifrar la password
            $pwd = hash('sha256', $params->password);

            // Devolver token o datos
            $signup = $jwtAuth->signup($params->email, $pwd);
            if (!empty($params->gettoken)) {
                $signup = $jwtAuth->signup($params->email, $pwd, true);
            }
        }

        return response()->json($signup, 200);
    }

    public function update(Request $request) {
        
        // Comprobar si el usuario esta identificado
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        // Recoger los datos por post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);
        
        if ($checkToken && !empty($params_array)) {
            // Actualizar el usuario
            // Sacar usuario identificado
            $user = $jwtAuth->checkToken($token, true);

            // Validar los datos
            $validate = \Illuminate\Support\Facades\Validator::make($params_array, [
                        'name' => 'required|alpha',
                        'surname' => 'required|alpha',
                        'email' => 'required|email|unique:users,' . $user->sub
            ]);

            // Quitar los campos que no se actualizan
            unset($params_array['id']);
            unset($params_array['rol']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            unset($params_array['remember_token']);

            // Actualizar los datos
            $user_update = User::where('id', $user->sub)->update($params_array);

            // Devolver el array con resultado
            $data = array(
                'code' => 200,
                'status' => 'success',
                'message' => $user,
                'user' => $user_update,
                'changes' => $params_array
            );
        } else {
            //Mensaje de error
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'El usuario no esta identificado.'
            );
        }
        return response()->json($data, $data['code']);
    }
    public function upload(Request $request) {
        // Recoger los datos de la petición
        $image = $request->file('file0');
        
        // Validacion de la imagen
        $validate=\Validator::make($request->all(),[
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif,bmp'
        ]);
        
        // Subir el archivo y guardar imagen
        if(!$image || $validate->fails()){
            // Devolver resultado negativo
           $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir imagen.'
            );
        }else{
            $image_name = time().$image->getClientOriginalName();
            \Storage::disk('users')->put($image_name, \File::get($image));
            
            // Devolver resultado positivo
            $data = array(
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            );
        }

        return response()->json($data,$data['code']);
    }
    public function getImage($filename){
        
        $isset= \Storage::disk('users')->exists($filename);
        
        if($isset) {
            $files = \Storage::disk('users')->get($filename);
            return new Response($files, 200);
        }else{
            // Devolver resultado positivo
            $data = array(
                'code' => 404,
                'status' => 'error',
                'image' => 'No existe la imagen'
            );
            return response()->json($data,$data['code']);
        }
    }
    
    public function deleteImage($filename){
        $isset= \Storage::disk('users')->exists($filename);        
        if($isset) {
            // borrar en caso de exisit
            $resultado = \Storage::disk('users')->delete($filename);            
            if($resultado){
                $data = array(
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'Borrado exitoso de '.$filename
                );
            }else{
                $data = array(
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'Fallo al intentar borrar la imagen '.$filename
                );
            }            
        }else{
            // Devolver resultado negativo
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'No existe la imagen '.$filename
            );            
        }
        return response()->json($data,$data['code']);
    }
    public function detail($id){
        $user = User::find($id);
        if(is_object($user)){
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' =>$user
            );
        }else{
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'El usuario no existe.'
            );
        }
        return response()->json($data,$data['code']);
    }
}
