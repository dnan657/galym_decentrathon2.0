<?php

$is_owner = user_get()['id'] ? true : false;
	
?>


<h1>Find Broadcast</h1>

<input type="number" class="form-control  mb-4" id="find_input" placeholder="Number Broadcast">
  
<button class="btn  btn-primary  btn-lg  mx-auto  d-block" style="width: max-content;" id="find_btn">Find</button>

<script>
$('#find_btn').on('click', function(){
	let num = parseInt($('#find_input').val());
	if( isNaN(num) ){
		return;
	}
	window.location.href = '/broadcast/' + num
})
</script>


<style>
#history_list  a{
	display: block;
	width: 100%;
	padding: 10px 20px;
	margin-bottom: 10px;
	font-size: 20px;
	text-decoration: none;
	border: 1px solid;
	border-radius: 5px;
}
</style>


<?php
	if($is_owner){
?>
	<div class="mb-2  mt-5  text-muted  small">My List Broadcast</div>
	<div id="history_list">
		<?php
			$arr_item = db_query("SELECT * FROM translations WHERE user_id = '". user_get()['id'] ."' ORDER BY id DESC");
			foreach($arr_item as $item_json){
		?>
				<a href="/broadcast/<?php echo($item_json['id']); ?>">#<?php echo($item_json['id'] . ' – ' . $item_json['title']); ?></a>
		<?php
			}
		?>
	</div>

<?php
	}
?>