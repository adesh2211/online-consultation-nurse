<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\Advertisement;
use Illuminate\Http\Request;
use App\Http\Traits\CategoriesTrait;
use DateTime,DateTimeZone;
use Config;
class AdvertisementController extends Controller
{
    use CategoriesTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $advertisements = Advertisement::orderBy('id','DESC')->get();
        return view('admin.advertisement.index')->with(array('advertisements'=>$advertisements));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = $this->parentCategories();
        $service_providers = $this->serviceProviders();
        $users = $this->users();
        $consultclasses = $this->consultClasses();
        return view('admin.advertisement.add',compact('categories','service_providers','consultclasses','users'));
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
                'date_range' => 'required',
                'advertisement_type' => 'required',
                'position' => 'required',
          ];
          if(config('client_connected') && Config::get("client_data")->domain_name == "curenik"){
          
            if(isset($request->advertisement_type)){
              if($request->advertisement_type=='category'){
                  $rule['category']='required';
              }elseif ($request->advertisement_type=='class') {
                  $rule['class']='required';
              }
             
            }
          }
         else{
          if(isset($request->advertisement_type)){
            if($request->advertisement_type=='category'){
                $rule['category']='required';
            }elseif ($request->advertisement_type=='service_provider') {
                $rule['service_provider']='required';
            }
           
          }
         }
         if(isset($request->image)){
             //$rule['image']='required|array|min:1|image|mimes:jpeg,png,jpg,gif,svg|dimensions:min_width=516';
             //$msg['image.dimensions'] = "image should be min_width=516";
         }
         if(isset($request->video)){
          //$rule['image']='required|array|min:1|image|mimes:jpeg,png,jpg,gif,svg|dimensions:min_width=516';
          //$msg['image.dimensions'] = "image should be min_width=516";
        }
        //  if(isset($request->image_mobile)){
        //      $rule['image_mobile']='required|image|mimes:jpeg,png,jpg,gif,svg|dimensions:min_width=516';
        //       $msg['image_mobile.dimensions'] = "image should be min_width=516";
        //  }
         $validator = \Validator::make($request->all(),$rule,$msg);
          if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
          }

          $data = [];
         
          $date_range = explode(' to ', $input['date_range']);
          $end_date =  date('Y-m-d', strtotime($date_range[1]));
          $start_date =  date('Y-m-d', strtotime($date_range[0]));
          // print_r($start_date);die;
          $advertisement = new Advertisement();
          // $advertisement->image = null;
          // $advertisement->video = null;
          $advertisement->start_date = $start_date;
          $advertisement->end_date = $end_date;
          $advertisement->position = $input['position'];
          $advertisement->category_id = $input['category'];
          $advertisement->sp_id = $input['service_provider'];
          $advertisement->user_id = isset($input['user']) ? $input['user'] : NULL;
         // $advertisement->class_id = $input['class'];
          $advertisement->banner_type = $input['advertisement_type'];
          if($request->hasfile('image')) {
            foreach($request->file('image') as $file)
            {
              if ($image = $file) {
                  $extension = $image->getClientOriginalExtension();
                  $filename = str_replace(' ','', md5(time()).'_'.$image->getClientOriginalName());
                  $thumb = \Image::make($image)->resize(100, 100,
                    function ($constraint) {
                        $constraint->aspectRatio();
                    })->encode($extension);
                  $normal = \Image::make($image)->resize(688, 416,
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
                  $data[] = $filename; 
                 
              }
            }
           // return $data;
          }


          $videodata = [];
          //video upload
          if($request->hasfile('video')) {
            foreach($request->file('video') as $file)
            {
                $image = $file;
                  $extension = $image->getClientOriginalExtension();
                  $filename = str_replace(' ','', md5(time()).'_'.$image->getClientOriginalName());
                   $FileEnconded=  \File::get($file);
                  \Storage::disk('spaces')->put('video/'.$filename, (string)$FileEnconded,'public');
                  $videodata[] = $filename; 
            }
            $advertisement->video = json_encode($videodata);
           // return $data;
          }
          
          $advertisement->image = json_encode($data);
          
          $advertisement->save();
        return redirect('admin/advertisement');


    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Model\Advertisement  $advertisement
     * @return \Illuminate\Http\Response
     */
    public function show(Advertisement $advertisement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\Advertisemnet  $advertisement
     * @return \Illuminate\Http\Response
     */
    public function edit(Advertisement $advertisement)
    {
        $categories = $this->parentCategories();
        $service_providers = $this->serviceProviders();
        $users = $this->users();
        $consultclasses = $this->consultClasses();
        $start_date =  date('Y-m-d', strtotime($advertisement->start_date));
        $end_date =  date('Y-m-d', strtotime($advertisement->end_date));
        $advertisement->date_range = $start_date.' to '.$end_date;
        $created_by = \App\User::where('id',$advertisement->created_by)->first();
        if($created_by){
          $advertisement->created_name = $created_by->name;
        }else{
          $advertisement->created_name = 'Admin';
        }
        return view('admin.advertisement.edit',compact('categories','service_providers','consultclasses','advertisement','users'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\Advertisement  $advertisement
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Advertisement $advertisement)
    {
        $input = $request->all();
        $msg = [];
          $rule = [
                'date_range' => 'required',
                'advertisement_type' => 'required',
                'position' => 'required',
          ];

          if(config('client_connected') && Config::get("client_data")->domain_name == "curenik"){
            if(isset($request->advertisement_type)){
              if($request->advertisement_type=='category'){
                  $rule['category']='required';
              }elseif ($request->advertisement_type=='class') {
                  $rule['class']='required';
              }
             
            }
          }
          else{

            if(isset($request->advertisement_type)){
              if($request->advertisement_type=='category'){
                  $rule['category']='required';
              }elseif ($request->advertisement_type=='service_provider') {
                  $rule['service_provider']='required';
              }
            
            }
          }
          
         if($request->hasfile('image')) {
          //  $rule['image']='required|mimes:jpeg,png,jpg,gif,svg|dimensions:min_width=516';
          //  $msg['image.dimensions'] = "image should be min_width=516";
         }
         if($request->hasfile('video')) {
          // $rule['image']='required|image|mimes:jpeg,png,jpg,gif,svg|dimensions:min_width=516';
          // $msg['image.dimensions'] = "image should be min_width=516";
        }
      
         $validator = \Validator::make($request->all(),$rule,$msg);
          if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
          }
          $date_range = explode(' to ', $input['date_range']);
          $start_date =  date('Y-m-d', strtotime($date_range[0]));
          $end_date =  date('Y-m-d', strtotime($date_range[1]));
          $advertisement->start_date = $start_date;
          $advertisement->end_date = $end_date;
          $advertisement->position = $input['position'];
          $advertisement->category_id = $input['category'];
          $advertisement->sp_id = $input['service_provider'];
          $advertisement->user_id = isset($input['user']) ? $input['user'] : NULL;
          //$advertisement->class_id = $input['class'];
          $advertisement->banner_type = $input['advertisement_type'];
          $advertisement->enable = $input['enable'];


          $data=[];

          // fetch old record
          $fetch = Advertisement::where('id',$input['id'])->first();
          $fetch_image = json_decode($fetch->image);
          $fetch_video = json_decode($fetch->video);
          if($fetch_image = '' || $fetch_image = Null )
          {
            $data = [];
          }
          else
          {
            $data = json_decode($fetch->image);
          }

          
           if($request->hasfile('image')) {
            foreach($request->file('image') as $file)
            {
              $image = $file;
                  $extension = $image->getClientOriginalExtension();
                  $filename = str_replace(' ','', md5(time()).'_'.$image->getClientOriginalName());
                  $thumb = \Image::make($image)->resize(100, 100,
                    function ($constraint) {
                        $constraint->aspectRatio();
                    })->encode($extension);
                  $normal = \Image::make($image)->resize(688, 416,
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
                  $data[] = $filename; 
              
            }
           //return $data;
          }

          $videodata = [];
          if($fetch_video = '' || $fetch_video = Null )
          {
            $videodata = [];
          }
          else
          {
            $videodata = json_decode($fetch->video);
          }
          //video upload
          if($request->hasfile('video')) {
            foreach($request->file('video') as $file)
            {
                 $image = $file;
                  $extension = $image->getClientOriginalExtension();
                  $filename = str_replace(' ','', md5(time()).'_'.$image->getClientOriginalName());
                   $FileEnconded=  \File::get($file);
                  \Storage::disk('spaces')->put('video/'.$filename, (string)$FileEnconded,'public');
                  $videodata[] = $filename; 
            }
            $advertisement->video = json_encode($videodata);
           // return $data;
          }
          
          $advertisement->image = json_encode($data);
        
          $advertisement->save();
          return redirect('admin/advertisement');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\Advertisement  $advertisement
     * @return \Illuminate\Http\Response
     */
    public function destroy(Advertisement $advertisement)
    {
       if($advertisement->delete()){
            return response()->json(['status'=>'success']);
        }else{
            return response()->json(['status'=>'error']);
        }
    }
}
