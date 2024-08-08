@extends('layouts.vertical', ['title' => 'Appointments' ])
@section('css')
<link href="{{asset('assets/libs/datatables/datatables.min.css')}}" rel="stylesheet" type="text/css" />
<link href="{{asset('assets/libs/sweetalert2/sweetalert2.min.css')}}" rel="stylesheet" type="text/css" />
<link href="https://coderthemes.com/ubold/layouts/assets/libs/flatpickr/flatpickr.min.css" rel="stylesheet" type="text/css"/>
<link href="https://coderthemes.com/ubold/layouts/assets/libs/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet" type="text/css">
<style>
.accept_btn {
	background: aliceblue;
	padding: 3px 0 10px 68px;
	margin-bottom: 12px;
}
.accept_btn .dropdown-item.notify-item.active.noifi {
	padding: 0 !important;
}
.dropdown-menu.dropdown-menu-right .dropdown-item.notify-item.active.noifi {
	padding: 3px 10px;
}
.dropdown-menu.dropdown-menu-right .dropdown-item.notify-item.active.noifi img {
	width: 45px !important;
	height: 45px !important;
	object-fit: cover;
	margin-right: 13px;
}
</style>
@endsection
@section('content')
<?php 
$category_permission = json_decode(Auth::user()->permission);
$permission = (isset($category_permission->module) && $category_permission->module=='category')?true:false;
$admin = Auth::user()->hasRole('admin');
$service_provider = Auth::user()->hasRole('service_provider');
if(config('client_connected') && Config::get("client_data")->domain_name=="care_connect_live"){
  $doctor_manager = Auth::user()->hasRole('doctor_manager');
}

if(isset($_COOKIE['royo_timZone'])){ $timeZone = $_COOKIE['royo_timZone'];}else{ $timeZone = 'Asia/Calcutta';} ?>
<div class="container-fluid">
		<div class="row">
		    <div class="col-12">
		      <div class="card">
		        <div class="card-header">
		          <h3 class="card-title">Appointments {{ $chats->count() }}</h3>
		          @if($service_provider && $permission)
		          <a href="{{ url('admin/appointment/create')}}" class="btn btn-sm btn-info float-right">Add New Appointment</a>
		          @endif
		        </div>
		        <!-- /.card-header -->
		        <div class="card-body">
				<!-- <div class="row">
					<div class="col-12">
						<h3 class="form-label">Services</h3>
						<div class="col-3">
							<button type="button" class="btn btn-outline-primary" id="">Chat</button>
						</div>

                    </div>
					
				</div> -->
                <div class="mb-3" >
                    <div class="row">
                        <div class="col-9">
						<h3 class="form-label"></h3>
                        </div>
                        <div class="col-3">
                            <label class="form-label">Date</label>
                            <div class="input-group mb-3">
								<input type="text" id="basic-datepicker" class="form-control" placeholder="Basic datepicker">
                               
                                <!-- <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-primary" id="change_date">Submit</button>
                                </div> -->
                            </div>
                        </div>
                    </div>
                </div>
				<div class="btn-group" id="slot_times" role="group" aria-label="Basic example" style="max-width:1000px; overflow-x: auto;">
					@if($slots)
					@foreach($slots as $slot)
						<button type="button"
							@if($slot['time'] == $time)
								class="btn btn-success slot_time active"
							@else
								@if($slot['count'] > 0)
									class="btn btn-warning slot_time"
								@else
									class="btn btn-secondary slot_time"
								@endif
							@endif
							data-time="{{$slot['time']}}"
						>{{$slot['time']}}</button>
					@endforeach
					@endif
				</div>
                 
				<br><br>
				<div id="tablediv" style="display: none;">
		          	<table id="scroll-horizontal-datatable2" class="table w-100 nowrap">
		            <thead>
		            <tr >
		            	<!-- <th>#</th> -->
                        @if(config('client_connected') && Config::get("client_data")->domain_name=="care_connect_live")
                        <th>Token Number</th>
                        @endif
		            	<th>Booking Date</th>
		            	<th>Time</th>
		            	<th>Patient</th>
		            	<th>{{ __('text.Vendor') }} Name</th>
						<th>Waiting Time</th>
						<th>Action</th>
		            	<th>Status</th>
		            	
		            </tr>
		            </thead>
		            <tbody id="container">
					@php $waiting_time=0;
					$abs='true';
					@endphp
		             @foreach($chats as $index => $chat)
					 
					 @if($chat->join_time != null && $chat->status != 'completed')
                    
                       @php  $current_time = new DateTime();
                       		 $join_time = new DateTime($chat->join_time);

								$func = $abs ? 'abs' : 'intval';
        						$diff = $func(strtotime($current_time->format('Y-m-d H:i:s')) - strtotime($join_time->format('Y-m-d H:i:s'))) * 1000;
								$waiting_time = $diff/1000;
								
                        @endphp    
                    @endif
			            <tr id="{{$chat->id}}" data-id="{{ $chat->id }}">
			              <!-- <td>{{ $index+1 }}</td> -->
                            @if(config('client_connected') && (Config::get("client_data")->domain_name=="care_connect_live"))
                            <td>{{$chat->token_number}}</td>
                            @endif
			              <td>{{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $chat->booking_date)->tz($timeZone)->format('M d, Y') }}</td>
			              <td>{{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $chat->booking_date)->tz($timeZone)->format('h:i A') }}</td>
			              <td>{{ $chat->cus_info->name }}</td>
						  
			              @php $d_detail = $chat->getCustomDoctor($chat->id); @endphp
			              @if($permission)
			              		<td>{{ isset($d_detail->first_name)?$d_detail->first_name:'NA' }}</td>
			              @else
				              <td>{{ isset($d_detail->first_name)?$d_detail->first_name:$chat->sr_info->name  }}</td>
			              @endif
						  @if($chat->join_time != null)
						  <td  id="timer_{{$chat->id}}"  ></td>
						  @else
						  <td></td>
						  @endif
						 
						  <td><a target=_blank href="https://meet.inhomed.com/Call_202001_{{$chat->id}}" class="btn btn-success" type="button">Join Call</a></td>

			              <td> <lable class="">{{ ($chat->requesthistory->status = 'accept')?'Accepted':'NA' }}</label> </td>
			             
			           
			            </tr>

						@if($chat->join_time != null)

						<script>
							var _rem_time = "{{$waiting_time }}";
							var timerVar = setInterval(countTimer, 1000);
							var totalSeconds = _rem_time;
							function countTimer() {
							++totalSeconds;
							var hour = Math.floor(totalSeconds /3600);
							var minute = Math.floor((totalSeconds - hour*3600)/60);
							var seconds = totalSeconds - (hour*3600 + minute*60);
							if(hour < 10)
								hour = "0"+hour;
							if(minute < 10)
								minute = "0"+minute;
							if(seconds < 10)
								seconds = "0"+seconds;
							document.getElementById("timer_{{$chat->id}}").innerHTML = hour + ":" + minute + ":" + seconds;
							}
						</script>
						@endif
			         @endforeach   
		        	</tbody>
		          </table>
				</div>

				
				</div>
	<!-- /.card-body -->
	</div>
	<!-- /.card -->
	</div>
	<!-- /.col -->
	</div>
</div>

@endsection
@section('script')
<script src="https://bevacqua.github.io/dragula/dist/dragula.js"></script>
 <script src="https://coderthemes.com/ubold/layouts/assets/libs/flatpickr/flatpickr.min.js"></script> 
<script src="{{asset('assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>
<script src="https://coderthemes.com/ubold/layouts/assets/libs/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
<script type="text/javascript">

var _selected_time = null;

var today = new Date();
var date = today.getFullYear()+'-'+(today.getMonth()+1)+'-'+today.getDate();
var time = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();
//alert (date+' '+time);

$("#basic-datepicker").flatpickr({
  //  enableTime: true,
  
    dateFormat:"m-d-Y",
    defaultDate: "{{ $slotdate }}",
    onClose: function(selectedDates, dateStr, instance){
       _selected_time = dateStr;
    }
});

$("#basic-datepicker").change(function(){
    $("#basic-datepicker").val(_selected_time);
    window.location.href = "{{ url('/admin/requests') }}?doctor_id={{$doctor_id}}&slot_date="+_selected_time;
    // alert(_selected_time);
});
$("#tablediv").show();

$("#slot_times .slot_time").click(function(){
	var _time = $(this).attr("data-time");
//	alert(_time);
	window.location.href = "{{ url('/admin/requests') }}?doctor_id={{$doctor_id}}&slot_date={{$slotdate}}&slot_time="+_time;
	

});


var container = document.getElementById('container');
var rows = container.children;

// forEach method from https://toddmotto.com/ditch-the-array-foreach-call-nodelist-hack/
var nodeListForEach = function (array, callback, scope) {
  for (var i = 0; i < array.length; i++) {
		callback.call(scope, i, array[i]);
  }
};

var sortableTable = dragula([container]);

sortableTable.on('dragend', function() {
  nodeListForEach(rows, function (index, row) {
      console.log(row);
    row.firstElementChild.textContent = index + 1;
    row.dataset.rowPosition = index + 1;
   // alert(row.getAttribute(data-id));
   // console.log(row.getAttribute(data-id) + " " + index + 1);
   var _row_p = index + 1;
   console.log(row.dataset.id + " " + row.dataset.rowPosition);

    $.ajax({
           type:'POST',
           url:base_url+'/admin/appointment/updatetoken',
           data:{
               '_token': '{{ csrf_token() }}',
               'id': row.dataset.id,
               'token_number': row.dataset.rowPosition
           },
           success:function(data){
             
           }
        });

  });

  Swal.fire(
    'Updated!',
    'Token Updated',
    'success'
    ).then((result)=>{
        // _this.html('Token Updated');
        window.location.reload();
    });

});


</script>

@endsection