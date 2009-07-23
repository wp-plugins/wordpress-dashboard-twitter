<?php
switch( $_GET['action'] ) {
	case 'upload-image':	
		$uploaddir = str_replace('inc/', '', dirname( __FILE__ ) . '/uploads/' );
		$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
		
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			echo "Uploaded.";
		} else {}
		break;
}
?>