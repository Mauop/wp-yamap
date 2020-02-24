<?php
defined( 'ABSPATH' ) or die();

// хук в инит функцию
add_action( 'init', 'yandexmap_posttype', 0 ); 
//Создаем кастомный тип записи
function yandexmap_posttype() {
	$labels = array(
		'name' 			=> 'Yandex карты',
		'singular_name' => 'Yandex карты',
		'menu_name' 	=> 'Yandex карты',
		'all_items' 	=> 'Все карты',
		'add_new' 		=> 'Добавить карту '
	);

	$args = array(
		'label' 		=> 'Yandex maps',
		'description' 	=> 'Yandex maps',
		'labels' 		=> $labels,
		'supports' 		=> array( 'title', 'editor' ),
		'public' 		=> true,
		'show_ui' 		=> true,
		'show_in_menu' 	=> true,
		'menu_position' => 5,
		'menu_icon'		=> 'dashicons-location-alt'
	);

	// регистрируем кастомный тип yamaps
	register_post_type( 'yamaps', $args );
}


// Добавляем блок с полями координат для кастомных постов yamaps
add_action('add_meta_boxes', 'yamap_add_custom_box');
function yamap_add_custom_box(){
	add_meta_box( 'yamap_section', 'Координаты', 'yamap_meta_box_callback', 'yamaps' );
}

// блок полей координат
function yamap_meta_box_callback( $post ) {

	// nonce для верификации
	wp_nonce_field( plugin_basename(__FILE__), 'yamap_noncename' );

	// значение полей координат
	$latitude 	= get_post_meta( $post->ID, 'latitude', 1 );
	$longitude 	= get_post_meta( $post->ID, 'longitude', 1 );

	// Инпуты для введения полей координат
	echo '<p><label for="latitude">' . 'Широта' . '</label> ';
	echo '<input type="text" id="latitude" name="latitude" value="'. $latitude .'" size="25" /></p>';
	echo '<p><label for="longitude">' . 'Долгота' . '</label> ';
	echo '<input type="text" id="longitude" name="longitude" value="'. $longitude .'" size="25" /></p>';
}

// Сохраням значения координат при сохранении поста
add_action( 'save_post', 'yamap_save_postdata' );
function yamap_save_postdata( $post_id ) {
	// Убедимся что поля установлены.
	if ( ! isset( $_POST['longitude'] ) || ! isset( $_POST['latitude'] ) )
		return;

	// проверяем nonce нашей страницы, потому что save_post может быть вызван с другого места.
	if ( ! wp_verify_nonce( $_POST['yamap_noncename'], plugin_basename(__FILE__) ) )
		return;

	// если это автосохранение ничего не делаем
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
		return;

	// проверяем права на возможность сохранения
	if( ! current_user_can( 'edit_post', $post_id ) )
		return;

	// Очищаем значение полей
	$lat = sanitize_text_field( $_POST['latitude'] );
	$lon = sanitize_text_field( $_POST['longitude'] );

	// Обновляем данные в БД.
	update_post_meta( $post_id, 'latitude', $lat );
	update_post_meta( $post_id, 'longitude', $lon );
}

//Создаем шорткод
function show_yamap(){
	global $post;
	// значение полей координат
	$latitude 	= get_post_meta( $post->ID, 'latitude', 1 );
	$longitude 	= get_post_meta( $post->ID, 'longitude', 1 );

	if ( empty($latitude) || empty($longitude) ) {
		return;
	}

	$output = "<div id='map' style='width: 100%; height: 90%;min-height:500px'></div>
		<script src='https://api-maps.yandex.ru/2.1/?lang=ru_RU&amp;apikey=36304522-c992-4a7c-a8b3-0ad4b0d590e5' type='text/javascript'></script>
		<script type='text/javascript'>
			var myMap;

			// Дождёмся загрузки API и готовности DOM.
			ymaps.ready(init);

			function init () {
			    // Создание экземпляра карты и его привязка к контейнеру с
			    // заданным id ('map').
			    myMap = new ymaps.Map('map', {
			        // При инициализации карты обязательно нужно указать
			        // её центр и коэффициент масштабирования.
			        center: [" . $latitude . "," . $longitude . "], // Москва
			        zoom: 12
			    }, {
			        searchControlProvider: 'yandex#search'
			    });
			    var placemark = new ymaps.Placemark(myMap.getCenter(), {}, {
				    // Задаем стиль метки (метка в виде круга).
				    preset: 'islands#circleDotIcon',
				    // Задаем цвет метки (в формате RGB).
				    iconColor: '#ff0000'
				});
				myMap.geoObjects.add(placemark);
			}
		</script>";
	return $output;

}
add_shortcode('yandex_map', 'show_yamap');

