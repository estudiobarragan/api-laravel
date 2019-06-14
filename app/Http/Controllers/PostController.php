<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Post;
use App\Helpers\JwtAuth;

class PostController extends Controller
{
    /*
     * Pruebas
     *
    public function pruebas(Request $request) {
        return "accion de pruebas de POST controller";
    }
    */
    public function __construct() {
        $this->middleware('api.auth', ['except' => [
                'index',
                'show',
                'getImage',
                'getPostsByCategory',
                'getPostsByUser'
        ]]);
    }

    public function index(){
        $posts = Post::all()->load('category')->load('user');
        
        return response()->json([
            'code' => 200,
            'status' => 'success',
            'posts'=> $posts
        ],200);
    }
    
    public function show($id) {
        $post = Post::find($id);
        if (is_object($post)) {
            $post->load('category')->load('user');
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post
            ];
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'El post no existe'
            ];            
        }
        return response()->json($data,$data['code']);
    }
    public function store(Request $request){
        // Recoger datos por post
        $json = $request->input('json', null);
        $params = json_decode(($json));
        $params_array = json_decode($json, true);
        
        if (!empty($params_array)) {
            // Conseguir datos de usuario identificado        
            $user = $this->getIdentity($request);
        
            // Validar los datos
            $validate = \Validator::make($params_array, [
                'title' => 'required',
                'content' =>'required',
                'category_id' => 'required',
                'image' => 'required'
            ]);

            // Guardar el post
            if ($validate->fails()) {
                $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'No se ha guardado el post, faltan datos.',
                    'params_array' =>$params_array,
                    'params'=>$params
                ];
            } else {
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id =$params->category_id;
                $post->title = $params->title;
                $post->content = $params->content;
                $post->image = $params->image;
                $post->save();
                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'message' => $post
                ];
            }
        }else{
            $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'No has enviado ninguna categoria.',
                    'json' => $json,
                    'params' => $params,
                    'params_array' => $params_array                
                ];
        }
        // devolver el resultado
        return response()->json($data, $data['code']);
    }
    
    public function update($id, Request $request) {
        // Conseguir datos de usuario identificado        
        $user = $this->getIdentity($request);
        
        // Recoger los datos que vienen por post
        $json = $request->input('json', null);
        $params = json_decode(($json));
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            // Validar los datos
            $validate = \Validator::make($params_array, [
                        'title' => 'required',
                        'content' => 'required',
                        'category_id' => 'required'
            ]);
            if ($validate->fails()) {
                $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos no validados correctamente.',
                    'errors'=> $validate->errors(),
                    'json' => $json,
                    'params' => $params,
                    'params_array' => $params_array
                ];
            } else{
                // Quitar lo que no quiero actualizar
                unset($params_array['id']);
                unset($params_array['user_id']);
                unset($params_array['created_at']);
                unset($params_array['user']);
                
                //Buscar el registro
                $post = Post::where('id',$id)
                            ->where('user_id',$user->sub)
                            ->first();
                if (!empty($post) && is_object($post)) {
                    //Actualizar el registro (categoria)
                    $post->update($params_array);
                    $data = [
                        'code' => 200,
                        'status' => 'success',
                        'post' => $post,
                        'changes' => $params_array
                    ];
                } else {
                    $data = [
                        'code' => 400,
                        'status' => 'error',
                        'message' => 'Post inexistente para el usuario actual.'                       
                    ];
                }
                /* Version vieja tener en cuenta
                $where = [
                    'id' => $id,
                    'user_id' =>$user->sub
                    ];
                $post = Post::updateOrCreate($where,$params_array);                 
                 */
                
            }
        } else {
            $data = [
                'user' => $user,
                'code' => 400,
                'status' => 'error',
                'message' => 'Datos enviados incorrectamente.',
                'json' => $json,
                'id'=> $id,
                'params' => $params,
                'params_array' => $params_array
            ];
        }
        // Devolver los datos
        return response()->json($data, $data['code']);
    }
    
    public function destroy($id, Request $request) {
        // Conseguir datos de usuario identificado                
        $user = $this->getIdentity($request);
        
        //  Conseguir el post que queremos
        // antes Post::find($id);
        
        $post = Post::where('id',$id)
                    ->where('user_id',$user->sub)
                    ->first();
        if (!empty($post)) {
            // Saca la imagen para borrarla
            $fileImage = $post->image; 
            $this->deleteImage($fileImage);
            
            // Borrar el registro            
            $post->delete();

            //Devolvemos info
            $data = [
                'code' => 200,
                'status' => 'success',
                'Mensaje' => 'Entrada borrada.',
                'post' => $post
            ];
        }else{
            $data = [
                'code' => 400,
                'status' => 'error',
                'Mensaje' => 'El post no existe o pertenece a otro usuario.',
                'id' => $id
            ];
        }
        return response()->json($data, $data['code']);
    }
    
    private function getIdentity($request){
        // Conseguir datos de usuario identificado        
        $jwtAuth = new JwtAuth();
        $token= $request->header('Authorization',null);
        $user = $jwtAuth->checkToken($token,true);
        return $user;
    }

    public function upload(Request $request){
        // Recoger los datos de la petición
        $image = $request->file('file0');
        
        // Validacion de la imagen
        $validate=\Validator::make($request->all(),[
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif,bmp'
        ]);
        
        // Subir el archivo y guardar imagen
        if(!$image || $validate->fails()){
            // Devolver resultado negativo
           $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir imagen.'
            ];
        }else{
            $image_name = time().$image->getClientOriginalName();
            
            \Storage::disk('images')->put($image_name, \File::get($image));
            
            // Devolver resultado positivo
            $data = [
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            ];
        }
        // Devolver datos
        return response()->json($data, $data['code']);
    }
    
    public function getImage($filename){
        // Comprobar si existe el fichero
        $isset= \Storage::disk('images')->exists($filename);
        
        if($isset) {
            // Devolver resultado positivo
            $files = \Storage::disk('images')->get($filename);
            return new Response($files, 200);
        }else{
            // Devolver resultado negativo
            $data = array(
                'code' => 404,
                'status' => 'error',
                'image' => 'No existe la imagen'
            );
            return response()->json($data,$data['code']);
        }
    }
    public function deleteImage($filename){
        $isset= \Storage::disk('images')->exists($filename);
        
        if($isset) {
            // borrar en caso de exisit
            $resultado = \Storage::disk('images')->delete($filename);            
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
    public function getPostsByCategory($id){
        $posts= Post::where('category_id',$id)->get();
        $posts->load('category')->load('user');
        
        return response()->json([
            'status' =>  'success',
            'posts' => $posts
        ],200);
    }
    
    public function getPostsByUser($id){
        $posts= Post::where('user_id',$id)->get();
        $posts->load('category')->load('user');
        
        return response()->json([
            'status' =>  'success',
            'posts' => $posts
        ],200);
    }
}
