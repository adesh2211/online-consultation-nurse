@extends('layouts.vertical', ['title' =>  __('text.Master Preference') ])

@section('css')
    <!-- Plugins css -->
    <link href="{{asset('assets/libs/sweetalert2/sweetalert2.min.css')}}" rel="stylesheet" type="text/css" />

@endsection
<?php
  $select = 0;
  $options = "<option>--Map Work Location--</option>";
  if(isset($masterPreference->mapOptions)){
      $select = 1;
      foreach($masterPreference->mapOptions as $mp){
        $options .= "<option value=".$mp->id.">$mp->name</option>";
      }
  }
 ?>
@section('content')
@php
  $is_care = false;
  if(config('client_connected') && Config::get("client_data")->domain_name=="care_connect_live"){
    $is_care = true;
  }
@endphp
    @if (Session::has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <h4 class="alert-heading">Success!</h4>
                <p>{{ Session::get('success') }}</p>

                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
    @endif
    <div class="row justify-content-center">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <h3>Edit {{  __('text.Master Preference') }}</h3>
            </div>

            <div class="card-body">
              <form action="{{ route('preferences.update',[$masterPreference->id])}}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <div class="form-group row">
                  @if(config('client_connected') && Config::get("client_data")->domain_name!="care_connect_live")
                  <div class="col-sm-4">
                      <label>Is Multi-Select</label>
                        <select  class="form-control" name="multiselect">
                          <option value="">--Select Status--</option>
                          <option value="1" <?php echo (old('multiselect') ?? $masterPreference->is_multi ==1)?"selected":'' ?>>True</option>
                          <option value="0" <?php echo (old('multiselect') ?? $masterPreference->is_multi ==0)?"selected":'' ?>>False</option>
                        </select>
                        @if ($errors->has('multiselect'))
                          <span class="text-danger">{{ $errors->first('multiselect') }}</span>
                        @endif
                    </div>
                      @endif
                    <div class="col-sm-4">
                      <label>Is Required</label>
                        <select  class="form-control" name="is_required">
                          <option value="1" <?php echo ($masterPreference->is_required ==1)?"selected":'' ?>>True</option>
                          <option value="0" <?php echo ($masterPreference->is_required ==0)?"selected":'' ?>>False</option>
                        </select>
                        @if ($errors->has('is_required'))
                          <span class="text-danger">{{ $errors->first('is_required') }}</span>
                        @endif
                    </div>
                    <div class="col-sm-4">
                      <label>Preference Name</label>
                      <input  type="text" name="preference_name" value="{{ $masterPreference->name }}" class="form-control" placeholder="Preference Name">
                       @if ($errors->has('preference_name'))
                        <span class="text-danger">{{ $errors->first('preference_name') }}</span>
                      @endif
                    </div>
                </div>

                @if(config('client_connected') && Config::get("client_data")->domain_name=="intely")
                <div class="form-group row">
                  <div class="col-sm-4">
                      <label>Show on APP</label>
                        <select  class="form-control" name="show_on">
                          <option value="both" <?php echo ($masterPreference->show_on=="both")?"selected":'' ?>>Both</option>
                          <option value="user" <?php echo ($masterPreference->show_on=="user")?"selected":'' ?>>Patient</option>
                          <option value="sp" <?php echo ($masterPreference->show_on=="sp")?"selected":'' ?>>Nurse</option>
                        </select>
                        @if ($errors->has('show_on'))
                          <span class="text-danger">{{ $errors->first('show_on') }}</span>
                        @endif
                    </div>
                    <div class="col-sm-4">
                      <label>Type</label>
                        <select  class="form-control" name="type" required="">
                          <option value="">--Select Type--</option>
                          <option value="covid" <?php echo ($masterPreference->type=="covid")?"selected":'' ?>>Covid</option>
                          <option value="personal_interest" <?php echo ($masterPreference->type=="personal_interest")?"selected":'' ?>>Personal Interest</option>
                          <option value="work_environment" <?php echo ($masterPreference->type=="work_environment")?"selected":'' ?>>Work Environment</option>
                        </select>
                        @if ($errors->has('type'))
                          <span class="text-danger">{{ $errors->first('type') }}</span>
                        @endif
                    </div>
                </div>
                @endif

                @if(config('client_connected') && Config::get("client_data")->domain_name=="care_connect_live")
                <div class="form-group row">
                    <div class="col-sm-4">
                        <label>Input Type</label>
                        <select id="input_type"  class="form-control" name="input_type">
                          <option value="dropdown_single" <?php echo (old('input_type')=="dropdown_single" || $masterPreference->input_type=='dropdown_single' || $masterPreference->input_type=='dropdown')?"selected":'' ?>>DropDown (SingleSelect)</option>
                          <option value="dropdown_multi" <?php echo (old('input_type')=="dropdown_multi" || $masterPreference->input_type=='dropdown_multi')?"selected":'' ?>>DropDown (MultiSelect)</option>
                          <option value="textbox" <?php echo (old('input_type')=="textbox" || $masterPreference->input_type=='textbox')?"selected":'' ?>>TextBox</option>
                          <option value="checkbox" <?php echo (old('input_type')=="checkbox" || $masterPreference->input_type=='checkbox')?"selected":'' ?>>CheckBox</option>
                          <option value="radio_button" <?php echo (old('input_type')=="radio_button" || $masterPreference->input_type=='radio_button')?"selected":'' ?>>Radio Button</option>
                        </select>
                        @if ($errors->has('input_type'))
                          <span class="text-danger">{{ $errors->first('input_type') }}</span>
                        @endif
                    </div>
                    <div id="data_type" class="col-sm-4" style="display: {{ $masterPreference->input_type=='textbox'?'block':'none' }}">
                      <label>Data Type</label>
                        <select  class="form-control" name="data_type" required="">
                          <option value="number" <?php echo (old('data_type')=="number" || $masterPreference->data_type=='number')?"selected":'' ?>>Number</option>
                          <option value="string" <?php echo (old('data_type')=="string"|| $masterPreference->data_type=='string')?"selected":'' ?>>String</option>
                        </select>
                        @if ($errors->has('data_type'))
                          <span class="text-danger">{{ $errors->first('data_type') }}</span>
                        @endif
                    </div>
                </div>
              @endif

                <div class="form-group" id="option_value" style="display: {{ $masterPreference->input_type=='textbox'?'none':'block' }}">
                 <div class="row">
                  <div class="col-sm-4">
                      <label>Options</label>
                      <div class="wrapper_class" style="width: 700px;">
                        @foreach($masterPreference->filter_option as $id=>$filter_value)
                          <div>
                            <br>
                            <div class="input-group">
                                  <input type="text" class="form-control is-warning" name="filter_option[name][{{ $filter_value->id }}]" placeholder=" Option" value="{{ $filter_value->name }}" required="">
                                  @if(!$is_care)
                                   <input type="file" class="form-control" name="filter_option[image][{{ $filter_value->id }}]">
                                   <!-- <img src="{{ asset('/storage/thumbs/'.$filter_value->image) }}" id="profile-img-tag-icon" height="50" width="50" /> -->
                                   <img src="{{ Storage::disk('spaces')->url('thumbs/'.$filter_value->image) }}" id="profile-img-tag-icon" height="50" width="50" />
                                  @endif
                                   @if(isset($masterPreference->mapOptions))
                                   <select class="form-control" name="filter_option[map_option_id][{{ $filter_value->id }}]" >
                                      <option>--Map Work Location--</option>
                                      @foreach($masterPreference->mapOptions as $mp)
                                      <option value="{{ $mp->id }}" {{ ($filter_value->map_option_id==$mp->id)?'selected':'' }}>{{ $mp->name }}</option>
                                      @endforeach
                                    </select>
                                  @endif
                                   <div>
                                    <span class="btn btn-danger delete_icon" data-filtertypeoption_id="{{ $filter_value->id }}">Delete - </span>
                                  </div>
                            </div>
                          </div>
                        @endforeach
                      </div>
                      <br>
                      <span class="btn btn-primary add_more_option">Add More +</span>
                       @if ($errors->has('filter_option'))
                        <span class="text-danger">{{ $errors->first('filter_option') }}</span>
                      @endif
                  </div>
                </div>
              </div>
                <div class="form-group">
                  <button type="submit" class="btn btn-primary">Update</button>
                </div>
              </form>
            </div>
          </div>
        </div>
    </div>
@endsection
@section('script')
<script src="{{asset('assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>
<script type="text/javascript">
  $(document).ready(function() {
      var is_care = "{{ $is_care }}";
      var wrapper         = $(".wrapper_class");
      var add_button      = $(".add_more_option");
      var select = "{{ $select }}";
      var mapOptions = "";
      if(select=="1"){
        mapOptions = '<select class="form-control" name="new_option[map_option_id][]">'+"{!! $options !!}"+'</select>';
      }
      $(add_button).click(function(e){
          e.preventDefault();
          let file = '<input type="file" class="form-control" name="new_option[image][]">';
          if(is_care=="1"){
              file = '';
          }
          $(wrapper).append('<div><br><div class="input-group"><input type="text" class="form-control is-warning" name="new_option[name][]" required="" placeholder="Option">'+file+mapOptions+'<div><span class="btn btn-danger delete_icon_new">Delete - </span></div></div></div>');
      });
   
      $(wrapper).on("click",".delete_icon", function(e){
        var __this = this;
        var filtertypeoption_id = $(this).attr('data-filtertypeoption_id');
        Swal.fire({
          title: 'Are you sure?',
          text: "You won't be able to revert this!",
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
          if (result.value) {
              $.ajax({
                 type:'POST',
                 url:base_url+'/admin/delete_master_option',
                 data:{filtertypeoption_id:filtertypeoption_id},
                 success:function(data){
                    if(data.status=='success'){
                        Swal.fire(
                          'Deleted!',
                          'Option has been deleted.',
                          'success'
                        ).then((result)=>{
                            e.preventDefault(); $(__this).parent('div').parent('div').remove();
                        });
                    }else{
                      Swal.fire(
                          'Error!',data.message,'error'
                        ).then((result)=>{

                        });
                    }
                 }
              });
            }
        });
      });
      $(wrapper).on("click",".delete_icon_new", function(e){
        e.preventDefault(); $(this).parent('div').parent('div').remove();
      });

      $(document).on("change","#input_type", function(e){
          if($("#input_type").val()=='textbox'){
              $("#data_type").css('display','block');
              $("#option_value").css('display','none');
          }else{
              $("#option_value").css('display','block');
              $("#data_type").css('display','none');
          }
      });
});

</script>
@endsection