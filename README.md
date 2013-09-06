Vitelity API Wrapper
====================

This is A Vitelity API Wrapper for Laravel 4. While you can add more functions
to this, only a select number of API calls have been bundled. You can add more
using Vitelity's API guide here: http://apihelp.vitelity.net

For information on the objects returned you can either dump the results, or 
refer to the guide on Vitelity's API page.

## With Composer
require  "Core3net/vitelity" : "dev-master"

## Using the Library

Set the API username and password by editing the vitelity.php file or you can set 
the public static variables.

	Vitelity::$VITELITY_USERNAME = "API_USERNAME";
	Vitelity::$VITELITY_PASSWORD = "API_PASSWORD";

## Example Calls
To get a list of numbers available in Georgia you would use the following command.

	$locals = Vitelity::getAvailableLocals("GA")->numbers->did;
	foreach ($locals AS $local)
	{
		echo "Number: $local->number \n ";
		echo "Ratecenter: $local->ratecenter \n";
	}




