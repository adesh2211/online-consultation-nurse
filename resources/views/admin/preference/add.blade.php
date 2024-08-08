@extends('layouts.vertical', ['title' => __('text.Master Preference') ])

@section('css')
    <!-- Plugins css -->
    <link href="{{asset('assets/libs/selectize/selectize.min.css')}}" rel="stylesheet" type="text/css" />

@endsection

@section('content')
@php
  $is_care = false;
  if(config('client_connected') && Config::get("client_data")->domain_name=="care_connect_live"){
    $is_care = true;
  }
@endphp
    <div class="row justify-content-center">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <h3>Add {{  __('text.Master Preference') }}</h3>
            </div>

            <div class="card-body">
              <form action="{{ route('preferences.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group row">
                  @if(config('client_connected') && Config::get("client_data")->domain_name!=="care_connect_live")
                   <div class="col-sm-4">
                      <label>Is Multi-Select</label>
                        <select  class="form-control" name="multiselect">
                          <option value="1" <?php echo (old('multiselect')=="1")?"selected":'' ?>>True</option>
                          <option value="0" <?php echo (old('multiselect')=="0")?"selected":'' ?>>False</option>
                        </select>
                        @if ($errors->has('multiselect'))
                          <span class="text-danger">{{ $errors->first('multiselect') }}</span>
                        @endif
                    </div>
                    @endif
                    <div class="col-sm-4">
                      <label>Is Required</label>
                        <select  class="form-control" name="is_required">
                          <option value="1" <?php echo (old('is_required')=="1")?"selected":'' ?>>True</option>
                          <option value="0" <?php echo (old('is_required')=="0")?"selected":'' ?>>False</option>
                        </select>
                        @if ($errors->has('is_required'))
                          <span class="text-danger">{{ $errors->first('is_required') }}</span>
                        @endif
                    </div>
                    <div class="col-sm-4">
                      <label>Preference Name</label>
                      <input name="preference_name" required="" type="text" value="{{ old('preference_name') }}" class="form-control" placeholder="Preference Name" >
                       @if ($errors->has('preference_name'))
                        <span class="text-danger">{{ $errors->first('preference_name') }}</span>
                      @endif
                  </div>
                </div>
              @if(config('client_connected') && Config::get("client_data")->domain_name=="care_connect_live")
                <div class="form-group row">
                    <div class="col-sm-4">
                        <label>Input Type</label>
                        <select id="input_type"  class="form-control" name="input_type">
                          <option value="dropdown_single" <?php echo (old('input_type')=="dropdown_single")?"selected":'' ?>>DropDown (SingleSelect)</option>
                          <option value="dropdown_multi" <?php echo (old('input_type')=="dropdown_multi")?"selected":'' ?>>DropDown (MultiSelect)</option>
                          <option value="textbox" <?php echo (old('input_type')=="textbox")?"selected":'' ?>>TextBox</option>
                          <option value="checkbox" <?php echo (old('input_type')=="checkbox")?"selected":'' ?>>CheckBox</option>
                          <option value="radio_button" <?php echo (old('input_type')=="radio_button")?"selected":'' ?>>Radio Button</option>
                        </select>
                        @if ($errors->has('input_type'))
                          <span class="text-danger">{{ $errors->first('input_type') }}</span>
                        @endif
                    </div>
                    <div id="data_type" class="col-sm-4" style="display: none;">
                      <label>Data Type</label>
                        <select  class="form-control" name="data_type" required="">
                          <option value="number" <?php echo (old('data_type')=="number")?"selected":'' ?>>Number</option>
                          <option value="string" <?php echo (old('data_type')=="string")?"selected":'' ?>>String</option>
                        </select>
                        @if ($errors->has('data_type'))
                          <span class="text-danger">{{ $errors->first('data_type') }}</span>
                        @endif
                    </div>
                </div>
              @endif

              @if(config('client_connected') && Config::get("client_data")->domain_name=="physiotherapist")
                <div class="form-group row">
                  <input type="hidden" name="module_table" value="requests">
                  <div class="col-sm-4">
                      <label>Show on APP</label>
                        <select  class="form-control" name="show_on_app">
                          <option value="both" <?php echo (old('show_on_app')=="both")?"selected":'' ?>>Both</option>
                          <option value="user" <?php echo (old('show_on_app')=="user")?"selected":'' ?>>Patient</option>
                          <option value="sp" <?php echo (old('show_on_app')=="sp")?"selected":'' ?>>Nurse</option>
                        </select>
                        @if ($errors->has('show_on_app'))
                          <span class="text-danger">{{ $errors->first('show_on_app') }}</span>
                        @endif
                    </div>
                    <div class="col-sm-4">
                      <label>Type</label>
                        <select  class="form-control" name="type" required="">
                          <option value="covid" <?php echo (old('type')=="covid")?"selected":'' ?>>Covid</option>
                        </select>
                        @if ($errors->has('type'))
                          <span class="text-danger">{{ $errors->first('type') }}</span>
                        @endif
                    </div>
                </div>
              @endif
              <div class="form-group" id="option_value">
                 <div class="row">
                  <div class="col-sm-4">
                      <label>Options</label>
                      <div class="wrapper_class" style="width: 700px;">
                        <div>
                        <div class="input-group">
                              <input type="text" class="form-control is-warning" name="filter_option[name][]" placeholder="Option">
                              @if(!$is_care)
                                <input type="file" class="form-control" name="filter_option[image][]">
                              @endif
                              <span class="btn btn-danger delete_icon">Delete - </span>
                        </div>
                        </div>
                      </div>
                      <span class="btn btn-primary add_more_option">Add More +</span>
                       @if ($errors->has('filter_option'))
                        <span class="text-danger">{{ $errors->first('filter_option') }}</span>
                      @endif
                  </div>
                </div>
              </div>
                <div class="form-group">
                  <button type="submit" class="btn btn-primary">Create</button>
                </div>
              </form>
            </div>
          </div>
        </div>
    </div>
@endsection

@section('script')

<script type="text/javascript">
  $(document).ready(function() {
      var is_care = "{{ $is_care }}";
      var wrapper         = $(".wrapper_class");
      var add_button      = $(".add_more_option");
   
      var x = 1;
      $(add_button).click(function(e){
          e.preventDefault();
          x++;
          let file = '<input type="file" class="form-control" name="filter_option[image][]">';
          if(is_care=="1"){
              file = '';
          }
          $(wrapper).append('<div><br><div class="input-group"><input type="text" class="form-control is-warning" name="filter_option[name][]"  placeholder="Filter Option">'+file+'<span class="btn btn-danger delete_icon">Delete - </span></div></div>');
      });
   
      $(wrapper).on("click",".delete_icon", function(e){
          e.preventDefault(); $(this).parent('div').parent('div').remove(); x--;
      })

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