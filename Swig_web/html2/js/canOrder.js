<script type="text/javascript">
	$(function() {
	  $(“#order_btn”).click(function() {
	    var now = new Date();
	    var day = now.getDay();
	    var hour = now.getUTCHours() - 8;
	    if ((day >= 5 && (hour >= 12 || hour <= 2)) || (day === 0 && hour >=12)) {
	      document.location='https://swig.wufoo.com/forms/order-now/';
	    } else {
	      document.location='https://swig.wufoo.com/forms/sorry-swig-is-currently-closed/';
	    }
	    return false;
	  });
	}
</script>

