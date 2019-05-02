<?php

	header('content-type: text/html; charset=utf-8');
	
	require 'db.php'; // подключаем библиотеку RedbeenPHP, чтобы безопасно и просто работать с базой данных
	require('/phpQuery/phpQuery.php');	// подключаем phpQuery
	define('HOST','http://tbeauty.ru'); // это наша константа HOST, сюда мы вписываем адрес сайта-донора, который необходимо спарсить 

	
	// Очищаем таблицы post и postprev в БД, чтобы при повторном запуске парсера, данные в таблицах не дублировались. 
	// или сделать проверку на совпадение с данными в БД и уже потом производить запись
	R::wipe('post');
	R::wipe('postprev');

	$data_site = file_get_contents(HOST); // получаем страницу сайта-донора
	$document = phpQuery::newDocument($data_site);
	$content_prev = $document->find('.news .post'); // получаем массив постов

	// перебираем в цикле все посты
	foreach ($content_prev as $el) {
		// Парсим превьюшки статей
    $pq = pq($el); // pq это аналог $ в jQuery
    $h2 = $pq->find('.post-title h2 a')->attr('title'); // парсим заголовок статьи 
    $link = $pq->find('.post-title h2 a')->attr('href'); // парсим ссылку на статью
    $text = $pq->find('.post-content p'); // парсим текст в превью статьи
    $img = $pq->find('.wp-post-image')->attr('src'); // парсим ссылку на изображение в превью статьи

  	// Записываем информацию о превьюшках в базу данных
  	$post_prev = R::dispense('postprev'); 
		if(!empty($h2)) $post_prev->h2 = strip_tags($h2); // htmlspecialchars преобразует специальные символы в HTML сущности
		if(!empty($link)) $post_prev->link = HOST.$link;
		if(!empty($text)) $post_prev->text = strip_tags($text); 
		if(!empty($img)) $post_prev->img = HOST.$img;	 
		R::store($post_prev);

		// пробегаемся по всем ссылкам на посты и парсим контент из открытых статей
		if(!empty($link)) $data_link = file_get_contents(HOST.$link);
		$document_с = phpQuery::newDocument($data_link);
		$content = $document_с->find('.broden-ajax-content');

		foreach ($content as $element) {
			$pq2 = pq($element);
			$h1 = $pq2->find('.post-title h1'); // парсим главный заголовок статьи
			$text_all = $pq2->find('.article__content .txt'); // парсим контент часть статьи
  	}
    
	// Если надо будет пройтись по пагинации:
	/*
	$next = $doc->find('.pages-nav .current')->next()->attr('href');
	 if( !empty($next) ){
		 parse(..);
	 }
	*/
	
  	// Записываем информацию о статьях в базу данных
  	$post = R::dispense('post'); 
		if(!empty($h1)) $post->h1 = strip_tags($h1);
		if(!empty($text_all)) $post->text = strip_tags($text_all); 
		R::store($post);

  }
  
  
  // в итоге:
  // в БД автоматически будут 2 таблицы: postprev, post
  // в таблице "postprev": id, h2, link, text, img - информация о превью статей (картинка, заголовок, небольшой текст и ссылка на сам пост). 
  // в таблицу "post": 	id, h1, text -  запишется уже полная информация о статьях.