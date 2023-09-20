<?php
/**
 * Plugin Name: Olá Marketing Campaign
 * Plugin URI: https://ola.marketing
 * Description: Ferramentas de geração de campanhas publicitárias
 * Version: 1.0.0
 * Author: Olá Marketing
 * Author URI: https://ola.marketing
 * Text Domain: ola-marketing-campaign
 * Requires at least: 6.1
 * Requires PHP: 7.3
 *
 */

defined( 'ABSPATH' ) or die( 'Acesso direto proibido.' );

define('OMC_BASE_PATH', dirname(__FILE__));
define('OMC_BASE_ID', 'ola-marketing-c');

class Ola_Marketing_Campaign{
	private static $instance = null;
    public $id = null;
    public $campos = [];
    public $id_mb = '';

	public static function get_instance(){
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    function __construct() {
        $this->id = OMC_BASE_ID;
        $this->id_mb = 'dados-campaign';

        $this->campos = array(

            array(
                'id' => 'website_url',
                'name' => 'website URL',
                'description' => 'O URL completo do site (por exemplo, https://www.example.com)',
            ),
            array(
                'id' => 'utm_source',
                'name' => 'Campaign source',
                'description' => 'O referenciador (por exemplo, google, boletim informativo)',
            ), 
            array(
                'id' => 'utm_medium',
                'name' => 'Campaign medium',
                'description' => 'Meio de marketing (por exemplo, CPC, banner, e-mail)',
            ),
            array(
                'id' => 'utm_campaign',
                'name' => 'Campaign name',
                'description' => 'Produto, código promocional ou slogan (por exemplo, spring_sale) É necessário um nome ou ID da campanha',
            ),                                  
            array(
                'id' => 'utm_id',
                'name' => 'Campaign ID',
                'description' => 'O ID da campanha publicitária',
            ),                       
            array(
                'id' => 'utm_term',
                'name' => 'Campaign term',
                'description' => 'Identifique as palavras-chave pagas',
            ),    
            array(
                'id' => 'utm_content',
                'name' => 'Campaign content',
                'description' => 'Use para diferenciar anúncios',
            ),                                            
        );

        add_action('init', [$this,'create_cpt_campaign_func']);
        add_filter('enter_title_here', [$this,'placeholder_title_cpt_func']);
        add_action('add_meta_boxes', [$this,'register_meta_boxes_cpt_func']);

        add_action('edit_form_after_title', [$this,'edit_form_after_title_func']);

        add_action('save_post', [$this,'salvar_dados_da_campanha_func'], 100, 2);

        add_action('admin_footer', [$this,'codigo_campaign_footer_func'], 100);

        add_action('template_redirect', [$this,'campaign_template_redirect_func']);

        add_filter("manage_{$this->id}_posts_columns",[$this,'add_column_campaign_func']);
        add_action("manage_{$this->id}_posts_custom_column",[$this,'add_content_column_campaign_func'],10,2);

    }

    public function generate_id_unique($length = 12){
      $genpassword = "";
      $possible = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"; 
      $i = 0; 
      while ($i < $length) { 
        $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
        if (!strstr($genpassword, $char)) { 
          $genpassword .= $char;
          $i++;
        }
      }
      return $genpassword;
    } 

    public function create_cpt_campaign_func(){
        register_post_type(OMC_BASE_ID,[
            'labels' => [
                'name'                  => 'Campanhas',
                'singular_name'         => 'Campanha',
                'menu_name'             => 'Campanhas',
            ],
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => 'ola',
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'author' ),
            'menu_icon' => 'data:image/svg+xml;base64,' . base64_encode('<svg width="20" height="20" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path fill="rgba(167,170,173,1)" d="M1591 1448q56 89 21.5 152.5t-140.5 63.5h-1152q-106 0-140.5-63.5t21.5-152.5l503-793v-399h-64q-26 0-45-19t-19-45 19-45 45-19h512q26 0 45 19t19 45-19 45-45 19h-64v399zm-779-725l-272 429h712l-272-429-20-31v-436h-128v436z"/></svg>')
        ]);
    }

    public function placeholder_title_cpt_func($title){
        $screen = get_current_screen();
        if  (OMC_BASE_ID !== $screen->post_type ){ return $title; }

        $title = 'Campanha';

        return $title;

    }

    public function register_meta_boxes_cpt_func(){
        add_meta_box($this->id_mb, 
        __( 'Dados da campanha', 'default' ), 
        [$this,'mb_campaign_callback_func'], 
        OMC_BASE_ID,
        'after_title',
        'high');

        add_meta_box( 'dados-campaign-output', __( 'Resultado', 'default' ), [$this,'mb_campaign_result_callback_func'],  OMC_BASE_ID, 'side' );

    }

    public function mb_campaign_callback_func(){
        global $post;
        ob_start();
        ?>

        <table class="form-table striped">
            <?php if(is_array($this->campos) && count($this->campos) > 0){

                $encurtador_url = get_post_meta($post->ID, 'encurtador_url', true );

                foreach($this->campos as $i => $c){

                    $value = esc_html(get_post_meta($post->ID, $c['id'], true ));
                    ?>
                    <tr>
                        <th width="100"><label for="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></label></th>
                        <td>
                            <input id="<?php echo $c['id']; ?>" type="text" class="regular-text" name="<?php echo $c['id']; ?>" value="<?php echo $value; ?>"/>
                            <p class="description"><?php echo $c['description']; ?></p>
                        </td>
                    </tr>                    
                    <?php
                }
                ?>
                    <tr>
                        <th>
                            <label for="encurtador_url">Usar como encurtador de URL</label>
                        </th>
                        <td>
                            <input type="checkbox" id="encurtador_url" name="encurtador_url" value="1" <?php checked($encurtador_url, 1 ); ?> /> <label class="description" for="encurtador_url">Use esta postagem como um encurtador de URL.</label>
                            <p>(Ao acessar este endereço: <code><a href="<?php echo get_the_permalink($post->ID); ?>" target="_blank"><?php echo get_the_permalink($post->ID); ?></a></code>, ele será redirecionado para a url do site).</p>
                        </td>
                    </tr>
                <?php
            }
            ?>
        </table>

        <?php
        $output = ob_get_contents();
        ob_end_clean();
        echo $output;
    }

    public function edit_form_after_title_func(){
        global $post, $wp_meta_boxes;
        do_meta_boxes( get_current_screen(), 'after_title', $post );
        unset( $wp_meta_boxes['post']['after_title'] );
    }

    public function mb_campaign_result_callback_func(){
        global $post;

        $website_url = esc_url($this->generate_campaign_url($post->ID));

        ob_start();
        ?>

        <textarea id="campaign_result_url" style="width:100%;" rows="5" cols="30" readonly="true">
        <?php echo $website_url; ?>
        </textarea>
        <a class="button textarea-copy"><span class="dashicons dashicons-admin-page"></span> Copiar</a>

        <?php
        $output = ob_get_contents();
        ob_end_clean();
        echo $output;        
    }

    public function salvar_dados_da_campanha_func($post_id, $post){
        if(OMC_BASE_ID == get_post_type()){

            $campos_id = array_column($this->campos,'id');
            $campos = array_merge($campos_id,['encurtador_url']);

            if(is_array($campos) && count($campos) > 0){
                foreach($campos as $i => $name){

                    $value = isset($_REQUEST[$name]) ? esc_html($_REQUEST[$name]) : '';
                    update_post_meta($post_id, $name, $value);
                    
                }
            }

        }
    }

    public function generate_campaign_url($post_id=''){
        $website_url = esc_html(get_post_meta($post_id,'website_url', true ));
        if($website_url == ''): return false; endif;
        $campos_id = array_column($this->campos,'id');
        $array = [];
        if(is_array($campos_id) && count($campos_id) > 0){
            foreach($campos_id as $i => $name){
                if($name !== 'website_url'){
                    $val = esc_html(get_post_meta($post_id,$name, true));
                    if($val !== ''){
                        $array[$name] = $val;
                    }
                }
            }
        }
        $url = add_query_arg($array,$website_url);
        return $url;
    }

    public function codigo_campaign_footer_func(){
        global $post;
        if(OMC_BASE_ID == get_post_type()){
        ob_start();

        $website_url = get_post_meta($post->ID,'website_url',true);    

        ?>
        <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function (event) {
            (function($){
                $(function($, undefined){    
                    
                    const div = $('#<?php echo $this->id_mb; ?>');
                    const textarea = $('#campaign_result_url');

                    var website_url = $('input#website_url[type="text"]').val() || '<?php echo $website_url; ?>';

                    var inputs = div.find('input[type=text]').not('#website_url');

                    var str = website_url+"?";

                    if(inputs.length > 0){
                        inputs.each(function (i, item) {
                            if(item.value !== ''){
                                str += encodeURIComponent(item.name) + "=" + encodeURIComponent(item.value) + "&";
                            }
                        });
                    }
                    var url = str.replace(/&([^_]*)$/, '$1');
                    textarea.val(url);

                    $(document).on('input', inputs, function(elm){
                        var inputs = div.find('input[type=text]');
                        var fn = $(elm);
                        if(inputs.length > 0){
                            var valor = '';
                            if(inputs.length > 0){
                                inputs.each(function (i, item) {
                                    if(item.name !== "website_url"){
                                        if(item.value !== ''){
                                            valor += encodeURIComponent(item.name) + "=" + encodeURIComponent(item.value) + "&";
                                        }
                                    }else{
                                        website_url = item.value;
                                    }
                                });
                            }
                            var url = valor.replace(/&([^_]*)$/, '$1');
                            if(website_url !== ''){
                            textarea.val(website_url + "?" + url);
                            }else{
                            textarea.val('');    
                            }
                        }
                    });

                    $(document).on('click', '.textarea-copy', function(elm){
                        let textarea = document.getElementById("campaign_result_url");
                        textarea.select();
                        document.execCommand("copy");
                        $('form').submit();
                    });

                });
            })(jQuery);
        });
        </script>
        <?php
        $output = ob_get_contents();
        ob_end_clean();
        echo $output;
        } 
    }

    public function campaign_template_redirect_func(){
        global $post;

        if(OMC_BASE_ID == get_post_type()){

            $website_url = esc_url_raw($this->generate_campaign_url($post->ID));
            $encurtador_url = intval(get_post_meta($post->ID, 'encurtador_url', true ));

            if($encurtador_url > 0){
                wp_redirect($website_url, 301 );
                exit();
            }else{
                set_query_var('website_url', $website_url);
                load_template( OMC_BASE_PATH . '/template.phtml' );
                exit();
            }

        }
    }

    public function add_column_campaign_func($columns){
        $offset = array_search('author', array_keys($columns));
        $new_columns = array(
            'website_url' => __('Link', 'default'),
            'url_ola' => __('Url', 'default'),
        );
        return array_merge(array_slice($columns, 0, $offset), $new_columns, array_slice($columns, $offset, null));
    }

    public function add_content_column_campaign_func($column_key, $post_id){

        $website_url = esc_url($this->generate_campaign_url($post_id));
        if ($column_key == 'website_url') {
            echo sprintf('<a href="%s" target="_blank">%s</a>',$website_url,$website_url);
        }
        if ($column_key == 'url_ola') {
            echo sprintf('<input type="text" readonly="true" style="%s" value="%s" />', 'width:100%', get_the_permalink($post_id));
        }
    }

}

$GLOBALS[OMC_BASE_ID] = Ola_Marketing_Campaign::get_instance();