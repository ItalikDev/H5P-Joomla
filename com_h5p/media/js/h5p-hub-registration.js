(function ($) {
	$(document).ready(function () {
     	  const data = H5PContentHubRegistration;
          data.container = document.getElementById('h5p-hub-registration');
          H5PHub.createRegistrationUI(data);
	});
})(H5P.jQuery);
