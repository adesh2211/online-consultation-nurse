<?php
$category_permission = json_decode(Auth::user()->permission);
$permission = (isset($category_permission->module) && $category_permission->module=='category')?true:false;
$admin = Auth::user()->hasRole('admin');
if(config('client_connected') && Config::get("client_data")->domain_name=="care_connect_live"){
    $doctor_manager = Auth::user()->hasRole('doctor_manager');
}

$service_provider = Auth::user()->hasRole('service_provider');
 $tx_dash = 'Consultants';
    if(config('client_connected') && Config::get("client_data")->domain_name=="mp2r")
        $tx_dash = 'Professionals';
    elseif(config('client_connected') && Config::get("client_data")->domain_name=="intely")
        $tx_dash = 'Nurses';
?>
<!-- ========== Left Sidebar Start ========== -->
<div class="left-side-menu">

    <div class="h-100" data-simplebar>

        <!-- User box -->
        <div class="user-box text-center">
            <img src="{{asset('assets/images/users/user-1.jpg')}}" alt="user-img" title="Mat Helme"
                class="rounded-circle avatar-md">
            <div class="dropdown">
                <a href="javascript: void(0);" class="text-dark dropdown-toggle h5 mt-2 mb-1 d-block"
                    data-toggle="dropdown">Geneva Kennedy</a>
                <div class="dropdown-menu user-pro-dropdown">

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item notify-item">
                        <i class="fe-user mr-1"></i>
                        <span>My Account</span>
                    </a>

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item notify-item">
                        <i class="fe-settings mr-1"></i>
                        <span>Settings</span>
                    </a>

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item notify-item">
                        <i class="fe-lock mr-1"></i>
                        <span>Lock Screen</span>
                    </a>

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item notify-item">
                        <i class="fe-log-out mr-1"></i>
                        <span>Logout</span>
                    </a>

                </div>
            </div>
            <p class="text-muted">Admin Head</p>
        </div>

        <!--- Sidemenu -->
        <div id="sidebar-menu">

            <ul id="side-menu">
                <li class="menu-title">Dashboard</li>
                @if(config('client_connected') && Config::get("client_data")->domain_name=="care_connect_live")
                @if($doctor_manager)
                  @php $user = \Auth::user();
                  $authUser = \App\User::where('id',$user->id)->first();
                  $assignUser = $authUser->assign_user;
                  @endphp
                <li>
                    <a href="#Users" data-toggle="collapse">
                        <i data-feather="users"></i>
                        <span> Doctors </span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse show" id="Users">
                        <ul class="nav-second-level">
                            @if($assignUser != null)
                            @foreach(json_decode($assignUser) as $assign)
                            @php
                            $assignusr = \App\User::where('id',$assign)->first();

                            $dateznow = new \DateTime("now", new \DateTimeZone('Asia/Kolkata'));
                            $datenow = $dateznow->format('Y-m-d H:i');
                            $time = $dateznow->format('h:i:a');
                            // echo $time;
                            // die();

                            list($h, $i, $a) = explode(':', $time);

                            if ($i == 0) {
                                $i = "00";
                            } else if ($i > 0 && $i < 30) {
                                $i = "30";
                            } else if ($i == 30) {
                                $i = "30";
                            } else if ($i > 30) {
                                $i = "00";
                                if($h < 12)
                                {
                                    $h++;
                                }
                                else
                                {
                                    $h = "01";
                                }
                            }

                            $time =  $h.":".$i." ".$a;
                            // die();

                            $date = $dateznow->format('m-d-Y');
                            @endphp
                            <li
                                @if($assign == Request::get('doctor_id'))
                                    class="doctor_name bg-success"
                                @else
                                    class="doctor_name"
                                @endif
                            > 
                                <a 
                                @if($assign == Request::get('doctor_id'))
                                    class="text-white"
                                @else
                                    class=""
                                @endif
                                href="{{url('admin/requests')}}?doctor_id={{$assign}}&slot_date={{$date}}&slot_time={{$time}}">
                                    @if($assignusr->manual_available == '1' )
                                    <i class="fa fa-check-circle" aria-hidden="true" style="color:yellowgreen;"></i>
                                    @endif
                                {{ ucwords($assignusr->name) }}
                                </a>
                               

                            </li>
                            @endforeach
                            @endif
                          
                          
                        </ul>
                    </div>
                </li>

                @endif
                @endif
                @if($admin)
                <li>
                    <a href="{{route('admin_dashboard')}}">
                        <i data-feather="airplay"></i>
                            <span>Dashboard</span>
                    </a>
                </li>
                <li class="menu-title mt-2">Apps</li>
                <li>
                    <a href="#sidebarEcommerce" data-toggle="collapse">
                        <i data-feather="cpu"></i>
                        <span> Configuration </span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="sidebarEcommerce">
                        <ul class="nav-second-level">
                            @if(config('client_connected') && (Config::get("client_data")->domain_name=="intely" || Config::get("client_data")->domain_name=="mp2r" || Config::get("client_data")->domain_name=="food" || Config::get("client_data")->domain_name=="iedu" || Config::get("client_data")->domain_name=="physiotherapist"))
                            @else
                            <li>
                                <a href="{{route('services')}}">Service types</a>
                            </li>
                            @endif
                            <li>
                                <a href="{{ url('admin/categories')}}">Categories</a>
                                
                            </li>
                            @if(config('client_connected') && (Config::get("client_data")->domain_name=="iedu"))
                            <li>
                                <a href="{{ url('admin/emsat')}}">Emsats </a>
                            </li>
                            <li>
                                <a href="{{ url('admin/additional-document')}}">Additional Documents </a>
                            </li>
                            <li>
                                <a href="{{ url('admin/course')}}">Courses </a>
                            </li>
                            @endif
                            <?php $exits=App\Helpers\Helper::checkFeatureExist([
                                'client_id'=>Config::get('client_id'),
                                'feature_name'=>'Insurances']) ?>
                            @if(!config('client_connected') || $exits)
                            <li>
                                <a href="{{url('admin/insurance')}}">Insurance</a>
                            </li>
                            @endif
                            @if(config('client_connected') && (Config::get("client_data")->domain_name=="mp2r" || Config::get("client_data")->domain_name=="care_connect_live" || Config::get("client_data")->domain_name=="food" || Config::get("client_data")->domain_name=="physiotherapist"))
                            @else
                            <li>
                                <a href="#VendorCustomField" data-toggle="collapse">
                                    {{ __('text.Vendor') }} Custom Fields <span class="menu-arrow"></span>
                                </a>
                                <div class="collapse" id="VendorCustomField">
                                    <ul class="nav-second-level">
                                        <li>
                                            <a href="{{ url('admin/vendor/custom-fields') }}">Listing</a>
                                        </li>
                                        <li>
                                            <a href="{{ url('admin/vendor/custom-fields/create') }}">Add Field</a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <li>
                                <a href="#UserCustomField" data-toggle="collapse">
                                    {{ __('text.User') }} Custom Fields <span class="menu-arrow"></span>
                                </a>
                                <div class="collapse" id="UserCustomField">
                                    <ul class="nav-second-level">
                                        <li>
                                            <a href="{{ url('admin/user/custom-fields') }}">Listing</a>
                                        </li>
                                        <li>
                                            <a href="{{ url('admin/user/custom-fields/create') }}">Add Field</a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            @endif
                            @if(config('client_connected') && (Config::get("client_data")->domain_name=="heal" || Config::get("client_data")->domain_name=="food" || Config::get("client_data")->domain_name=="healtcaremydoctor" || Config::get("client_data")->domain_name=="care_connect_live" || Config::get("client_data")->domain_name=="intely" || Config::get("client_data")->domain_name=="physiotherapist" ||
                            Config::get("client_data")->domain_name=="iedu" || Config::get("client_data")->domain_name=="curenik"))
                            <li>
                                <a href="#Preferences" data-toggle="collapse">
                                    {{ __('text.Master Preferences') }}<span class="menu-arrow"></span>
                                </a>
                                <div class="collapse" id="Preferences">
                                    <ul class="nav-second-level">
                                        <li>
                                            <a href="{{ url('admin/master/preferences') }}">Listing</a>
                                        </li>
                                        <li>
                                            <a href="{{ url('admin/master/preferences/create') }}">Add</a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            @if(config('client_connected') && (Config::get("client_data")->domain_name=="intely"))
                            <li>
                                <a href="#Duties" data-toggle="collapse">
                                    {{ __('text.Custom Master Preferences') }}<span class="menu-arrow"></span>
                                </a>
                                <div class="collapse" id="Duties">
                                    <ul class="nav-second-level">
                                        <li>
                                            <a href="{{ url('admin/master/duties') }}">Listing</a>
                                        </li>
                                        <li>
                                            <a href="{{ url('admin/master/duties/create') }}">Add</a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            @endif
                            @else
                            @endif

                            @if(config('client_connected') && (Config::get("client_data")->domain_name=="food" || Config::get("client_data")->domain_name=="curenik" || Config::get("client_data")->domain_name=="healtcaremydoctor"))
                            <li>
                                <a href="#Symptoms" data-toggle="collapse">
                                    {{ __('text.Symptoms') }}<span class="menu-arrow"></span>
                                </a>
                                <div class="collapse" id="Symptoms">
                                    <ul class="nav-second-level">
                                        <li>
                                            <a href="{{ url('admin/master/symptoms') }}">Listing</a>
                                        </li>
                                        <li>
                                            <a href="{{ url('admin/master/symptoms/create') }}">Add</a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            @else
                            @endif 

                            @if(config('client_connected') && (Config::get("client_data")->domain_name=="curenik"))
                            <li>
                                <a href="#LifeStyle" data-toggle="collapse">
                                    LifeStyle<span class="menu-arrow"></span>
                                </a>
                                <div class="collapse" id="LifeStyle">
                                    <ul class="nav-second-level">
                                        <li>
                                            <a href="{{ url('admin/master/lifestyle') }}">Listing</a>
                                        </li>
                                        <li>
                                            <a href="{{ url('admin/master/lifestyle/create') }}">Add</a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <li>
                                <a href="#MedicalHistory" data-toggle="collapse">
                                    Medical History<span class="menu-arrow"></span>
                                </a>
                                <div class="collapse" id="MedicalHistory">
                                    <ul class="nav-second-level">
                                        <li>
                                            <a href="{{ url('admin/master/medical_history') }}">Listing</a>
                                        </li>
                                        <li>
                                            <a href="{{ url('admin/master/medical_history/create') }}">Add</a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <li>
                                <a href="#MedicalRecord" data-toggle="collapse">
                                    Medical Records<span class="menu-arrow"></span>
                                </a>
                                <div class="collapse" id="MedicalRecord">
                                    <ul class="nav-second-level">
                                        <li>
                                            <a href="{{ url('admin/custom/masterfields') }}">Listing</a>
                                        </li>
                                        <li>
                                            <a href="{{ url('admin/custom/masterfields/create') }}">Add</a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            @else
                            @endif
                        </ul>
                    </div>
                </li>
                <li>
                    <a href="#Users" data-toggle="collapse">
                        <i data-feather="users"></i>
                        <span> Users </span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="Users">
                        <ul class="nav-second-level">
                            <li>
                                <a href="{{url('admin/consultants')}}">{{ __('text.Vendors') }}</a>
                            </li>
                            <li>
                                <a href="{{url('admin/customers')}}">{{ __('text.Users') }}</a>
                            </li>
                            @if(config('client_connected') && (Config::get("client_data")->domain_name=="care_connect_live"))
                            <li>
                                <a href="{{url('admin/doctormanagers')}}">{{ __('Doctor Managers') }}</a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                @if(config('client_connected') && (Config::get("client_data")->domain_name=="clouddoc"))
                <li>
                    <a href="#BookingsD" data-toggle="collapse">
                        <i data-feather="cpu"></i>
                        <span> Bookings </span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="BookingsD">
                        <ul class="nav-second-level">
                            <li>
                                <a href="{{url('admin/requests')}}">All</a>
                            </li>
                            <li>
                                <a href="{{url('admin/pending-requests')}}">Pending Bookings</a>
                            </li>
                            <li>
                                <a href="{{url('admin/un-answered-requests')}}">Un-Answered Bookings</a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endif
                @if(config('client_connected') && (Config::get("client_data")->domain_name=="iedu"))
                <li>
                    <a href="#Topics" data-toggle="collapse">
                        <i data-feather="file"></i>
                        <span> Topics </span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="Topics">
                        <ul class="nav-second-level">
                            <li>
                                <a href="{{url('admin/topics')}}">{{ __('Topics List') }}</a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endif
                <li>
                    <a href="#Marketing" data-toggle="collapse">
                        <i data-feather="briefcase"></i>
                        <span> Marketing </span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="Marketing">
                        <ul class="nav-second-level">
                            <li>
                                <a href="{{url('admin/banner')}}">Banners</a>
                            </li>
                            @if(config('client_connected') && (Config::get("client_data")->domain_name=="care_connect_live"))
                            <li>
                                <a href="{{url('admin/advertisement')}}">Advertisements</a>
                            </li>
                            @endif
                            @if(config('client_connected') && (Config::get("client_data")->domain_name=="clouddoc"))
                            <li>
                                <a href="{{url('admin/subscriptions')}}">Subscription Plan</a>
                            </li>
                            @endif
                            <?php $exits=App\Helpers\Helper::checkFeatureExist([
                                'client_id'=>Config::get('client_id'),
                                'feature_name'=>'Packages']) ?>
                            @if(!config('client_connected') || $exits)
                            <li>
                                <a href="{{url('admin/package')}}">Packages</a>
                            </li>
                            @endif
                            <?php $exits=App\Helpers\Helper::checkFeatureExist([
                                'client_id'=>Config::get('client_id'),
                                'feature_name'=>'Master Interval']) ?>
                            @if(!config('client_connected') || $exits)
                            <li>
                                <a href="{{url('admin/master_slot')}}">Master Interval</a>
                            </li>
                            @endif
                            @if(config('client_connected'))
                                @if(Config::get("client_data")->domain_name!=="mp2r")
                                <li>
                                    <a href="{{url('admin/coupon')}}">Coupons</a>
                                </li>
                                @endif
                                <?php $plans=App\Helpers\Helper::checkFeatureExist([
                                'client_id'=>Config::get('client_id'),
                                'feature_name'=>'monthly plan']); ?>
                                @if($plans)
                                <li>
                                    <a href="{{url('admin/subscription')}}">Subscription Plan</a>
                                </li>
                                @endif
                            @else
                                <li>
                                    <a href="{{url('admin/coupon')}}">Coupons</a>
                                </li>
                            @endif
                            @if(config('client_connected') && config::get("client_data")->domain_name=="curenik")
                                 <li>
                                    <a href="{{url('admin/covid19')}}">Covid-19</a>
                                </li>
                                @endif

                            @if(config('client_connected') && config::get("client_data")->domain_name=="healtcaremydoctor")
                                 <li>
                                    <a href="{{url('admin/support_packages')}}">Support Packages</a>
                                </li>
                                @endif
                            @if(config('client_connected') && config::get("client_data")->domain_name=="clouddoc")
                                 <li>
                                    <a href="{{url('admin/send/email')}}">Email</a>
                                </li>
                                @endif
                            
                           <!--  <li>
                                <a href="{{url('admin/consultants')}}">Wallet Coupons</a>
                            </li> -->
                        </ul>
                    </div>
                </li>
                <li>
                    <a href="#Settings" data-toggle="collapse">
                        <i data-feather="settings"></i>
                        <span> Settings </span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="Settings">
                        <ul class="nav-second-level">
                            <li>
                                <a href="{{url('admin/pages')}}">Pages</a>
                            </li>
                            <li>
                                <a href="{{url('admin/app_detail')}}">App Setting</a>
                            </li>
                            @if(config('client_connected') && (Config::get("client_data")->domain_name=="physiotherapist" || Config::get("client_data")->domain_name=="healtcaremydoctor" || Config::get("client_data")->domain_name=="care_connect_live" || Config::get("client_data")->domain_name=="clouddoc" ||
                            Config::get("client_data")->domain_name=="meetmd"))
                                <li>
                                    <a href="#Blogs" data-toggle="collapse">
                                        Blogs<span class="menu-arrow"></span>
                                    </a>
                                    <div class="collapse" id="Blogs">
                                            <ul class="nav-second-level">
                                                <li>
                                                    <a href="{{ url('admin/blogs') }}">Listing</a>
                                                </li>
                                                <li>
                                                    <a href="{{ url('admin/blogs/create') }}">Add</a>
                                                </li>
                                            </ul>
                                    </div>
                                </li>
                            @else
                            @endif
                            <li>
                                <a href="{{url('admin/service_enable')}}">Variables</a>
                            </li>
                            @if(config('client_connected') && (Config::get("client_data")->domain_name=="mp2r" || Config::get("client_data")->domain_name=="food"))
                            @else
                            <li>
                                <a href="{{url('admin/faq')}}">FAQs</a>
                            </li>
                            <li>
                                <a href="{{url('admin/app_version')}}">App Version</a>
                            </li>
                            @endif

                            @if(config('client_connected') && (Config::get("client_data")->domain_name=="heal" || Config::get("client_data")->domain_name=="curenik"))
                                <li>
                                    <a href="{{url('admin/tip')}}">Tip Of The Day</a>
                                </li>
                            @endif
                            @if(config('client_connected') && (Config::get("client_data")->domain_name=="healtcaremydoctor"))
                                <li>
                                    <a href="{{url('admin/ask_question')}}">Ask Questions</a>
                                </li>
                            @endif
                            @if(config('client_connected') && (config::get("client_data")->domain_name=="healtcaremydoctor" || Config::get("client_data")->domain_name=="food" ))
                             <li>
                                <a href="{{url('admin/support_questions')}}">Support Ask Question</a>
                            </li>
                            @endif
                            @if(config('client_connected') && ( Config::get("client_data")->domain_name=="curenik"))
                            <li>
                            <a href="{{url('admin/slots')}}">Slots</a>     
                            </li>
                            @endif
                           
                        </ul>
                    </div>
                </li>
                @if(Config::get('client_connected'))
                    @if((Config::get("client_data")->domain_name!=="mp2r" && Config::get("client_data")->domain_name!=="food1"))
                    <li>
                        <a href="#Features" data-toggle="collapse">
                            <i data-feather="settings"></i>
                            <span> Features </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="Features">
                            <ul class="nav-second-level">
                                @if(Config::get('client_features'))
                                    @foreach(Config::get('client_features') as $index => $client_feature)
                                    <li>
                                        <a href="{{url('admin/feature-types/'.$client_feature->feature_type->id)}}">{{ $client_feature->feature_type->name }}</a>
                                    </li>
                                    @endforeach
                                @endif
                            </ul>
                        </div>
                    </li>
                    @if(Config::get("client_data")->domain_name=="curenik" || Config::get("client_data")->domain_name=="healtcaremydoctor")
                     <li>
                        <a href="{{url('admin/payouts')}}">
                            <i data-feather="bookmark"></i>
                            <span> Payouts </span>
                        </a>
                    </li>
                    @endif
                    @endif
                @endif
                @if(!config('client_connected'))
                    <li class="menu-title mt-2">Reports</li>
                    <li>
                        <a href="{{url('admin/payouts')}}">
                            <i data-feather="bookmark"></i>
                            <span> Payouts </span>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)">
                            <i data-feather="bookmark"></i>
                            <span> Classes </span>
                        </a>
                    </li>
                @endif

                @if(config('client_connected') && (Config::get("client_data")->domain_name=="intely"))
                     
                @endif

                @elseif($service_provider && $permission)
                <li>
                    <a href="{{url('admin/requests')}}">
                        <i data-feather="airplay"></i>
                            <span>Dashboard</span>
                    </a>
                </li>
                @if(Config('client_connected') && Config::get("client_data")->domain_name=="physiotherapist")
                <li class="menu-title mt-2">Apps</li>
                <li>
                    <a href="#Users2" data-toggle="collapse">
                        <i data-feather="Users2"></i>
                        <span> {{ __('text.Vendors') }} </span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="Users2">
                        <ul class="nav-second-level">
                            <li>
                                <a href="{{ url('admin/centre/doctors') }}">{{ __('text.Vendors') }} List</a>
                            </li>
                            <li>
                                <a href="{{ url('admin/centre/doctor/create') }}">Add {{ __('text.Vendor') }} </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li>
                    <a href="#patients" data-toggle="collapse">
                        <i data-feather="patients"></i>
                        <span> {{ __('text.Users') }} </span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="patients">
                        <ul class="nav-second-level">
                            <li>
                                <a href="{{ url('admin/patients') }}">{{ __('text.Users') }} List</a>
                            </li>
                            <li>
                                <a href="{{ url('admin/patient/create') }}">Add {{ __('text.User') }} </a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endif
                @endif
            </ul>

        </div>
        <!-- End Sidebar -->

        <div class="clearfix"></div>

    </div>
    <!-- Sidebar -left -->

</div>
<!-- Left Sidebar End -->