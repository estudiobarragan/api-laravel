<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;
use App\User;

class JwtAuth{
    public $key;
    public function __construct(){
        $this->key = 'clave-secreta_4560425*#*';
    }
    public function signup($email, $password, $getToken=null) {      
        // Buscar si existe el usuario con sus credenciales
        $user = User::where([
           'email' => $email,
            'password' => $password
        ])->first();
        // Comprobar si son correctas (objeto)
        $signup = false;
        if(is_object($user)){
            $signup =true;
        }
        // Generar el token con los datos del usuario identificados
        if($signup){
            $token=array(
                'sub'       =>  $user->id,
                'email'     =>  $user->email,
                'name'      =>  $user->name,
                'surname'   =>  $user->surname,
                'description'   => $user->description,
                'image'         => $user->image,
                'iat'       =>  time(),
                'exp'       =>  time()+ 7*24*60*60 //segundo de una semana caduca
            );
            
            $jwt =JWT::encode($token,$this->key,'HS256');
             
            // Devolver los datos decodificados o el token, en función de un parametro
            $decoded= JWT::decode($jwt, $this->key,['HS256']);
            if(is_null($getToken)){
                $data = $jwt;
            } else {
                $data =$decoded;
            }
        }else{
            $data=array(
                'status' => 'error',
                'messsage'  => 'Login incorrecto',
            );
        }
        
        return $data;
    }
    
    public function CheckToken($jwt, $getIdentity = false) {
        $auth = false;
        try{
            $jwt = str_replace('"','',$jwt);
            $decode = JWT::decode($jwt, $this->key, ['HS256']);
        } catch(\UnexpectedValueException $e){
        $auth = false;
        } catch (\DomainException $ex) {
            $auth = false;
        }
        if(!empty($decode)&& is_object($decode)&& isset($decode->sub)){
            $auth = true;
        }else{
            $auth = false;
        }
        if($getIdentity){
            return $decode;
        }
        return $auth;
    }

}
