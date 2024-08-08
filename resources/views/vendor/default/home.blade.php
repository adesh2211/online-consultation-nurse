@include('vendor.default.layouts.header-before-login')
  <!-- Bannar Section -->
  <section class="bannar">
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <h2>Upto <span>40% off</span></h2>
          <p>Get a fitness expert at upto 40% discount. Hurry up and book now.</p>
          <button class="btn mr-3 mt-4">Book Now</button>
        </div>
      </div>
    </div>
  </section>
<!--    banner section--> 
    
<!-- consult section   -->
    <section class="home-consult-section">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h2>Consult Experts</h2>
                    <p>Get consultations from top experts in various fields</p>
                </div>
                @foreach($categories as $category)
                <div class="col-lg-3 col-md-6">
                    <div class="box" style="background-color:{{ $category->color_code }}">
                        <h4>{{ $category->name }}</h4>
                        <img src="{{ Storage::disk('spaces')->url('thumbs/'.$category->image) }}">
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
<!-- consult section   -->
<!-- consult section   -->
    <section class="home-consult-section">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h2>Join classes</h2>
                    <p>Join classes given by top experts in various fields</p>
                </div>
                @foreach($categories as $category)
                <div class="col-lg-3 col-md-6">
                    <div class="box" style="background-color:{{ $category->color_code }}">
                        <h4>{{ $category->name }}</h4>
                        <img src="{{ Storage::disk('spaces')->url('thumbs/'.$category->image) }}">
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
<!-- consult section   -->
    

  <!-- download Section -->
 
  <section class="download-now">
    <div class="container">
      <div class="row">
        <div class="col-md-5 text-center">
          <img class="download-now-img" src="{{ asset('assets/default/images/ic_mobile.png') }}">
        </div>
        <div class="col-md-7 mt-5">
          <h4 class="heading">Download the RoyoConsultation app</h4>
        <ul>
            <li>Video call with experts</li>
            <li>Chat with experts</li>
            <li>Home consultation with experts</li>
            <li>In-person consultation with experts</li>
            <li>Join classes given by experts</li>
            </ul>
            <h5>Get the app download link</h5>
            <div class="row">
                <div class="col-md-8">
                    <div class="form">
                        <select class="form-control">
                            <option>Ind</option>
                            <option>UK</option>
                            <option>USA</option>
                        </select>
                        <input type="tel" class="form-control" placeholder="Enter your phone number">
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="blue-btn btn">Send Link</button>
                </div>
            </div>
             <a href=""><img class="img-fluid mr-4" src="{{ asset('assets/default/images/button_playstore.png') }}"> </a>
             <a href=""><img class="img-fluid" src="{{ asset('assets/default/images/button_appstore.png') }}"> </a>
         
        </div>
      </div>
    </div>
  </section>
@include('vendor.default.layouts.footer-before-login')

