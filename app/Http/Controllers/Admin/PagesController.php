<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class PagesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pages = Page::orderBy('id','desc')->get();
        return view('admin.pages')->with(['pages'=>$pages]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.create_page');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $validator = \Validator::make($request->all(), [
            'title' => 'required',
            'body' => 'required',
            'status'=>'required',
            'app_type'=>'required',
            'slug'=>'required',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $input = $request->all();
        $page = new Page();
        $slug = $input['slug'];
        if($slug=='other'){
            $slug = $input['title'];
        }
        $page->slug = $page->createSlug($slug,$input['app_type']);
        $page->title = $input['title'];
        $page->app_type = $input['app_type'];
        $page->author_id = $user->id;
        $page->status = $input['status'];
        $page->body = $input['body'];
        $page->save();
        return redirect('admin/pages');

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Model\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function show(Page $page)
    {
       return view('admin.pages.view')->with(['page'=>$page]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Model\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function showPageBySlug(Request $request,$slug='')
    {
        $app_type = isset($request->app_type)?$request->app_type:'';
        $page = Page::query();
        $page = $page->whereSlug($slug);
        if($app_type){
            $page = $page->where('app_type',$app_type);
        }
        $page = $page->first();
        if($page){
            return view('admin.pages.view')->with('page', $page);
        }else{
            abort(404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function edit(Page $page)
    {
        return view('admin.pages.update')->with(['page'=>$page]); 
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Page $page)
    {
        $slug_exist = Page::where(['app_type'=>$request->app_type,'slug'=>$page->slug])->where('id','!=',$page->id)->first();
        if($slug_exist){
            return back()->withErrors(['this slug already added to '.$request->app_type.' side'])->withInput();
        }
        $page->app_type = $request->app_type;
        $page->title = $request->title;
        $page->body = $request->body;
        $page->status = $request->status;
        $page->save();
        return redirect('admin/pages');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function destroy(Page $page)
    {
        if($page->delete()){
            return response()->json(['status'=>'success']);
        }else{
            return response()->json(['status'=>'error']);
        }
    }
}
