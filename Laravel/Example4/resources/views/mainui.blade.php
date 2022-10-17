@extends('layouts.guest')

@section('main_container')
    <!-- page content -->
    <div class="right_col" role="main">
		<div class="contactSectionPosition">
			<div class="container">

			    <div class="row contactSectionFormAndContacts Fedra">
                  <div class="col-sm-7 animateForm contactSectionForm ">
                          <form action="/question/sent" method="post" onsubmit="return submitUserForm();">
						  	{{ csrf_field() }}
							  <input type="hidden" name="contact_us" value="1">
                              <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                                  <label for="name">FirstName *</label>
                                   <input type="text" class="form-control"  placeholder="FirstName *" name="name" required @if(Auth::user()) value="{{ auth()->user()->name }}" @endif>
                              </div>
                              <div class="form-group {{ $errors->has('email') ? 'has-error' : '' }}">
                                  <label for="email">Email *</label>
                                  <input type="email" class="form-control"  placeholder="Email *" name="email" required  @if(Auth::user()) value="{{ auth()->user()->email }}" @endif>
                              </div>
							  <div class="form-group {{ $errors->has('phone') ? 'has-error' : '' }}">
								  <label for="phone">Phone *</label>
								  <input type="text" class="form-control feetback-phone" placeholder="Phone *" name="phone" onkeypress = "return isNumber(event)" required maxlength="15" @if(Auth::user()) value="{{ auth()->user()->phone }}" @endif>
							  </div>

                              <div class="form-group {{ $errors->has('message') ? 'has-error' : '' }}">
                                  <label for="comment">Message *</label>
                                  <textarea placeholder="Message *" name="message" class="form-control" style="height:150px;width:100%;overflow:hidden" required>{{ old('message') }}</textarea>
                              </div>

							  <div class="captcha-chat">
								  <div class="captcha-container media">
									  <div id="captcha">
										  <div class="controls">
											  <input class="user-text btn-common" placeholder="Enter here *" type="text" name="captcha"/>
											  <input type="hidden" name="captcha-value" id="captcha-value" value=""/>
											  <button class="validate btn-common">
												  <img src="img/enter_icon.png" alt="submit icon">
											  </button>
											  <button class="refresh btn-common" onclick="refreshStopPropogation(event)">
												  <img src="img/captcha_icon.png" alt="refresh icon">
											  </button>
										  </div>
									  </div>
									  <p class="wrong info">Wrong!, please try again.</p>
								  </div>
							  </div>
                              <div class="form-group">
                                  <input type="checkbox" name='agree' checked> I agree <a href="/terms" target="_blank"></a>
                              </div>
                              <button type="submit"   class="contactBtn">Send</button>
                          </form>
                    </div>
			    </div>
			</div>
		</div>
    </div>
    <!-- /page content -->
@endsection

@push('scripts')
	<script type="text/javascript">
		function refreshStopPropogation(e) {
			e.preventDefault();
		}

		function submitUserForm() {
			document.querySelector('.validate').click();
			if (document.querySelector('.user-text').getAttribute('data-allow') && document.querySelector('.user-text').getAttribute('data-allow') === 'false') {
				return false
			}
		}

		document.addEventListener("DOMContentLoaded", function() {
			document.body.scrollTop;

			var timeout;
			var captcha = new $.Captcha({
				selector: '#captcha',
				inputValueSelector: '#captcha-value',
				onFailure: function() {
					$(".captcha-chat .wrong").show({
						duration: 30,
						done: function() {
							var that = this;
							clearTimeout(timeout);
							$(this).removeClass("shake");
							$(this).css("animation");
							$(this).addClass("shake");
							var time = parseFloat($(this).css("animation-duration")) * 1000;
							timeout = setTimeout(function() {
								$(that).removeClass("shake");
							}, time);
						}
					});
				},

				onSuccess: function() {
					$(".captcha-chat .wrong").hide()
				}
			});

			captcha.generate();

			$(".feetback-phone").keyup(function(){
				var phoneNumber = $('.feetback-phone').val();
				$('.feetback-phone').val(phoneNumber.replace(/\+7/, 8))
			});

			$(".feedback-btn").click(function() {
				$("#myNavbar").removeClass("in");
				$('html, body').animate({
					scrollTop: $(".scroll-btn").offset().top
				}, 1000);
			});
		});

	</script>
@endpush
