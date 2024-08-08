<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Model\Emsat;
use Auth;
class EmsatController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
         $emsats = Emsat::orderBy('id','DESC')->get();
        return view('admin.emsat.index', compact('emsats'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.emsat.add');
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
                'title'      => 'required|unique:emsats,title',
                'question' => 'required',
                'marks' => 'required',
          ]);
          if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
          }
          $input = $request->all();
          $icon = null;
          if($request->hasfile('icon') && $image = $request->file('icon')){
          		$icon = $this->imageUpload($image);
          }
          $Emsat = new Emsat();
          $Emsat->title = $input['title'];
          $Emsat->question = $input['question'];
          $Emsat->marks = $input['marks'];
          $Emsat->icon = $icon;
          $Emsat->created_by = $user->id;
          $Emsat->save();
          return redirect('admin/emsat');
    }

    private function imageUpload($image){
	        $extension = $image->getClientOriginalExtension();
	        $filename = str_replace(' ','', md5(time()).'_'.$image->getClientOriginalName());
	        $thumb = \Image::make($image)->resize(100, 100,
	          function ($constraint) {
	              $constraint->aspectRatio();
	          })->encode($extension);
	        $normal = \Image::make($image)->resize(260, 260,
	          function ($constraint) {
	              $constraint->aspectRatio();
	          })->encode($extension);
	        $big = \Image::make($image)->encode($extension);
	        $_800x800 = \Image::make($image)->resize(800, 800,
	          function ($constraint) {
	              $constraint->aspectRatio();
	          })->encode($extension);
	        $_400x400 = \Image::make($image)->resize(400, 400,
	          function ($constraint) {
	              $constraint->aspectRatio();
	          })->encode($extension);
	        \Storage::disk('spaces')->put('thumbs/'.$filename, (string)$thumb, 'public');
	        \Storage::disk('spaces')->put('uploads/'.$filename, (string)$normal, 'public');
	        \Storage::disk('spaces')->put('original/'.$filename, (string)$big, 'public');
	        \Storage::disk('spaces')->put('800x800/'.$filename, (string)$_800x800, 'public');
	        \Storage::disk('spaces')->put('400x400/'.$filename, (string)$_400x400, 'public');
	      return $filename;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Model\CustomMasterField  $customMasterField
     * @return \Illuminate\Http\Response
     */
    public function show(CustomMasterField $customMasterField)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\CustomMasterField  $customMasterField
     * @return \Illuminate\Http\Response
     */
    public function edit(Emsat $emsat)
    {
        return view('admin.emsat.edit', compact('emsat'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\CustomMasterField  $customMasterField
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Emsat $emsat)
    {
        $validator = \Validator::make($request->all(), [
                'title'      => 'required|unique:emsats,title,'.$emsat->id,
                'question' => 'required',
                'marks' => 'required',
          ]);
          if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
          }
          $icon = $emsat->icon;
          if($request->hasfile('icon') && $image = $request->file('icon')){
          		$icon = $this->imageUpload($image);
          }
          $input = $request->all();
          $emsat->title = $input['title'];
          $emsat->question = $input['question'];
          $emsat->marks = $input['marks'];
          $emsat->icon = $icon;
          $emsat->save();
          return redirect('admin/emsat');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\CustomMasterField  $customMasterField
     * @return \Illuminate\Http\Response
     */
    public function destroy(Emsat $emsat)
    {
        if($emsat->delete()){
            return response()->json(['status'=>'success']);
        }else{
            return response()->json(['status'=>'error']);
        }
    }
}
