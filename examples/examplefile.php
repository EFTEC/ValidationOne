<form method="post" enctype="multipart/form-data">
	<input type="file" class="custom-file-input" name="frm_image" id="frm_image"><br>
	<button type="submit" class="btn btn-primary" name="button" value="submit">Submit</button>
</form>

<?php

use eftec\ValidationOne;


include "common.php";


$validaton=new ValidationOne("frm_");

$file=$validaton
	->def("")
	->ifFailThenDefault(false) // if fails then we show the same value however it triggers an error
	->type("file")
	->condition("image","The file is not a right image")
	->condition("ext","The file is incorrect",['jpg','png'])
	->condition("req","this value is required")
	->getFile('image',false);

var_dump($file);
echo "<br>validations:";
var_dump($validaton->getMessages());
