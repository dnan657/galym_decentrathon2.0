<?php


$uri_arr = explode('/', $_SERVER['REQUEST_URI']);

$method_name = $uri_arr[2];

$method_json = [
	'user_login'		=> 'user_login',
	'user_create'		=> 'user_create',
	'user_get'			=> 'user_me',
	'user_update'		=> 'user_update',
	
	'translation_live_send'		=> 'translation_live_send',
	'translation_live_get'		=> 'translation_live_get',
	'translation_data_roasting'	=> 'translation_data_roasting',
	'translation_search'		=> 'translation_search',
	'translation_create'		=> 'translation_create',
	'translation_update'		=> 'translation_update',
	'translation_delete'		=> 'translation_delete',
	
	'ask_search'		=> 'ask_search',
	'ask_create'		=> 'ask_create',
];

$func = $method_json[$method_name];

$_POST = json_decode(file_get_contents('php://input'), true);
$request_json = $_POST;

$response_json = [
	'error' => '',
	'data' => [],
];

if($func == false){
	$response_json['error'] = 'Unknown method';
	api_response($response_json);
}else{
	$func($request_json, $response_json);
}


function api_valid($value="", $min=0, $max=255){
	$str_value = $value . '';
	if($min !== false){
		if( mb_strlen($str_value) < $min ){
			api_response(['data'=>[], 'error'=>'Min '.$min .' length', 'value'=>$value]);
		}
	}
	if($max !== false){
		if( mb_strlen($str_value) > $max ){
			api_response(['data'=>[], 'error'=>'Max '.$max .' length', 'value'=>$value]);
		}
	}
	
	return $value;
}

function api_сhat_gpt($request_json=[]) {
	
	$response_json = [
		'data' => '',
		'error' => '',
	];
	
	$system_prompt = $request_json['system'];
	$user_prompt = $request_json['user'];
	$max_tokens = isset($request_json['max_tokens']) ? $request_json['max_tokens'] : 200;
	$temperature = isset($request_json['temperature']) ? $request_json['temperature'] : 0.7;

    // Ваш API ключ ChatGPT
    $api_key = 'sk-2vJsD0gPkcS-7_P7Z0SUAgZfkvZx9w-dbgHVn_Oj1aT3BlbkFJnH74F4ZPZo5fH9GeHariNtFT1dab3hxLu6Qyv7gYcA';

    // URL для отправки запроса к ChatGPT API
    $api_url = 'https://api.openai.com/v1/chat/completions';

    // Параметры для отправки запроса
    $data = [
        'model' => 'gpt-4o-mini', // Укажите нужную модель
        'messages' => [
			[
				'role' => "system",
				'content' => $system_prompt
			],
            [
                'role' => 'user',
				'content' => $user_prompt
            ]
        ],
        'max_tokens' => $max_tokens, // Максимальное количество токенов в ответе
        'temperature' => $temperature // Температура для контроля "креативности"
    ];

    // Настройка cURL для отправки POST-запроса
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // Выполняем запрос и получаем ответ
    $response = curl_exec($ch);

    // Проверяем ошибки cURL
    if (curl_errno($ch)) {
        //echo 'Error:' . curl_error($ch);
        //return null;
		$response_json['error'] = 'Request ChatGPT API';
		return $response_json;
    }

    // Закрываем cURL-сессию
    curl_close($ch);

    // Декодируем JSON-ответ
    $result = json_decode($response, true);

    // Возвращаем ответ от ChatGPT
    if (isset($result['choices'][0]['message']['content'])) {
        $response_json['data'] = $result['choices'][0]['message']['content'];
    } else {
		$response_json['error'] = 'No response from ChatGPT API';
		$response_json['error'] = $response;
    }
	
	return $response_json;
}




function ask_create($request_json, $response_json){
	
	$question = db_value( api_valid($request_json['question'], 1, 1000) );
	$context = db_value( api_valid($request_json['context'], 0, 10000) );
	
	$translation_id = intval( $request_json['translation_id'] );
	
	$item_translation = row_id('translations', $translation_id);
	if( !isset($item_translation) ){
		$response_json['error'] = 'Not find audio broadcast';
		api_response($response_json);
	}
	
	$system = "
Audio Broadcast title:
{$item_translation['title']}

Audio Broadcast description:
{$item_translation['description']}

Audio Broadcast author:
{$item_translation['author']}

Audio Broadcast data:
{$item_translation['transcript']}

Audio Broadcast transcript:
{$item_translation['transcript']}
	";
	
	$user = "
Part of the audio broadcast:
{$context}

User question:
{$question}
";
	
	$answer = api_сhat_gpt(['user'=>$user, 'system'=>$system, 'max_tokens'=>10000])['data'];
	
	db_query("INSERT INTO asks (context, question, answer, translation_id) VALUES ('{$context}', '{$question}', '{$answer}', '{$translation_id}')");
	
	$response_json['data'] = db_query("SELECT * FROM asks WHERE context = '{$context}' AND question = '{$question}' AND answer = '{$answer}' AND translation_id = '{$translation_id}'  LIMIT 1")[0];
	
	api_response($response_json);
}

function ask_search($request_json, $response_json){
	
	$translation_id = intval($request_json['translation_id']);
	
	$response_json['data'] = db_query("SELECT * FROM asks WHERE translation_id = '{$translation_id}' ORDER BY id ASC ");
	
	api_response($response_json);
}



function translation_data_roasting($request_json, $response_json){
	
	$data = api_valid($request_json['data'], 10, 50000);
	
	$system = '
You are an AI assistant who acts as an experienced methodologist analyzing lesson plans. You will provide deep and constructive analysis based on modern pedagogical practices and research in the field of education.

Main areas of analysis:
Student engagement: evaluation of methods and techniques that promote active participation of students in the learning process.
Compliance with educational standards: checking the alignment of the lesson content with the requirements of the curriculum and state standards.
Lesson structure and logic: analysis of the sequence and coherence of lesson stages.
Differentiation and inclusion: recommendations for adapting the material for students with different needs and abilities.
Evaluation and feedback: analysis of methods for assessing student progress and providing them with constructive feedback.

When analyzing, you will:
Give specific examples from the lesson plan, supporting your conclusions with them.
Offer clear, implementable recommendations for improving the plan.
Use professional pedagogical terminology.
Maintain a respectful tone and a constructive approach in feedback.
Consider the context of the lesson (subject, age of students, type of school).
Offer innovative approaches and technologies that improve the effectiveness of learning.
Pay attention to interdisciplinary connections and the development of meta-subject skills.

You will answer in the same language in which the document being analyzed is written. In case of ambiguity, you will ask for additional information. The purpose of the analysis is to promote the professional growth of the teacher and improve the quality of the educational process.
	';
	//$system = 'Улучши мой текст и напиши его';
	
	$response_json = api_сhat_gpt(['user'=>$data, 'system'=>$system, 'max_tokens'=>10000]);
	
	api_response($response_json);
}



function translation_search($request_json, $response_json){
	
	$search = db_value($request_json['search']);
	$id = intval($request_json['id']);
	
	if( $id == 0 ){
		$response_json['data'] = db_query("
			SELECT
				*
			FROM
				translations
			WHERE
				user_id = '" . user_get()['id'] . "'
				AND (
					title			LIKE '%{$search}%' 
					OR  description	LIKE '%{$search}%'
					OR  author		LIKE '%{$search}%'
				)
			ORDER BY
				id DESC
		");
	}else{
		$response_json['data'] = row_id('translations', $id);
	}
	
	api_response($response_json);
}



function translation_create($request_json, $response_json){
	
	if(user_get() == false){
		$response_json['error'] = 'No access';
		api_response($response_json);
	}
	
	if (!valid_date($request_json['public_date'], 'Y-m-d H:i:s')) {
		$response_json['error'] = 'The date is incorrect';
		api_response($response_json);
	}
	
	$title = db_value( api_valid($request_json['title'], 1) );
	$description = db_value( api_valid($request_json['description'], 1) );
	$author = db_value( api_valid($request_json['author'], 1) );
	$status = db_value( api_valid($request_json['status'], 1) );
	$data = db_value( api_valid($request_json['data'], 0, 50000) );
	$data_roasting = db_value( api_valid($request_json['data_roasting'], 0, 50000) );
	$public_date = db_value( api_valid($request_json['public_date'], 10) );
	$user_id = user_get()['id'];
	
	if($user_id == false){
		$response_json['error'] = 'No access';
		api_response($response_json);
	}
	
	db_query("INSERT INTO translations (title, description, author, data, data_roasting, status, public_date, user_id) VALUES ('{$title}', '{$description}', '{$author}', '{$data}', '{$data_roasting}', '{$status}', '{$public_date}', '{$user_id}')");
	
	$response_json['data'] = db_query("SELECT * FROM translations WHERE title = '{$title}' AND description = '{$description}' AND author = '{$author}' AND status = '{$status}' AND public_date = '{$public_date}' AND user_id = '{$user_id}' ORDER BY id DESC LIMIT 1")[0];
	
	api_response($response_json);
}


function translation_live_send($request_json, $response_json){
	
	if(user_get() == false){
		$response_json['error'] = 'No access';
		api_response($response_json);
	}
	
	$id = intval($request_json['id']);
	$text = api_valid(mb_trim($request_json['text']), 1);
	$time = api_valid($request_json['time'], 1);
	$duration = intval($request_json['duration']);
	$user_id = user_get()['id'];
	
	$transcript = db_value( $text . ' (' .$time. ')' );
	$transcript_date = date('Y-m-d H:i:s', strtotime('now'));
	
	$item = db_query("SELECT * FROM translations WHERE id = '{$id}' LIMIT 1")[0];
	if($item == false){
		$response_json['error'] = 'Item not found';
		api_response($response_json);
	}
	
	$transcript = $item['transcript'] . "\n\n" . $transcript;
	
	if($user_id != $item['user_id']){
		$response_json['error'] = 'No access';
		api_response($response_json);
	}
	
	db_query("UPDATE translations SET transcript = '{$transcript}', transcript_date = '{$transcript_date}', transcript_duration = '{$duration}' WHERE id = {$id}");
	
	$response_json['data'] = $item;
	
	api_response($response_json);
}

function translation_live_get($request_json, $response_json){
	
	$id = intval($request_json['id']);
	
	$item_translation = row_id('translations', $id);
	
	if($item_translation == false){
		$response_json['error'] = 'Item not found';
		api_response($response_json);
	}
	
	$response_json['status'] = $item_translation['status'];
	$response_json['transcript'] = $item_translation['transcript'] ?: '';
	
	api_response($response_json);
}


function translation_update($request_json, $response_json){
	
	if(user_get() == false){
		$response_json['error'] = 'No access';
		api_response($response_json);
	}
	
	if (!valid_date($request_json['public_date'], 'Y-m-d H:i:s')) {
		$response_json['error'] = 'The date is incorrect';
		api_response($response_json);
	}
	
	$id = intval($request_json['id']);
	$title = db_value( api_valid($request_json['title'], 1) );
	$description = db_value( api_valid($request_json['description'], 1) );
	$author = db_value( api_valid($request_json['author'], 1) );
	$status = db_value( api_valid($request_json['status'], 1) );
	$data = db_value( api_valid($request_json['data'], 0, 50000) );
	$data_roasting = db_value( api_valid($request_json['data_roasting'], 0, 50000) );
	$public_date = db_value( api_valid($request_json['public_date'], 10) );
	$user_id = user_get()['id'];
	
	$item = db_query("SELECT * FROM translations WHERE id = '{$id}' LIMIT 1")[0];
	if($item == false){
		$response_json['error'] = 'Item not found';
		api_response($response_json);
	}
	
	if($user_id != $item['user_id']){
		$response_json['error'] = 'No access';
		api_response($response_json);
	}
	
	db_query("UPDATE translations SET title = '{$title}', description = '{$description}', status = '{$status}', author = '{$author}', data = '{$data}', data_roasting = '{$data_roasting}', public_date = '{$public_date}' WHERE id = {$id}");
	
	$response_json['data'] = $item;
	
	api_response($response_json);
}

function translation_delete($request_json, $response_json){
	
	if(user_get() == false){
		$response_json['error'] = 'No access';
		api_response($response_json);
	}
	
	$id = intval($request_json['id']);
	$user_id = user_get()['id'];
	
	$item = db_query("SELECT * FROM translations WHERE id = '{$id}' LIMIT 1")[0];
	if($item == false){
		$response_json['error'] = 'Item not found';
		api_response($response_json);
	}
	
	if($user_id != $item['user_id']){
		$response_json['error'] = 'No access';
		api_response($response_json);
	}
	
	db_query("DELETE FROM translations WHERE id = '{$id}'");
	
	api_response($response_json);
}









function user_login($request_json, $response_json){
	
	if (!filter_var($request_json['email'], FILTER_VALIDATE_EMAIL)) {
		$response_json['error'] = 'Incorrect email';
		api_response($response_json);
	}
	
	$email = db_value($request_json['email']);
	$pass_hash = hash('sha256', api_valid($request_json['pass'], 1) );
	
	$user = db_query("SELECT * FROM users WHERE email = '{$email}' LIMIT 1")[0];
	
	if($user['pass_hash'] == $pass_hash){
		cookie_set('user', $user['id'].'-'.$user['pass_hash']);
	}else{
		$response_json['error'] = 'Incorrect Email or Password';
	}
	
	api_response($response_json);
}

function user_create($request_json, $response_json){
	
	if ( !filter_var($request_json['email'], FILTER_VALIDATE_EMAIL)) {
		$response_json['error'] = 'Incorrect email';
		api_response($response_json);
	}
	
	$email = db_value($request_json['email']);
	$name = db_value(api_valid($request_json['name'], 1));
	$pass_hash = hash('sha256', api_valid($request_json['pass'], 1) );
	
	$user = db_query("SELECT * FROM users WHERE email = '{$email}' LIMIT 1")[0];
	if($user != false){
		$response_json['error'] = 'A user with this email already exists';
		api_response($response_json);
	}
	
	db_query("INSERT INTO users (email, name, pass_hash) VALUES ('{$email}', '{$name}', '{$pass_hash}')");
	
	$user = db_query("SELECT * FROM users WHERE email = '{$email}' LIMIT 1")[0];
	
	cookie_set('user', $user['id'].'-'.$user['pass_hash']);
	
	api_response($response_json);
}

function user_update($request_json, $response_json){
	
	if ( !filter_var($request_json['email'], FILTER_VALIDATE_EMAIL)) {
		$response_json['error'] = 'Incorrect email';
		api_response($response_json);
	}
	
	if(user_get() == false){
		$response_json['error'] = 'No access';
		api_response($response_json);
	}
	
	//$id = intval($request_json['id']);
	$email = db_value($request_json['email']);
	$name = db_value( api_valid($request_json['name'], 1) );
	$pass_hash = hash('sha256', api_valid($request_json['pass'], 1) );
	$id = user_get()['id'];
	
	$user = db_query("SELECT * FROM users WHERE id = '{$id}' LIMIT 1")[0];
	if($user == false){
		$response_json['error'] = 'User not found';
		api_response($response_json);
	}
	
	db_query("UPDATE users SET email='{$email}', name='{$name}', pass_hash='{$pass_hash}' WHERE id = {$id}");
	
	$user = db_query("SELECT * FROM users WHERE id = '{$id}' LIMIT 1")[0];
	
	cookie_set('user', $user['id'].'-'.$user['pass_hash']);
	
	api_response($response_json);
}

function user_me($request_json, $response_json){
	
	$response_json['data'] = user_get();
	
	api_response($response_json);
}




?>