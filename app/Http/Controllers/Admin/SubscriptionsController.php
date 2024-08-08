<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\PackagePlan;
use Illuminate\Http\Request;

class SubscriptionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $packages = PackagePlan::orderBy('id','DESC')->get();
        return view('admin.subscriptions.index',compact('packages'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = $this->getTypes();
        return view('admin.subscriptions.add',compact('categories'));
    }

    private function getTypes(){
        $types = ['monthly'=>'Monthly','half_yearly'=>'Half Yearly','yearly'=>'Yearly'];
        return $types;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $msg = [];
        $rules = [
                'title' => 'required',
                'price'      => 'required|integer|min:1',
                'total_session' => 'required|integer|min:1',
                'type' => 'required'
          ];
          $input = $request->all();
          $validator = \Validator::make($request->all(),$rules,$msg);
          if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
          }

          $package = new PackagePlan();
          if($request->hasfile('image')) {
            if ($image = $request->file('image')) {
                $extension = $image->getClientOriginalExtension();
                $filename = str_replace(' ','', md5(time()).'_'.$image->getClientOriginalName());
                $thumb = \Image::make($image)->resize(100, 100,
                  function ($constraint) {
                      $constraint->aspectRatio();
                  })->encode($extension);
                $normal = \Image::make($image)->resize(400, 480,
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
                \Storage::disk('spaces')->put('original/'.$filename, (string)$big, 'public');
                $package->image_icon = $filename;
            }
          }
          $package->title = $input['title'];
          $package->description = isset($input['description'])?$input['description']:null;
          $package->price = $input['price'];
          $package->total_session = $input['total_session'];
          $package->type = $input['type'];
          $package->save();
          return redirect('admin/subscriptions');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Model\PackagePlan  $packagePlan
     * @return \Illuminate\Http\Response
     */
    public function show(PackagePlan $packagePlan)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\PackagePlan  $packagePlan
     * @return \Illuminate\Http\Response
     */
    public function edit(PackagePlan $packagePlan)
    {
         // print_r($packagePlan);die;
         $categories = $this->getTypes();
        return view('admin.subscriptions.edit',compact('categories','packagePlan'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\PackagePlan  $packagePlan
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PackagePlan $packagePlan)
    {
        $msg = [];
         $rules = [
                'title' => 'required',
                'price'      => 'required|integer|min:1',
                'total_session' => 'required|integer|min:1',
                // 'type' => 'required'
          ];
          $input = $request->all();
          $validator = \Validator::make($request->all(),$rules,$msg);
          if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
          }
          if($request->hasfile('image')) {
            if ($image = $request->file('image')) {
                $extension = $image->getClientOriginalExtension();
                $filename = str_replace(' ','', md5(time()).'_'.$image->getClientOriginalName());
                $thumb = \Image::make($image)->resize(100, 100,
                  function ($constraint) {
                      $constraint->aspectRatio();
                  })->encode($extension);
                $normal = \Image::make($image)->resize(400, 400,
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
                \Storage::disk('spaces')->put('original/'.$filename, (string)$big, 'public');
                $packagePlan->image_icon = $filename;
            }
          }
          $packagePlan->title = $input['title'];
          $packagePlan->description = isset($input['description'])?$input['description']:$packagePlan->description;
          $packagePlan->price = $input['price'];
          $packagePlan->total_session = $input['total_session'];
          // $package->type = $input['type'];
          $packagePlan->save();
          return redirect('admin/subscriptions');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\PackagePlan  $packagePlan
     * @return \Illuminate\Http\Response
     */
    public function destroy(PackagePlan $packagePlan)
    {
        if($page->delete()){
            return response()->json(['status'=>'success']);
        }else{
            return response()->json(['status'=>'error']);
        }
    }
}
