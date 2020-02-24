<?php
defined( 'ABSPATH' ) or die();

class yaMapShortcode {

    function __construct() {

        add_action( 'init', array( $this, 'create_post_type' ) );
        add_action('add_meta_boxes', array( $this, 'yamap_metabox' ) );
        add_action( 'save_post', array( $this, 'save_coordinates') );
        add_shortcode( 'yandex_map', array( $this, 'show_yamap' ) );

    }

    public function create_post_type() {

        register_post_type( 
            'yamaps',
            array(
                'labels' => array(
                    'name'          => 'Yandex карты',
                    'singular_name' => 'Yandex карты',
                    'menu_name'     => 'Yandex карты',
                    'all_items'     => 'Все карты',
                    'add_new'       => 'Добавить карту '
                ),
                'public'             => true,
                'menu_icon'          => 'dashicons-location-alt'
            )
        );

    }

    // Добавляем блок полей координат
    public function yamap_metabox( $post_type ){

            // Устанавливаем типы постов к которым будет добавлен блок
            $cptype = 'yamaps';

            if ( $cptype == $post_type ) {
                add_meta_box(
                    'yamap_box',
                    'Координаты',
                    array( $this, 'render_metabox' ),
                    $cptype,
                    'advanced',
                    'high'
                );
            }
    }

    // блок полей координат
    public function render_metabox( $post ) {

        // nonce для верификации
        wp_nonce_field( plugin_basename(__FILE__), 'yamap_noncename' );

        // значение полей координат
        $latitude   = get_post_meta( $post->ID, 'latitude', 1 );
        $longitude  = get_post_meta( $post->ID, 'longitude', 1 );

        // Инпуты для введения полей координат
        echo '<p><label for="latitude">' . 'Широта' . '</label> ';
        echo '<input type="text" id="latitude" name="latitude" value="'. $latitude .'" size="25" /></p>';
        echo '<p><label for="longitude">' . 'Долгота' . '</label> ';
        echo '<input type="text" id="longitude" name="longitude" value="'. $longitude .'" size="25" /></p>';
    }

    // Сохраням значения координат при сохранении поста
    public function save_coordinates( $post_id ) {

        // Убедимся что поля установлены.
        if ( ! isset( $_POST['longitude'] ) || ! isset( $_POST['latitude'] ) )
            return;

        // проверяем nonce нашей страницы, потому что save_post может быть вызван из другого места.
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

    public function show_yamap( $atts, $content ) {
        global $post;
        // значение полей координат
        $latitude   = get_post_meta( $post->ID, 'latitude', 1 );
        $longitude  = get_post_meta( $post->ID, 'longitude', 1 );

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

}

$my_class = new yaMapShortcode();

?>