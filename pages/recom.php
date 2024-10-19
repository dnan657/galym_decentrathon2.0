<?php
	
?>

<h1>Рекомендации книг</h1>

<div class="list_book"></div>

<a template="item_book" href="" class="item_book  mt-4  card  px-3 py-2" style="text-decoration: none;">
	<h2 class="mb-2">
		<span column="title"></span>
	</h2>
	<div column="description  mb-2"></div>
	<div class="row  text-muted  text-small">
		<div class="col-1">#<span column="id"></span></div>
		<div class="col-3">Дата: <span column="public_date"></span></div>
		<div class="col-4">Автор: <span column="author"></span></div>
		<div class="col-4">Жанр: <span column="genre"></span></div>
	</div>
</a>



<script>

function find(){
	let jq_list = $('.list_book');
	jq_list.html('');
	my_api('book_recom', {}, $(this), function(response){
		if(response['error'] === ''){
			
			response['data'].forEach(function(item_json){
				let jq_item = $(template_json['item_book']);
				
				jq_item.attr('href', '/book/'+item_json['id'])
				
				Object.keys(item_json).forEach(function(key){
					jq_item.find('[column="'+key+'"]').text( item_json[key] || '-' )
				})
				
				jq_list.append(jq_item);
			})
			
			if(response['data'].length == 0){
				jq_list.append('<h2 class="text-muted  text-center  mt-4">Пусто</h2>');
			}
			
		}
	})
}

find();

</script>