<!-- footer -->
      <footer>
         <div class="container">
            <div class="row">
               <div class="col-lg-3">
                  <a href="#"><img class="footer-logo" src="{{ asset('assets/default/images/logo_footer.png') }}"></a>     
                  <p>One platform that takes care of your well being. You can consult experts in various fields and even take classes from them in order to learn.</p>
                   <ul class="social-links">
                    <li><a href="#"><i class="fa fa-facebook-square" aria-hidden="true"></i></a></li>
                    <li><a href="#"><i class="fa fa-google" aria-hidden="true"></i></a></li>
                    <li><a href="#"><i class="fa fa-twitter" aria-hidden="true"></i></a></li>
                    <li><a href="#"><i class="fa fa-whatsapp" aria-hidden="true"></i></a></li>
                   </ul>
               </div>
               <div class="col-lg-3">
                  <div class="footer-item">
                     <h3>Links</h3>
                     <ul>
                        <li><a href="">About Us</a></li>
                        <li><a href="">Blogs</a></li>
                        <li><a href="{{ url('terms-conditions') }}">Terms &amp; conditions</a></li>
                        <li><a href="{{ url('privacy-policy') }}">Privacy policy</a></li>
                        <li><a href="">Contact Us</a></li>
                        <li><a href="">Become an expert</a></li>
                     </ul>
                  </div>
               </div>
               <div class="col-lg-3">
                  <div class="footer-item">
                     <h3>Consult experts</h3>
                     <ul>
                        <li><a href="">Doctor</a></li>
                        <li><a href="">Lawyer</a></li>
                        <li><a href="">Meditation expert</a></li>
                        <li><a href="">Dietician</a></li>
                        <li><a href="">Fitness expert</a></li>
                        <li><a href="">Love expert</a></li>
                      </ul>
                  </div>
               </div>
               <div class="col-lg-3">
                  <div class="footer-item">
                     <h3>Join classes</h3>
                     <ul>
                        <li><a href="">Doctor</a></li>
                        <li><a href="">Lawyer</a></li>
                        <li><a href="">Meditation expert</a></li>
                        <li><a href="">Dietician</a></li>
                        <li><a href="">Fitness expert</a></li>
                        <li><a href="">Love expert</a></li>
                      </ul>
                  </div>
               </div>
            </div>
         </div>
      </footer>
      <div class="footer-bottom">
         <div class="container">
            <div class="row">
               <div class="col-md-12 text-center">
                  <p>&copy; 2020 Royoconsultant. All rights reserved</p>
               </div>
            </div>
         </div>
      </div>
      <!-- footer -->

 
<!-- popup's -->
    
<!-- signup -->
<div class="modal fade" id="signup" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Sign up</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <img src="{{ asset('assets/default/images/ic_cancel.png') }}">
        </button>
      </div>
      <div class="modal-body">
        <div class="form-group">
            <button type="button" class="btn-primary">
                <img src="{{ asset('assets/default/images/ic_user.png') }}"> Continue with phone number
            </button>
          </div>
        <div class="form-group">
            <button type="button" class="btn-primary">
              <a href="{{ url('redirect?type=facebook')}}">
                <img src="{{ asset('assets/default/images/ic_facebook.png') }}"> Continue with Facebook
                </a>
            </button>
          </div>
        <div class="form-group">
            <button type="button" class="btn-primary">
                <a href="{{ url('redirect?type=google')}}">
                  <img src="{{ asset('assets/default/images/ic_google.png') }}"> Continue with Google
                </a>
            </button>
          </div>
          
          <p>By continuing, you agree to our <a href="#">Terms of serivce</a> and <a href="">Privacy policy</a></p>
          
      </div>
      <div class="modal-footer">
<!--
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
-->
          <p>Already have an account? <a href="">Login</a></p>
      </div>
    </div>
  </div>
</div>
<!-- signup -->
<!-- login -->
<div class="modal fade" id="login" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Let’s  Get Started</h5>
          <p>We need your phone number to identify you</p>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <img src="{{ asset('assets/default/images/ic_cancel.png') }}">
        </button>
      </div>
      <div class="modal-body">
        <div class="form-group">
            <label>Enter your Contact number</label>
            <div class="form">
                <select class="form-control">
                    @foreach($countries as $phonecode=>$name)
                      <option value="{{ $phonecode }}">{{ $name }} +{{ $phonecode }}</option>
                    @endforeach
                </select>
                <input type="tel" class="form-control" placeholder="Enter your contact number">
            </div>
          </div>
          <button type="button" class="btn" data-toggle="modal" data-target="#otp">Next</button>
          <p>By continuing, you agree to our <a href="{{ url('terms-conditions') }}">Terms of serivce</a> and <a href="{{ url('privacy-policy') }}">Privacy policy</a></p>
          
      </div>
<!--
      <div class="modal-footer">
          <p>Already have an account? <a href="">Login</a></p>
      </div>
-->
    </div>
  </div>
</div>
<!-- login -->
    
<!-- otp -->
<div class="modal fade" id="otp" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Verification</h5>
          <p>We sent you a code to +91 9984929384</p>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <img src="images/ic_cancel.png">
        </button>
      </div>
      <div class="modal-body">
        <div class="form-group">
            <label>Enter OTP</label>
            <div class="form">
                <div class="row">
                    <div class="col-md-3">
                        <input type="tel" class="form-control" placeholder="">                    
                    </div>
                    <div class="col-md-3">
                        <input type="tel" class="form-control" placeholder="">                    
                    </div>
                    <div class="col-md-3">
                        <input type="tel" class="form-control" placeholder="">                    
                    </div>
                    <div class="col-md-3">
                        <input type="tel" class="form-control" placeholder="">                    
                    </div>
                </div>
            </div>
          </div>
          <p class="text-left">Didn’t receive the code yegt? <a href="#">Resend Code</a></p>

          <button type="submit" class="btn">Submit</button>
          
      </div>
<!--
      <div class="modal-footer">
          <p>Already have an account? <a href="">Login</a></p>
      </div>
-->
    </div>
  </div>
</div>
<!-- otp -->
    
    
<!-- popup's -->

  <!-- Optional JavaScript -->
  <!-- jQuery first, then Popper.js, then Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
    integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo"
    crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
    integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1"
    crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
    integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM"
    crossorigin="anonymous"></script>

  <script type="text/javascript">
    $(window).scroll(function () {
      if ($(this).scrollTop() > 100) {
        $('header').addClass('header');
      } else {
        $('header').removeClass('header');
      }
    });
  </script>

  <script type="text/javascript">
    $(window).scroll(function () {
      if ($(document).scrollTop() > 50) {
        $("nav").addClass("shrink");
      } else {
        $("nav").removeClass("shrink");
      }
    });
  </script>
</body>

</html>