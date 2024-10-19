<?php

include "./functions.php";

$user = user_get();

$project_name = "👨‍🏫 Galym";

$uri_arr = explode('/', $_SERVER['REQUEST_URI']);

$page_title = '';
$page_name = $uri_arr[1] ?: 'index';
$page_id = $uri_arr[2];
$page_number = $_GET['page'];


if( $page_name == 'api' ){
	include "./api.php";
	exit();

}else if($page_name == 'exit'){
	cookie_delete('user');
	redirect('/');
}


$file_path = './pages/' . $page_name . '.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo($project_name); ?></title>
	
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" integrity="sha512-vKMx8UnXk60zUwyUnUPM3HbQo8QfmNx7+ltw8Pm5zLusl1XIfwcxo8DbWCqMGKaWeNxWA8yrx5v3SaVpMvR3CA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
	
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js" integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>
<body class="bg-light">
	<style>
		*{
			word-break: keep-all;
		}
		[template]{
			display: none;
		}
		.form-label {
			margin-bottom: 5px;
		}
		.form-control,
		.form-select{
			border-color: #b4b4b4;
		}
		.w-max{
			width: max-content;
			max-width: 100%;
		}
		::placeholder{
			user-select: none;
			opacity: 0.4!important;
		}
		h1{
			font-size: 30px;
			margin-bottom: 30px;
			text-align: center;
		}
		h2{
			font-size: 24px;
		}
		h3{
			font-size: 20px;
			text-align: center;
		}
		.container{
			max-width: 700px;
		}
		.toast,
		.toast-message{
			-webkit-box-shadow: none!important;
			box-shadow: none!important;
			opacity: 1!important;
		}
		.nav-link {
			padding: 8px 20px !important;
		}
		.main{
			padding: 0px 25px;
			padding-top: 106px;
			padding-bottom: 50px;
			min-height: 100vh;
		}
		
		
		
		/*
		.start_broadcast  #time_left_title{
			animation: kf_blink 2s ease-in-out infinite;
		}

		@keyframes kf_blink{
			0%{
				opacity: 1;
			}
			50%{
				opacity: 0.4;
			}
			100%{
				opacity: 1;
			}
		}

		*/

		#history_list  .item,
		.history_ask  .item_ask{
			padding: 10px 20px;
		}
		#history_list  .item:not(:last-of-type),
		.history_ask  .item_ask:not(:last-of-type){
			border-bottom: 1px solid var(--bs-border-color);
		}
		
		
		#history_list  .item.temp_item  .text_msg{
			color: silver;
		}
		
		#history_list  .item  .time{
			display: flex;
			align-items: center;
			flex-wrap: nowrap;
			font-size: 12px;
			gap: 10px;
			color: silver;
		}
		
		
		#history_list  .item{
			cursor: pointer;
		}
		#history_list  .item.selected{
			background: rgb(0 103 255 / 50%);
		}
	</style>

	<nav class="navbar navbar-expand-lg navbar-dark bg-dark  fixed-top">
		<div class="container">
			<a class="navbar-brand" href="/"><?php echo($project_name); ?></a>
			
			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar_nav" aria-controls="navbar_nav" aria-expanded="false">
				<span class="navbar-toggler-icon"></span>
			</button>
			
			<div class="collapse navbar-collapse" id="navbar_nav">
				<ul class="navbar-nav ms-auto">
					<li class="nav-item"><a class="nav-link" href="/">						Find Broadcast			</a></li>
					<?php if($user !== false){ ?>
						<li class="nav-item"><a class="nav-link" href="/broadcast/create">			Create Broadcast		</a></li>
						<li class="nav-item"><a class="nav-link" href="/settings">			Settings		</a></li>
						<li class="nav-item"><a class="nav-link  text-danger  opacity-75" href="/exit">				Logout			</a></li>
					<?php } ?>
					<?php if($user === false){ ?>
						<li class="nav-item"><a class="nav-link" href="/login">				Login			</a></li>
					<?php } ?>
				</ul>
			</div>
			
		</div>
	</nav>
	
	<script>
		
		function get_field(){
			let data_json = {};
			$('[field_name]').each(function(i, elem){
				let jq_elem = $(elem);
				let name = jq_elem.attr('field_name');
				let val = jq_elem.val();
				data_json[name] = val;
				
				if(jq_elem.attr('type') == "datetime-local"){
					data_json[name] = val.replaceAll('T', ' ');
				}
			})
			return data_json;
		}
		
		async function my_api(method, data_json={}, jq_btn, callback=function(){}){
			
			if( jq_btn ){
				jq_btn.attr('disabled', 'disabled');
			}
			
			let response = await fetch('/api/'+method, {
				method: 'POST',
				headers: {'Content-Type': 'application/json'},
				body: JSON.stringify(data_json)
			})
			
			
			let response_json = {'error': 'Unknown'};
			
			try{
				response_json = await response.json();
			}catch{}
			
			if( jq_btn ){
				jq_btn.removeAttr('disabled');
			}
			
			if( response_json['error'] !== '' ){
				toastr.error('Error: ' + response_json['error']);
			}
			
			callback(response_json)
		}
	</script>
	
	<div class="container  main  bg-white" >
		<?php
			if (file_exists($file_path)) {
				include $file_path;
			} else {
				echo "<h1 class='text-center'>Page not found</h1>";
			}
		?>
	</div>
	
	<script>
		document.title = $('h1').text() + ' | ' + document.title;
		
		
		let template_json = {};
		$('[template]').each(function(i, elem){
			let jq_elem = $(elem).clone();
			$(elem).remove();
			let name = jq_elem.attr('template')
			jq_elem.removeAttr('template')
			template_json[name] = jq_elem[0].outerHTML
		})
		
		$('input[type="number"][min], input[type="number"][max]').on('input', function(){
			let jq_elem = $(this);
			let value = parseInt(jq_elem.val());
			let min = parseInt(jq_elem.attr('min'));
			let max = parseInt(jq_elem.attr('max'));
			if(min > value){
				jq_elem.val( min )
			}
			if(max < value){
				jq_elem.val( max )
			}
		})
	</script>
	
</body>
</html>