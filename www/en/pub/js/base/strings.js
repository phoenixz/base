// Return the string from the start until the specified character is encountered
String.prototype.until = function(character, more, from){
	if(!from) from = 0;
	if(!more) more = 0;

	var pos = this.indexOf(character, from);

	if(pos < 0) return this.toString().substr(from);

	return this.substr(0, pos + more);
}



// Return the string from the specified character until the end
String.prototype.from = function(character, more, from){
	if(!more) more = 0;
	if(!from) from = 0;

	var pos = this.indexOf(character, from);

	if(pos < 0) return '';

	return this.substr(pos - more + character.length);
}



// Return the string from the start until the specified character is encountered, reversed
String.prototype.runtil = function(character, more, from){
	if(!more) more = 0;
	if(!from) from = this.length;

	var pos = this.lastIndexOf(character, from);

	if(pos < 0) return this.toString();

	return this.substr(0, pos + more);
}



// Return the string from the specified character until the end, reversed
String.prototype.rfrom = function(character, more, from){
	if(!more) more = 0;
	if(!from) from = this.length;

	var pos = this.lastIndexOf(character, from);

	if(pos < 0) return '';

	return this.substr(pos - more + character.length);
}



// Ensure that the string has minimum length, if not pad with character on left side
String.prototype.lpad = function(character, length){
	var str = this;

	while (str.length < length){
		str = character + str;
	}

	return str;
}



// Ensure that the string has minimum length, if not pad with character on right side
String.prototype.rpad = function(character, length){
	var str = this;

	while (str.length < length){
		str = character + str;
	}

	return str;
}
