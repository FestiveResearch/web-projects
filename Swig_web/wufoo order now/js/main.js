//This hides different form elements and causes the form to submit.
<script>
	window.onload = function() {
		$('.next-button').on('click', function() {
			var step = $(this).parent().attr('id');
			switch (step) {
				case 'step-1':
					$('#step-1').css('visibility', 'hidden');
					$('#step-2').css('visibility', 'visible');
					break;
				case 'step-2':
					$('#step-2').css('visibility', 'hidden');
					$('#step-3').css('visibility', 'visible');
					break;
				case 'step-3':
					$('#saveForm').trigger('click');
					break;
				default:window.location.reload();
			}
		});
		// $('#saveForm').on('click', function() { alert('Submitted motha fucka!'); });
	};
	</script>
