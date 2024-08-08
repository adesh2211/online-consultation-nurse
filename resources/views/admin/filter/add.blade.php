@extends('layouts.vertical', ['title' => 'Filter Type'])

@section('css')
    <!-- Plugins css -->
    <link href="{{asset('assets/libs/selectize/selectize.min.css')}}" rel="stylesheet" type="text/css" />

@endsection

@section('content')

@php
  $is_care = false;
  $width = '';
  if(config('client_connected') && Config::get("client_data")->domain_name=="care_connect_live"){
    $width = '50%';
    $is_care = true;
  }
@endphp
    <div class="row justify-content-center">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <h3>Add Filter Type</h3>
            </div>

            <div class="card-body">
              <form action="{{ route('filters.store',$category->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group row">
                  <div class="col-sm-4">
                      <label>Category Name</label>
                      <input type="text" disabled="" placeholder="Selected Category" class="form-control" id="category_selected" value="{{ $category->name }}" type="text" readonly="">
                      <input  type="hidden" value="{{ $category->id }}" id="last_action" name="category" type="text" readonly="">
                      @if ($errors->has('category'))
                        <span class="text-danger">{{ $errors->first('category') }}</span>
                      @endif
                  </div>
                <div class="col-sm-4">
                @if(!$is_care)
                      <label>Is Multi-Select</label>
                        <select  class="form-control" name="multiselect">
                          <option value="1" <?php echo (old('multiselect')=='true')?"selected":'' ?>>True</option>
                          <option value="0" <?php echo (old('multiselect')=='false')?"selected":'' ?>>False</option>
                        </select>
                        @if ($errors->has('multiselect'))
                          <span class="text-danger">{{ $errors->first('multiselect') }}</span>
                        @endif
                  @endif
                    </div>
                </div>

                 <div class="form-group">
                  <div class="row">
                  <div class="col-sm-4">
                    <label>Filter Name</label>
                    <input type="text" required="" name="filter_name" class="form-control" value="{{ old('filter_name') }}" placeholder="Filter Name">
                    @if ($errors->has('filter_name'))
                      <span class="text-danger">{{ $errors->first('filter_name') }}</span>
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
              </div>

              @if($is_care)
                <div class="form-group row">
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
              
              <div class="form-group" id="option_value">
                 <div class="row">
                  <div class="col-sm-11">
                      <label>Filter Options</label>
                      @php $show_price = false; @endphp
                      <div class="wrapper_class">
                        <div>
                          <br>
                        <div class="input-group" style="width: {{ $width }}">
                              <input type="text" class="form-control is-warning" name="filter_option[name][]" placeholder="Filter Option">
                              @if(Config('client_connected') && Config::get("client_data")->domain_name=="intely")
                              @php $show_price = true; @endphp
                              <input type="number" placeholder="Price" class="form-control" name="filter_option[price][]">
                              @endif
                              @if(!$is_care)
                                <textarea  class="form-control is-warning" name="filter_option[description][]" placeholder="Address/Description"></textarea>
                                <input type="file" class="form-control" name="filter_option[image][]">
                                <img src="" id="profile-img-tag-icon" height="50" width="50" />
                              @endif
                              <div>
                              <span class="btn btn-danger delete_icon">Delete - </span>
                              </div>
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
      var width = "{{ $width }}";
      var wrapper         = $(".wrapper_class");
      var add_button      = $(".add_more_option");
      var show_price = "{{ $show_price }}";
      var price = '';
      if(show_price){
        price = '<input type="number" placeholder="Price" class="form-control" name="filter_option[price][]">';
      }
      var x = 1;
      $(add_button).click(function(e){
          e.preventDefault();
          x++;

          let file = '<textarea  class="form-control is-warning" name="filter_option[description][]" placeholder="Address/Description"></textarea><input type="file" class="form-control" name="filter_option[image][]"><img src="" id="profile-img-tag-icon" height="50" width="50" />';
          if(is_care=="1"){
              file = '';
          }
          $(wrapper).append('<div><br><div class="input-group" style="width:'+width+'"><input type="text" class="form-control is-warning" name="filter_option[name][]"  placeholder="Filter Option">'+price+file+'<div><span class="btn btn-danger delete_icon">Delete - </span></div></div></div>');
      });
   
      $(wrapper).on("click",".delete_icon", function(e){
          e.preventDefault(); $(this).parent('div').parent('div').remove(); x--;
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
<!-- <script type="text/javascript">
  $(function () {
  $('.selectize-close-btn').selectize({
            plugins: ['remove_button'],
            persist: false,
            create: true,
            render: {
                item: function(data, escape) {
                    return '<div>"' + escape((data.text)) + '"</div>';
                }
            },
            onDelete: function(values) {
                return confirm(values.length > 1 ? 'Are you sure you want to remove these ' + values.length + ' items?' : 'Are you sure you want to remove "' + values[0] + '"?');
            }
        });
});
</script> -->
@endsection