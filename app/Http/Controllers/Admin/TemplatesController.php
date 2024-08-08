<?php

namespace App\Http\Controllers\Admin;

use App\Model\Template;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
class TemplatesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $templates = Template::get();

        return view('admin.template.view')->with('templates',$templates);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()    
    {
        return view('admin.template.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $msg = [];
        $rule = [
              'template_name' => 'required',
              'message' => 'required'
        ];
        $validator = \Validator::make($request->all(),$rule,$msg);
        if ($validator->fails()) {
              return back()->withErrors($validator)->withInput();
        }
        $template = new Template();
        $template->template_name = $input['template_name'];
        // $template->type = $input['type'];
        $template->message = $input['message'];
        $template->save();
        return redirect('admin/templates');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Template  $feed
     * @return \Illuminate\Http\Response
     */
    public function show(Template $template)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function edit(Template $template)
    {
        return view('admin.template.update',compact('template'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Template $template, $id)
    {
        $input = $request->all();
        $msg = [];
        $rule = [
              'template_name' => 'required',
              'message' => 'required'
        ];
        $validator = \Validator::make($request->all(),$rule,$msg);
        if ($validator->fails()) {
              return back()->withErrors($validator)->withInput();
        }
        $get_templete = template::find($id);
        $get_templete->template_name = $input['template_name'];
        $get_templete->type = $input['type'];
        $get_templete->message = $input['message'];
        $get_templete->save();
        return redirect('admin/templates');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function destroy(Template $template)
    {
       
    }
}
