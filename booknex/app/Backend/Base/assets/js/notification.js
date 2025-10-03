(function ($) {
  "use strict";

  $(document).ready(() => {
    const notificationButton = $(".booknetic_notification_button");
    const notificationDropDown = $(".booknetic_notification_panel");
    const carouselWrapper = $(".notification-carousel-wrapper");
    const carouselItems = $(".notification-carousel-item");

    const slideWidth = carouselItems.outerWidth();
    const slideCount = carouselItems.length;

    let slideIndex = 1;
    let scrollIntervalID = null;

    const closeNotification = (event) => {
      if (!$(event.target).closest(".booknetic_notification_area").length) {
        notificationDropDown.fadeOut("fast");
        $(document).off("click", closeNotification);
        stopCarousel();
      }
    };

    notificationButton.on("click", (event) => {
      notificationDropDown.fadeToggle("fast");
      $(document).one("click", closeNotification);
      $(".booknetic_help_center_dropdown").fadeOut("fast");
      startCarousel();
      event.stopPropagation();
    });

    const updateCarousel = () => {
      slideIndex = (slideIndex + 1) % slideCount;
      const distance =
        slideIndex > 0
          ? "+=" + slideIndex * slideWidth
          : "-=" + slideWidth * slideCount;

      carouselWrapper.animate(
        {
          scrollLeft: distance,
        },
        "slow"
      );
    };

    const startCarousel = () => {
      if (!scrollIntervalID) {
        scrollIntervalID = setInterval(updateCarousel, 5000);
      }
    };

    const stopCarousel = () => {
      clearInterval(scrollIntervalID);
    };
  });
})(jQuery);
