@extends('layouts.vertical', ['title' => 'Edit '.__('text.Cat. Service Type')])
@section('css')
    <!-- Plugins css -->
    <link href="{{asset('assets/libs/ion-rangeslider/ion-rangeslider.min.css')}}" rel="stylesheet" type="text/css" />
    <style>
.remCF {
    top: 35px !important;
    position: relative !important;
}
</style>
@endsection
@section('content')
  <section class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-left">
                <li class="breadcrumb-item"><a href="">Home</a></li>
                <li class="breadcrumb-item active"><a href="{{ url('admin/categories').'/'.$category->id.'/edit'}}" >{{ $categoryservicetype->service->type }} categories</a></li>
                <li class="breadcrumb-item active"> Edit</li>
              </ol>
            </div>
          </div>
        </div><!-- /.container-fluid -->
      </section>
    
    <div class="row justify-content-center">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <h3>Edit {{ __('text.Cat. Service Type')}}</h3>
            </div>

            <div class="card-body">
              <form action="{{ url('admin/categories/').'/'.$category->id.'/service/'.$categoryservicetype->id.'/edit'}}" method="POST" enctype="multipart/form-data">
                @csrf
                 <input type="hidden" name="_method" value="PUT">
                 <input type="hidden" name="service_id" value="{{ $categoryservicetype->id }}">
                 <div class="form-group">
                  <div class="row">
                  <div class="col-sm-4">
                    <label>Service Name</label>
                    <input type="text"  disabled="" class="form-control" value="{{ $categoryservicetype->service->type }}">
                  </div>
                  <div class="col-sm-4">
                    <!-- <div class="form-group col-sm-4"> -->
                      <label>Category</label>
                      <input type="text" placeholder="Selected Category" class="form-control" id="category_selected" name="category_name"  type="text" value="{{ old('category_name')??$categoryservicetype->category->name }}" readonly="">
                  </div>
                </div>
              </div>
              <div class="form-group">
                  <div class="row">
                  <div class="col-sm-4">
                    <label>Booking Gap Duration</label>
                    <input type="number" min="5" name="gap_duration" class="form-control" value="{{ old('gap_duration')??$categoryservicetype->gap_duration}}" placeholder="Booking Gap Duration in seconds">
                    @if ($errors->has('gap_duration'))
                          <span class="text-danger">{{ $errors->first('gap_duration') }}</span>
                    @endif
                  </div>
                  <div class="col-sm-4">
                   <label>Minimum Duration</label>
                        <input type="number" min="5" name="minimum_duration" class="form-control" value="{{ old('minimum_duration')??$categoryservicetype->minimum_duration }}" placeholder="Minimum Duration in seconds">
                        @if ($errors->has('minimum_duration'))
                          <span class="text-danger">{{ $errors->first('minimum_duration') }}</span>
                        @endif
                  </div>
                  <div class="col-sm-4">
                   <label>Price Calculation Active</label>
                       <select  class="form-control" name="price_calculation_active">
                            @if($categoryservicetype->price_fixed!==null)
                               <option value="fixed_price" selected="">Fixed Price</option>
                               <option value="price_range" >Price Range</option>
                            @else
                               <option value="fixed_price">Fixed Price</option>
                               <option value="price_range" selected="">Price Range</option>
                            @endif
                        </select>
                        @if ($errors->has('price_calculation_active'))
                          <span class="text-danger">{{ $errors->first('price_calculation_active') }}</span>
                        @endif
                  </div>
                </div>
              </div>
              <div class="form-group">
                  <div class="row">
                  <div class="col-sm-4">
                    <label>Price Fixed</label>
                    <input type="number"  name="fixed_value" class="form-control" value="{{ old('fixed_value')??$categoryservicetype->price_fixed }}" placeholder="Add Fixed Price ">
                    @if ($errors->has('fixed_value'))
                          <span class="text-danger">{{ $errors->first('fixed_value') }}</span>
                    @endif
                  </div>
                  <div class="col-sm-4">
                   <label>Price Range</label>
                      <div class="slider-green">
                        <input id="range_04" type="text" name="price_range"  class="form-control">
                      </div>
                        @if ($errors->has('price_range'))
                          <span class="text-danger">{{ $errors->first('price_range') }}</span>
                        @endif
                  </div>
                  <div class="col-sm-4">
                   <label>Active</label>
                       <select class="form-control" name="is_active">
                            <option value="1" <?php echo ((old('is_active')=='1')?"selected":'')||(($categoryservicetype->is_active=='1')?"selected":'') ?>>True</option>
                            <option value="0" <?php echo ((old('is_active')=='0')?"selected":'')||(($categoryservicetype->is_active=='0')?"selected":'') ?>>False</option>
                        </select>
                        @if ($errors->has('is_active'))
                          <span class="text-danger">{{ $errors->first('is_active') }}</span>
                        @endif
                  </div>
                </div>
              </div>
              @if(Config('client_connected') && Config::get("client_data")->domain_name=="curenik")
              @if(sizeOf($emergencytimeslots)> 0 )
              <div class="form-group  emergency_slot_time_container">
              
              @foreach($emergencytimeslots as $timeslot)
                <div class="row customFields">
                 <div class="col-sm-4">
                    <label>Start Time</label>
                    <input type="time" class="form-control" placeholder="04:30" name="start_time[]" value="{{ $timeslot->start_time }}"/>
                  
                  </div>
                  <div class="col-sm-4">
                    <label>End Time</label>
                    <input type="time" class="form-control" placeholder="04:30" name="end_time[]" value="{{ $timeslot->end_time }}"/>
                   
                    <input type="hidden" class="form-control"  name="emergency_slot_id[]" value="{{ $timeslot->id }}"/>
                  
                  </div>
                  <div class="col-sm-3">
                    <label> Price</label>
                    <input class="form-control" type="number" name="price[]" value="{{ $timeslot->price }}">
                  
                  </div>
                  <div class="col-sm-1"><label></label> <a class="remCF" href="#" data-url= '{{url("admin/services/delete")}}/{{$timeslot->id}}'  data-category = '{{$category->id}}'  data-id = '{{$timeslot->id}}'  data-category_type_id = '{{ $timeslot->category_sevice_type }}'><i class="fas fa-trash-alt"></i></a></div>
                </div>
              @endforeach
             
              </div>
              <div class="row">
                  <div class="col-12">
                      <a class="newrow" href="#"><i class="fas fa-plus"></i> New Interval</a>
                  </div>
              </div>
              @endif
              @endif
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
      <!-- Plugins js-->
      <script src="{{asset('assets/libs/ion-rangeslider/ion-rangeslider.min.js')}}"></script>
      <script type="text/javascript">
        $(document).ready(function () {
            $("#range_04").ionRangeSlider({
                type: "double",
                grid: true,
                min: 5,
                max: 100,
                from:"{{ $categoryservicetype->price_minimum }}",
                to: "{{ $categoryservicetype->price_maximum }}"
            });
        });

        $('.newrow').click(function(){
         $(".emergency_slot_time_container").append('<div class="row customFields"><div class="col-sm-4"><label>Start Time</label><input type="time" class="form-control" placeholder="04:30" name="start_time[]"/></div><div class="col-sm-4"><label>End Time</label><input type="time" class="form-control" placeholder="04:30" name="end_time[]"/></div><div class="col-sm-3"><label> Price</label><input class="form-control" type="number" name="price[]"></div><div class="col-sm-1"><label></label> <a class="remCF" href="#"><i class="fas fa-trash-alt"></i></a></div></div>');
        });

  $(".emergency_slot_time_container").on('click','.remCF',function(){

    var slotId = $(this).attr('data-id');

    var categoryTypeId = $(this).attr('data-category_type_id');

    var categoryId = $(this).attr('data-category_id');

    var delete_url = $(this).attr('data-url');

    $.post(delete_url, { _token: "{{ csrf_token() }}", slotId: slotId , categoryTypeId: categoryTypeId  }).done(function(data){
                    console.log(data);
                    if(data.status == 'success')
                    {
                      alert(data.message);
                      $(this).closest('.customFields').remove();
                      //location.reload();
                    }
                });

     $(this).closest('.customFields').remove();
      // $(this).parent(".new_row").remove();
  });
      </script>
  @endsection