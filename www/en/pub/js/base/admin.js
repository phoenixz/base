$(document).ready(function() {
	$('.fix_sCase').live('click', function(event) {
		var obj=$(this).prev();
		sCase(obj,obj.val());
	});

	$('.fix_ucfirst').live('click', function(event) {
		var obj=$(this).prev();
		obj.val(ucwords(obj.val()));
	});

	$('.fixall').live('click', function(event) {
		$(".fix").each(function() {
			if($(this).hasClass('fix_sCase'))
			{
				var obj=$(this).prev();
				sCase(obj,obj.val());
			}
			if($(this).hasClass('fix_ucfirst'))
			{
				var obj=$(this).prev();
				obj.val(ucwords(obj.val()));
			}
		});
	});
});

function ucwords (str) {
	str=str.toLowerCase();
	return (str + '').replace(/^([a-z])|\s+([a-z])/g, function ($1) {
        return $1.toUpperCase();
	});
}

function sCase(obj,val){
    if(val!=undefined){
        var result      = new Array();
            result2     = '';
            count       = 0;
            endSentence = new Array();

        for (var i = 1; i < val.length; i++){
            if(val.charAt(i) == '.' || val.charAt(i) == '!' || val.charAt(i) == '?'){
                endSentence[count] = val.charAt(i);
                count++
            }
        }

        var val2 = val.split(/[.|?|!]/);

        if(val2[val2.length-1]=='')val2.length=val2.length-1;

        for (var j = 0; j < val2.length; j++){
            val3 = val2[j];

            if(val3.substring(0,1) != ' '){
                val2[j] = ' ' + val2[j];
            }

            var temp = val2[j].split(' '),
                incr = 0;

            if(temp[0] == ''){
                incr = 1;
            }

            temp2      = temp[incr].substring(0, 1);
            temp3      = temp[incr].substring(1, temp[incr].length);
            temp2      = temp2.toUpperCase();
            temp3      = temp3.toLowerCase();
            temp[incr] = temp2+temp3;

            for (var i = incr + 1; i < temp.length; i++){
                temp2   = temp[i].substring(0, 1);
                temp2   = temp2.toLowerCase();
                temp3   = temp[i].substring(1, temp[i].length);
                temp3   = temp3.toLowerCase();
                temp[i] = temp2+temp3;
            }

            if(endSentence[j] == undefined){
                endSentence[j] = '';
                result2 += temp.join(' ') + endSentence[j];
            }

            if(result2.substring(0, 1) == ' '){
                result2 = result2.substring(1, result2.length);
                obj.val(result2);
            }
        }
    }
}