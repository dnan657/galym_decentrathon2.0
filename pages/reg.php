<?php

if( user_get() !== false ){
	redirect('/');
}
	
?>

<h1>Create account</h1>

<div class="row  justify-content-center">

	<div class="col-lg-6">
	
		<div class="mb-3">
			<label class="form-label">Email</label>
			<input type="email" class="form-control  form-control-lg" field_name="email" placeholder="Email">
		</div>
		
		<div class="mb-3">
			<label class="form-label">Name</label>
			<input type="text" class="form-control  form-control-lg" field_name="name" placeholder="Name">
		</div>
		
		<div class="mb-3">
			<label class="form-label">Password</label>
			<input type="password" class="form-control  form-control-lg" field_name="pass" placeholder="Password">
		</div>

		<button type="button" class="btn btn-primary btn-lg d-block w-100  mt-4  mb-3">Create account</button>
		
		<a href="/login" class="text-center  d-block  btn btn-lg  btn-outline-dark">Login</a>
		
	</div>
</div>


<script>

$('button').on('click', function(){
	
	my_api('user_create', get_field(), $(this), function(response){
		if(response['error'] === ''){
			location.href = '/';
		}
	})
	
})

</script>