<doc
var now = new Date();

// 
var day = now.getDay();

var hour = now.getUTCHours() - 8;

var canOrder = function(day, hour) {
	if ((day >= 5 && (hour >= 12 || hour <= 2)) || (day === 0 && hour >=12) {
alert("Sweet!!!! We're Open. ")
	};
	else {
		alert("Bummer! We're closed for the night.")
	};
};