<?php

namespace App\Http\Controllers;

use App\Authorizable;
use App\Permission;
use App\Role;
use Illuminate\Http\Request;
use App\Http\Utils\Response;

class RoleController extends Controller
{
    use Authorizable;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $roles = Role::whereNull('deleted_at')->orderby('name')->get();
        $permissions = Permission::all();

        return view('role.index', compact('roles', 'permissions'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, ['name' => 'required|unique:roles']);

        if( Role::create($request->only('name')) ) {
            flash('Role Added');
        }

        return redirect()->back();
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
        if($role = Role::findOrFail($id)) {
            // admin role has everything
            if($role->name === 'Admin') {
                $role->syncPermissions(Permission::all());
                return redirect()->route('roles.index');
            }

            $permissions = $request->get('permissions', []);

            // dd($permissions);

            $permissions[] = 'view_actions_button';

            $role->syncPermissions($permissions);

            flash( $role->name . ' permissions has been updated.');
        } else {
            flash()->error( 'Role with id '. $id .' note found.');
        }

        return redirect()->route('roles.index');
    }

    public function getRolesPermissions(Request $request)
    {
        $response = new Response();

        try
        {
            $consulta = [];
            $message = 'OK';

            if(isset($request->id)){
                $rol = Role::select('id', 'name')->find($request->id);
                if($rol){
                    $permisos = $rol->permissions()->pluck('name');

                    $consulta['roles']  = [
                            'id' =>  $rol['id'],
                            'name' =>  $rol['name'],
                            'permisos' =>  $permisos
                    ];
                }else{
                    $message = 'No se encuentra registrado';
                }

            }else{
                $consulta['roles'] = Role::select('id', 'name')->get();
            }

            $response->setSuccess(true);
            $response->setEntities($consulta);
            $response->setMessage($message);
        }
        catch(\Exception $e)
        {
            $response->setSuccess(false);
            $response->setError($e->getMessage());
        }

        return response()->json($response->toArray());
    }
}