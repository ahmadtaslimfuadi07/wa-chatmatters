
<!-- tp-offcanvus-area-start -->
<div class="tp-offcanvas-area">
   <div class="tpoffcanvas">
      <div class="tpoffcanvas__close-btn">
         <button class="close-btn"><i class="fal fa-times"></i></button>
      </div>
      <div class="tpoffcanvas__logo">
         <a href="{{ url('/') }}">
            <img src="{{ asset(get_option('primary_data',true)->logo ?? '') }}" alt="">
         </a>
      </div>
      <div class="tpoffcanvas__text"></div>
      <div class="mobile-menu"></div>
      <div class="tpoffcanvas__info">
         <h3 class="offcanva-title">{{ __('Get In Touch') }}</h3>
         <div class="tp-info-wrapper mb-20 d-flex align-items-center">
            <div class="tpoffcanvas__info-icon">
               <a href="#"><i class="fal fa-envelope"></i></a>
            </div>
            <div class="tpoffcanvas__info-address">
               <span>{{ __('Email') }}</span>
               <a href="maito:{{ get_option('primary_data',true)->contact_email ?? '' }}">{{ get_option('primary_data',true)->contact_email ?? '' }}</a>
            </div>
         </div>
         <div class="tp-info-wrapper mb-20 d-flex align-items-center">
            <div class="tpoffcanvas__info-icon">
               <a href="#"><i class="fal fa-phone-alt"></i></a>
            </div>
            <div class="tpoffcanvas__info-address">
               <span>{{ __('Phone') }}</span>
               <a href="tel:+{{ get_option('primary_data',true)->contact_phone ?? '' }}">{{ get_option('primary_data',true)->contact_phone ?? '' }}</a>
            </div>
         </div>
         <div class="tp-info-wrapper mb-20 d-flex align-items-center">
            <div class="tpoffcanvas__info-icon">
               <a href="#"><i class="fas fa-map-marker-alt"></i></a>
            </div>
            <div class="tpoffcanvas__info-address">
               <span>{{ __('Location') }}</span>
               <a href="#">{{ get_option('primary_data',true)->address ?? '' }}</a>
            </div>
         </div>
      </div>
      <div class="tpoffcanvas__social">
         <div class="social-icon">
            @if(!empty(get_option('primary_data',true)->socials->twitter))
            <a href="{{ get_option('primary_data',true)->socials->twitter }}"><i class="fab fa-twitter"></i></a>
            @endif
            @if(!empty(get_option('primary_data',true)->socials->instagram))
            <a href="{{ get_option('primary_data',true)->socials->instagram }}"><i class="fab fa-instagram"></i></a>
            @endif
            @if(!empty(get_option('primary_data',true)->socials->facebook))
            <a href="{{ get_option('primary_data',true)->socials->facebook }}"><i class="fab fa-facebook-square"></i></a>
            @endif
            @if(!empty(get_option('primary_data',true)->socials->linkedin))
            <a href="{{ get_option('primary_data',true)->socials->linkedin }}"><i class="fab fa-linkedin"></i></a>
            @endif
         </div>
      </div>
   </div>
</div>
<div class="body-overlay"></div>
   <!-- tp-offcanvus-area-end -->