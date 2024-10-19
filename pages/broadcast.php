<?php

$is_create = $page_id == 'create';
$is_owner = false;
$user_id = user_get()['id'];


$translation_data = [
	'id'			=> '',
	'title'			=> '',
	'description'	=> '',
	'data'			=> '',
	'data_text'		=> '',
	'author'		=> '',
	'status'		=> '',
	'public_date'	=> date('Y-m-d H:i:s'),
	'create_date'	=> date('Y-m-d H:i:s'),
	'user_id'		=> '',
];

if($is_create){
	
	if( user_get() == false ){
		redirect('/');
	}
	
}else{
	
	$item_translation = row_id('translations', $page_id);
	
	if( $item_translation ){
		$translation_data = $item_translation;
	}else{
		exit('<h1 class="text-danger">Item not found</h1>');
	}
	
	$is_owner = user_get()['id'] == $translation_data['user_id'];
}

$form_disabled = ($is_create || $is_owner) ? '' : 'disabled=disabled';


?>

<h1>
	<?php echo(htmlentities($is_create ? 'Create Audio Broadcast' : ('Audio Broadcast #' . $page_id))); ?>
</h1>


<?php if( $is_create == false && $is_owner == false ){ ?>


<div class="mb-2  text-muted  small">Transcript Broadcast</div>
<div class="chat_box  border  rounded" style="height: 300px; overflow-y: scroll;">
	<div id="history_list"><?php echo(htmlentities($translation_data['transcript'])); ?></div>
</div>

<script>

window.addEventListener("load", (event) => {

	let th_time = null;
	let jq_history_list = $('#history_list');
	let jq_chat_box = $(".chat_box");
	
	$(document).on('click', '#history_list  .item', function(){
		let jq_el = $(this);
		if( jq_el.hasClass('selected') ){
			jq_el.removeClass('selected')
		}else{
			jq_el.addClass('selected')
		}
		
		let selected_context = '';
		jq_history_list.find('.item.selected').each(function(i, jq_item){
			jq_item = $(jq_item)
			
			let text_msg = jq_item.find('.text_msg').text()
			let time = jq_item.find('.time').text()
			
			selected_context += "\n\n" + text_msg + " (" + time + ")"
			
		})
		
		$('.context_ask').val( ( selected_context || '' ).trim() )
	})


	let gl_old_transcript = "";

	function f_parse_transcript(text_transcript=""){
		let new_text_transcript = text_transcript.substr( gl_old_transcript.length )
		let html_history_text = '';
		new_text_transcript.trim().split("\n\n").forEach(function(item_msg){
			let tmp_item = $('<div class="item"><div class="text_msg"></div><div class="time"></div></div>');
			let splite_pattern = /\(([^()]+)\)$/;
			
			let time_match = item_msg.match(splite_pattern);

			let time = "";
			if (time_match) {
				// Получаем содержимое последних скобок
				time = time_match[1];
				// Удаляем эту часть из исходного текста
				item_msg = item_msg.replace(splite_pattern, '').trim();
				tmp_item.find('.text_msg').text(item_msg);
				tmp_item.find('.time').text(time);
				
				//html_history_text += tmp_item[0].outerHTML
				jq_history_list.append(tmp_item)
			}
		})
		gl_old_transcript = ( text_transcript )
		if( new_text_transcript != '' ){
			//jq_history_list.html( jq_history_list.html() + html_history_text);
			jq_chat_box.scrollTop(jq_chat_box[0].scrollHeight);
		}
		
		f_interval()
	}
	
	let tmp = jq_history_list.html();
	jq_history_list.html('');
	
	f_parse_transcript( tmp );


	function f_interval(){
		my_api('translation_live_get', {'id': $('[field_name="id"]').val()}, null, function(response){
			if(response['error'] !== ''){
				console.log('translation_live_get 1', response['error'])
			}else{
				if( response['status'] == 1 ){
					setTimeout(function(){
						f_parse_transcript( response['transcript'] || '' );
					}, 1000)
				}
			}
		})
	}

})

</script>

<?php 
}
?>

<?php if( !$is_create && $is_owner ){ ?>

<div class="row  mb-4">
	<div class="col  d-flex align-items-center flex-nowrap">
		<button id="switch_record_btn" class="btn btn-primary" title_start="Start broadcast" title_stop="Stop broadcast">Start broadcast</button>
		<div id="time_left_title" class="ms-2  text-muted  small"></div>
	</div>
	
	<div class="col  d-flex align-items-center flex-nowrap">
		<div class="me-2  text-muted  small">
			Language: 
		</div>
		<select id="lang_select" class="form-select  border">
			<option value="ru-RU">Russian (RU)</option>
			<option value="en-US">English (US)</option>
			<option value="de-DE">German (DE)</option>
			<!-- Добавьте другие языки по необходимости -->
		</select>
	</div>
</div>

<div class="mb-2  text-muted  small">Transcript Broadcast</div>
<div class="chat_box  border  rounded" style="height: 300px; overflow-y: scroll;">
	<div id="history_list"><?php echo(htmlentities($translation_data['transcript'])); ?></div>
</div>


<script>

let recognition = window.SpeechRecognition || window.webkitSpeechRecognition;;
let gl_is_listening = false;
let th_time = null;
let th_delete_temp_item = null;
let gl_time_left = <?php echo(htmlentities($translation_data['transcript_duration'])); ?>;
let jq_switch_record_btn = $('#switch_record_btn');
let jq_lang_select = $('#lang_select');
let jq_history_list = $('#history_list');
let jq_time_left_title = $('#time_left_title');
let jq_chat_box = $(".chat_box");


let old_history_text = jq_history_list.html();
let html_history_text = '';
old_history_text.trim().split("\n\n").forEach(function(item_msg){
	let tmp_item = $('<div class="item"><div class="text_msg"></div><div class="time"></div></div>');
	let splite_pattern = /\(([^()]+)\)$/;
	
    let time_match = item_msg.match(splite_pattern);

    let time = "";
    if (time_match) {
        // Получаем содержимое последних скобок
        time = time_match[1];
        // Удаляем эту часть из исходного текста
        item_msg = item_msg.replace(splite_pattern, '').trim();
		tmp_item.find('.text_msg').text(item_msg);
		tmp_item.find('.time').text(time);
		html_history_text += tmp_item[0].outerHTML
    }
})
jq_history_list.html(html_history_text);
jq_chat_box.scrollTop(jq_chat_box[0].scrollHeight);



// Проверка на наличие Web Speech API в браузере
if (!recognition) {
	alert('Ваш браузер не поддерживает Speech Recognition API');
} else {
	recognition = new recognition();
	recognition.continuous = true;
	recognition.interimResults = true; // Включаем промежуточные результаты

	let temp_transcript = ''; // Для хранения промежуточного текста

	// Обработка распознанного текста
	recognition.onresult = function(event) {
		let final_transcript = '';
		temp_transcript = '';
		
		clearTimeout( th_delete_temp_item );
		
		th_delete_temp_item = setTimeout(function(){
			let jq_temp_item = jq_history_list.find('.temp_item');
			
			if( ( jq_temp_item.find('.text_msg').text() || '' ).trim().length > 0 ){
				my_api('translation_live_send', {'id': $('[field_name="id"]').val(), 'text': jq_temp_item.find('.text_msg').text(), 'time': jq_temp_item.find('.time').text(), 'duration': gl_time_left}, null, function(response){
					if(response['error'] !== ''){
						console.log('translation_live_send 1', response['error'])
					}
				})
			}
			
			jq_temp_item.removeClass('temp_item');
		}, 4000)

		for (let i = event.resultIndex; i < event.results.length; i++) {
			let now_transcript = event.results[i][0].transcript.trim() + ' ';
			if (event.results[i].isFinal) {
				final_transcript += now_transcript;
			} else {
				temp_transcript += now_transcript;
			}
		}
		
		let jq_temp_item = jq_history_list.find('.temp_item')
		
		let need_scrool = f_is_scrolled_to_bottom(jq_chat_box);
		
		if (final_transcript) {
			jq_temp_item.find('.text_msg').text(final_transcript)
			jq_temp_item.removeClass('temp_item')
			jq_temp_item.find('.time').text( jq_temp_item.find('.time').text() + ' - ' + f_time_elapsed(gl_time_left) )
			
			
			my_api('translation_live_send', {'id': $('[field_name="id"]').val(), 'text': jq_temp_item.find('.text_msg').text(), 'time': jq_temp_item.find('.time').text(), 'duration': gl_time_left}, null, function(response){
				if(response['error'] !== ''){
					console.log('translation_live_send 2', response['error'])
				}
			})
			
		}else{
			if (temp_transcript.trim() === '') {
                // Если промежуточный текст пустой, удаляем temp_item
                jq_temp_item.remove();
			}else{
				if(jq_temp_item.length == 0){
					jq_temp_item = $('<div class="item  temp_item"><div class="text_msg"></div><div class="time"></div></div>');
					jq_temp_item.find('.time').text( f_time_elapsed(gl_time_left) )
					jq_history_list.append( jq_temp_item );
				}
				jq_temp_item.find('.text_msg').text( temp_transcript )
			}
		}
		
		if( need_scrool ){
			jq_chat_box.scrollTop(jq_chat_box[0].scrollHeight);
		}
	};

	// Обработка ошибок
	recognition.onerror = function(event) {
		console.error('Speech Recognition Error', event);
	};
	
	
	// Перезапуск при завершении
	recognition.onend = function() {
		if (gl_is_listening) {
			recognition.start(); // Перезапуск после завершения
		}
	};
}

function f_get_current_time() {
	let now = new Date();
	let hours = String(now.getHours()).padStart(2, '0');
	let minutes = String(now.getMinutes()).padStart(2, '0');
	let seconds = String(now.getSeconds()).padStart(2, '0');
	
	return `${hours}:${minutes}:${seconds}`;
}

function f_time_elapsed(totalSeconds) {
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
	
	let arr_time = [];
	
    // Форматируем значения с ведущими нулями
	if( hours > 0 ){
		arr_time.push( String(hours).padStart(2, '0') )
	}
    arr_time.push( String(minutes).padStart(2, '0') )
    arr_time.push( String(seconds).padStart(2, '0') )

    // Возвращаем отформатированное время
    return arr_time.join(':');
}

// Функция, которая проверяет, находится ли скролл внизу контейнера
function f_is_scrolled_to_bottom(jq_elem) {
	return (Math.abs(jq_elem[0].scrollHeight - (jq_elem.scrollTop() + jq_elem.innerHeight())) <= 10) || (jq_elem[0].scrollHeight - jq_elem.innerHeight() <= 5);
}


th_time = setInterval(function(){
	if( gl_is_listening == true ){
		gl_time_left += 1;
		jq_time_left_title.text( f_time_elapsed( gl_time_left ) )
	}
}, 1000)

// Кнопка "Switch Record"
jq_switch_record_btn.click(function() {
	
	// Start Record
	if( gl_is_listening == false ){
		$('body').addClass('start_broadcast');
		jq_time_left_title.text( f_time_elapsed( gl_time_left ) )
		jq_switch_record_btn.addClass('btn-danger').removeClass('btn-primary').text( jq_switch_record_btn.attr('title_stop') )
		let language = jq_lang_select.val();
		recognition.lang = language;
		recognition.start();
		gl_is_listening = true;
		jq_lang_select.prop('disabled', true);
	}else{
		$('body').removeClass('start_broadcast');
		jq_switch_record_btn.addClass('btn-primary').removeClass('btn-danger').text( jq_switch_record_btn.attr('title_start') )
		recognition.stop();
		gl_is_listening = false;
		jq_lang_select.prop('disabled', false);
		
	}
	
})

</script>

<?php } ?>


<?php if( !$is_create ){ ?>

<h2 class="mt-5  mb-3  text-center">List Questions</h2>

<div class="mb-2  text-muted  small">History Questions</div>
<div class="border  rounded" style="height: 300px; overflow-y: scroll;">
	<div class="history_ask"></div>
</div>

<style>

#modal_chatbot  .history_ask  .item_ask  b{
	display: none;
}
#modal_chatbot  .history_ask  .item_ask  .context{
	display: none;
}
#modal_chatbot  .history_ask  .item_ask  .question{
	text-align: right;
	padding-left: 20%;
	margin-left: auto;
	width: max-content;
	max-width: 100%;
	margin-bottom: 20px;
}
#modal_chatbot  .history_ask  .item_ask  .answer{
	text-align: left;
	padding-right: 20%;
	margin-bottom: 20px;
}

#modal_chatbot  .history_ask  .item_ask  .question  span,
#modal_chatbot  .history_ask  .item_ask  .answer  span{
	padding: 10px 15px;
	border-radius: 5px;
	background: #eeeeee;
	display: block;
}
#modal_chatbot  .history_ask  .item_ask  .answer  span{
	background: #B3E5FC;
}

#modal_chatbot  .history_ask  .item_ask{
	border: none;
}


</style>

<?php
	if( !$is_owner ){
?>
	
	<div class="mt-2">
		<label class="form-label">Context</label>
		<textarea disabled type="text" class="form-control  form-control  context_ask" placeholder="Selects messages(quotes)" rows=4></textarea>
	</div>
	<div class="my-2">
		<label class="form-label">Your question</label>
		<textarea type="text" class="form-control  form-control  question_ask" placeholder="Your question" rows=4></textarea>
	</div>
	
	<button type="button" class="btn_ask  btn btn-primary btn-lg d-block w-100  mb-3">Ask</button>
	
	<button type="button" class="btn btn-dark  btn-lg  d-block  w-100" data-bs-toggle="modal" data-bs-target="#modal_chatbot">Open Window with ChatBot</button>

	<!-- Модальное окно -->
	<div class="modal fade" id="modal_chatbot" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-scrollable">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title fs-5"><?php echo($item_translation['title']); ?></h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body  p-0">
					<div class="history_ask"></div>
				</div>
				<div class="modal-footer  p-0">
					<div class="input-group">
						<input type="text" class="form-control  modal_question_ask px-4  py-3" placeholder="Your question">
						<button type="button" class="btn btn-success  btn_ask   px-4">Ask</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	
<?php
	}
?>


<div template="item_ask" class="item_ask">
	<div class="context">
		<b class="me-2">Context quote:</b>
		<span column="context"></span>
	</div>
	<div class="question">
		<b class="me-2">Question:</b>
		<span column="question"></span>
	</div>
	<div class="answer">
		<b class="me-2">Answer:</b>
		<span column="answer"></span>
	</div>
</div>


<script>

let jq_context = $('.context_ask');
let jq_question = $('.question_ask');
let jq_modal_question = $('.modal_question_ask');
let jq_history_ask = $('.history_ask');

$('.btn_ask').on('click', function(){
	$('#history_list').find('.item.selected').removeClass('selected');
	
	jq_context.attr('disabled', true)
	jq_question.attr('disabled', true)
	jq_modal_question.attr('disabled', true)
	
	let context = jq_context.val();
	let question = jq_question.val() || jq_modal_question.val();
	
	my_api('ask_create', {'translation_id': <?php echo($page_id); ?>, 'context': context, 'question': question }, $(this), function(response){
		jq_context.attr('disabled', false)
		jq_question.attr('disabled', false)
		jq_modal_question.attr('disabled', false)
	
		if(response['error'] === ''){
			jq_context.val('')
			jq_question.val('')
			jq_modal_question.val('')
			
			let jq_item = $(template_json['item_ask']);
			
			Object.keys(response['data']).forEach(function(key){
				jq_item.find('[column="'+key+'"]').text( response['data'][key] || '-' )
			})
			
			jq_history_ask.parent().scrollTop(jq_history_ask.parent()[1].scrollHeight);
			
			jq_history_ask.append( jq_item );
		}
	})
})

let gl_count_asks = 0;

function asks_get(){
	my_api('ask_search', {'translation_id': <?php echo($page_id); ?>}, null, function(response){
		if(response['error'] === ''){
			
			let arr_new_ask = response['data'].slice(gl_count_asks)
			gl_count_asks += arr_new_ask.length;
			
			arr_new_ask.forEach(function(item_json){
				let jq_item = $(template_json['item_ask']);
				
				Object.keys(item_json).forEach(function(key){
					jq_item.find('[column="'+key+'"]').text( item_json[key] || '-' )
				})
				
				jq_history_ask.append(jq_item);
			})
			
			if( arr_new_ask.length > 0 ){
				jq_history_ask.parent().scrollTop(jq_history_ask.parent()[1].scrollHeight);
			}
			
			if(<?php echo($is_owner ? 'true' : 'false'); ?>){
				setTimeout(function(){
					asks_get();
				}, 2000)
			}
			
		}
	})
}

asks_get();

</script>

<?php } ?>


<h2 class="mt-5  mb-3  text-center">Info</h2>

<div class="mb-3">
	<label class="form-label">Title</label>
	<input type="text" class="form-control  form-control" field_name="title" placeholder="Title" value="<?php echo(htmlentities($translation_data['title'])); ?>"  <?php echo($form_disabled); ?>>
</div>

<div class="mb-3">
	<label class="form-label">Description</label>
	<textarea type="text" class="form-control  form-control" field_name="description" placeholder="Description"  rows=4  <?php echo($form_disabled); ?>><?php echo(htmlentities($translation_data['description'])); ?></textarea>
</div>

<?php if( $is_create || $is_owner ){ ?>

	<div class="row">
		<div class="mb-3  col-lg-6">
			<div class="mb-3">
				<label class="form-label  d-flex">
					Data
					<input type="file" id="file_input" accept=".txt" style="display: none;">
					<button type="button" class="btn_open_file  btn btn-info btn-sm  py-0  ms-auto  me-2">Open File</button>
					<button type="button" class="btn_data_roasting  btn btn-warning btn-sm  py-0">Improve</button>
				</label>
				<textarea type="text" class="form-control  form-control" field_name="data" placeholder="Data"  rows=4  <?php echo($form_disabled); ?>><?php echo(htmlentities($translation_data['data'])); ?></textarea>
			</div>
		</div>
		
		<div class="mb-3  col-lg-6">
			<div class="mb-3">
				<label class="form-label">
					Improved Data
				</label>
				<textarea type="text" class="form-control  form-control" field_name="data_roasting" placeholder="Improved Data"  rows=4  <?php echo($form_disabled); ?>><?php echo(htmlentities($translation_data['data_roasting'])); ?></textarea>
			</div>
		</div>
	</div>

<?php } ?>

<div class="row">
	<div class="mb-3  col-6">
		<label class="form-label">Author Name</label>
		<input type="text" class="form-control  form-control" field_name="author" placeholder="Author" value="<?php echo(htmlentities($translation_data['author'])); ?>"  <?php echo($form_disabled); ?>>
	</div>
	
	<div class="mb-3  col-6">
		<label class="form-label">Public date</label>
		<input type="datetime-local" class="form-control  form-control" field_name="public_date" placeholder="Date" value="<?php echo(htmlentities($translation_data['public_date'])); ?>"  <?php echo($form_disabled); ?>>
	</div>
</div>

<div class="mb-3">
	<label class="form-label">Status</label>
	<select class="form-select" field_name="status"  <?php echo($form_disabled); ?>>
		<option value="0" <?php echo( $translation_data['status'] == 0 ? 'selected' : ''); ?>>Draft</option>
		<option value="1" <?php echo( $translation_data['status'] == 1 ? 'selected' : ''); ?>>Live ON</option>
		<option value="2" <?php echo( $translation_data['status'] == 2 ? 'selected' : ''); ?>>Completed</option>
	</select>
</div>

<div class="row">
	<div class="mb-3  col-6">
		<label class="form-label">Create date</label>
		<input type="datetime-local" class="form-control  form-control" field_name="create_date" placeholder="Date" value="<?php echo(htmlentities($translation_data['create_date'])); ?>"  disabled>
	</div>

	<div class="mb-3  col-6">
		<label class="form-label">ID</label>
		<input type="text" class="form-control  form-control" field_name="id" placeholder="ID" value="<?php echo(htmlentities($translation_data['id'])); ?>" disabled>
	</div>
</div>

<?php if( $is_create || $is_owner ){ ?>
	<button type="button" class="btn_save  btn btn-primary btn-lg d-block w-100  mt-4  mb-3">Save</button>
	
	
	<script>

	$('.btn_open_file').on('click', function(){
		$('#file_input').click(); // Открытие скрытого input
	})
	
	// Чтение файла после его выбора
	$('#file_input').on('change', function (event) {
		const file = event.target.files[0];
		if (file) {
			const reader = new FileReader();
			reader.onload = function (e) {
				$('[field_name="data"]').val(e.target.result); // Запись содержимого файла в textarea
			};
			reader.readAsText(file);
		} else {
			alert("Select txt file");
		}
		$('#file_input').val('');
	});

	$('.btn_data_roasting').on('click', function(){
		
		let jq_data = $('[field_name="data"]');
		let jq_data_roasting = $('[field_name="data_roasting"]');
		
		my_api('translation_data_roasting', {'data': jq_data.val()}, $(this), function(response){
			if(response['error'] === ''){
				toastr.success('Success')
				jq_data_roasting.val( response['data'] )
			}
		})
		
	})

	$('.btn_save').on('click', function(){
		
		my_api('<?php echo( $is_create ? 'translation_create' : 'translation_update'); ?>', get_field(), $(this), function(response){
			if(response['error'] === ''){
				toastr.success('Saved')
				setTimeout(function(){
					location.href = '/broadcast/'+response['data']['id'];
				}, 100)
			}
		})
		
	})
	
	</script>
	
<?php } ?>

<?php if( $is_owner ){ ?>
	<button type="button" class="btn_delete  btn btn-outline-danger btn-lg d-block w-100  mt-4  mb-3">Delete</button>

	<script>

	$('.btn_delete').on('click', function(){
		
		if (window.confirm("Are you sure you want to delete?")) {
		
			my_api('translation_delete', get_field(), $(this), function(response){
				if(response['error'] === ''){
					toastr.success('Deleted')
					setTimeout(function(){
						location.href = '/';
					}, 100)
				}
			})
		
		}
		
	})


	</script>

<?php } ?>

