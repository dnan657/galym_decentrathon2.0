<?php

if( user_get() == false ){
	redirect('/');
}
	
?>

<h1>My settings</h1>

<div class="row  justify-content-center">

	<div class="col-lg-6">
		
		<div class="mb-3">
			<label class="form-label">Email</label>
			<input type="email" class="form-control  form-control-lg" field_name="email" placeholder="Email" value="<?php echo(htmlentities($GLOBALS['user']['email'])); ?>">
		</div>
		
		<div class="mb-3">
			<label class="form-label">Name</label>
			<input type="text" class="form-control  form-control-lg" field_name="name" placeholder="Name" value="<?php echo(htmlentities($GLOBALS['user']['name'])); ?>">
		</div>
		
		<div class="mb-3">
			<label class="form-label">Password</label>
			<input type="password" class="form-control  form-control-lg" field_name="pass" placeholder="Password">
		</div>

		<button type="button" class="btn btn-primary btn-lg d-block w-100  mt-4  mb-3">Edit</button>
		
	</div>
</div>


<script>

$('button').on('click', function(){
	
	my_api('user_update', get_field(), $(this), function(response){
		if(response['error'] === ''){
			toastr.success('Save')
		}
	})
	
})

</script>