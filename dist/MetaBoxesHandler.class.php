<?php

/**
 * MetaBoxesHandler
 * http://mbh.kmstudio.it/
 * 
 * È un utility per Wordpress per la gestione automatizzata di metabox da utilizzare con un custom post type, sia lato backend che frontend.
 * 
 * @versione                        0.21
 * @data ultimo aggiornamento       27 Giugno 2017
 * @data prima versione             20 Maggio 2017
 * 
 * @autore                          Giorgio Suadoni
 * @wiki                            https://github.com/GiorgioKM/MetaBoxesHandler/wiki/
 * 
 */

define('BASE_MBH', dirname(__FILE__));

/*--- Ottengo il percorso HTTP dove si trova la classe e la salvo in una costante */
$absolutePath = explode('/', BASE_MBH);
$wpThemePath = explode('/', get_stylesheet_directory_uri());

$foundIndex = array_search($wpThemePath[count($wpThemePath) - 1], $absolutePath);
$foundIndex++;

$maxIndex = count($absolutePath) - 1;
$uri = get_stylesheet_directory_uri();

for ($x = $foundIndex; $x <= $maxIndex; $x++) {
	$uri .= '/'. $absolutePath[$x];
}

define('BASE_MBH_URI', $uri);
/*-------------------------------------------------------------------------------------*/

require_once(BASE_MBH .'/includes/BaseMBH.php');

class MetaBoxesHandler extends BaseMBH {
	/**
	 * Il nome del custom post type.
	 *
	 * @dalla v0.1
	 *
	 * @accesso protetto
	 * @var     string
	 */
	protected $postType = '';
	
	/**
	 * Tipologia di trasformazione del testo nel Titolo del custom post type.
	 *
	 * @dalla v0.1
	 *
	 * @accesso protetto
	 * @var     string
	 */
	protected $typeTransformationForTextPostTitle = '';
	
	/**
	 * Permette o meno codice HTML nel Titolo del custom post type.
	 *
	 * @dalla v0.1
	 *
	 * @accesso protetto
	 * @var     bool
	 */
	protected $allowHTMLInPostTitle = true;
	
	/**
	 * Lista di tutti i campi personalizzati con i relativi parametri.
	 *
	 * @dalla v0.1
	 *
	 * @accesso protetto
	 * @var     array
	 */
	protected $fields = array();
	
	/**
	 * Tipi validi per la trasformazione del testo.
	 *
	 * @dalla v0.1
	 *
	 * @accesso protetto
	 * @var     array
	 */
	protected $validTextTransform = array(
		'none',
		'capitalize',
		'uppercase',
		'lowercase',
	);
	
	/**
	 * Se il supporto all'immagine in evidenza è stato aggiunto o no
	 *
	 * @dalla v0.15
	 *
	 * @accesso privato
	 * @var     bool
	 */
	private $isFeaturedAdded = false;
	
	/**
	 * Costruttore.
	 *
	 * @aggiornamento v0.19.12 Controllo che gli script o stili non vengono caricati più di una volta
	 * @dalla v0.1
	 *
	 * @accesso pubblico
	 */
	public function __construct($lang = false) {
		parent::__construct($lang);
		
		if (!wp_script_is('mbh-base')) {
			wp_enqueue_script('mbh-base', BASE_MBH_URI .'/js/mbh-base.js', array('jquery'), filemtime(BASE_MBH .'/js/mbh-base.js'), true);
			wp_localize_script('mbh-base', 'mbh_vars', array(
				'save_confirm_delete' => parent::_getTranslate('save_confirm_delete'),
				'save_confirm_mod' => parent::_getTranslate('save_confirm_mod'),
				'select_media_image' => parent::_getTranslate('select_media_image'),
				'use_this_image' => parent::_getTranslate('use_this_image'),
				'change_image' => parent::_getTranslate('change_image'),
				'set_image' => parent::_getTranslate('set_image'),
			));
		}
		
		if (!wp_script_is('wp-upload-image'))
			wp_register_script('wp-upload-image', BASE_MBH_URI .'/js/wp-upload-image.js', array('jquery'), filemtime(BASE_MBH .'/js/wp-upload-image.js'), true);
		
		if (!wp_script_is('custom-attachment'))
			wp_register_script('custom-attachment', BASE_MBH_URI .'/js/custom-attachment.js', array('jquery'), filemtime(BASE_MBH .'/js/custom-attachment.js'), true);
		
		if (!wp_script_is('mbh-style'))
			wp_enqueue_style('mbh-style', BASE_MBH_URI .'/css/mbh-style.css', null, filemtime(BASE_MBH .'/css/mbh-style.css'));
	}
	
	/**
	 * Imposta il nome del custom post type.
	 *
	 * @dalla v0.1
	 *
	 * @accesso   pubblico
	 * @parametro string $postType Obbligatorio. Il nome del custom post type.
	 * @ritorno   object Restituisce l'istanza in cui questo metodo viene richiamato.
	 */
	public function setPostType($postType) {
		$this->postType = $postType;
		
		return $this;
	}
	
	/**
	 * Imposta la trasformazione del testo nel Titolo del Post.
	 *
	 * @dalla v0.1
	 *
	 * @accesso   pubblico
	 * @parametro string $type Obbligatorio. Il tipo di trasformazione. Un valore tra (none|capitalize|uppercase|lowercase).
	 * @ritorno   object Restituisce l'istanza in cui questo metodo viene richiamato.
	 */
	public function transformTextPostTitleAs($type) {
		if (!in_array($type, $this->validTextTransform))
			$this->typeTransformationForTextPostTitle = $this->validTextTransform[0];
		else
			$this->typeTransformationForTextPostTitle = $type;
		
		return $this;
	}
	
	/**
	 * Rimuove il codice HTML nel Titolo del Post.
	 *
	 * @dalla v0.1
	 *
	 * @accesso pubblico
	 * @ritorno object Restituisce l'istanza in cui questo metodo viene richiamato.
	 */
	public function removeHTMLInPostTitle() {
		$this->allowHTMLInPostTitle = false;
		
		return $this;
	}
	
	/**
	 * Aggiunge il supporto per l'immagine in evidenza (nativa di Wordpress)
	 *
	 * @aggiornamento v0.18.1
	 * @dalla v0.15
	 *
	 * @accesso   pubblico
	 * @parametro array  $arrLabels   Facoltativo. Un array con le etichette da modificare per l'immagine in evidenza
	 * @parametro string $description Facoltativo. La descrizione da visualizzare nel box
	 */
	public function setFeatureSupport($arrLabels = array(), $description = false) {
		if (!$this->postType) {
			wp_die('
				Non è stato specificato il nome del "Post Type" nella classe <strong>'. get_class($this) .'</strong>!<br>
				<br>
				Richiamare prima il metodo <strong>$mbh->setPostType(\'&lt;nome del custom post type&gt;\')</strong>
			');
		}
		
		if (!has_post_format('post-thumbnails', $this->postType))
			add_theme_support('post-thumbnails', array($this->postType)); 
		
		$this->isFeaturedAdded = true;
		
		add_action('init', function() use($arrLabels) {
			global $wp_post_types;
			
			$labels = $wp_post_types[$this->postType]->labels;
			
			$labelsFeature = array(
				'featured_image',
				'set_featured_image',
				'remove_featured_image',
				'use_featured_image',
			);
			
			foreach ($labelsFeature as $nl) {
				if (isset($arrLabels[$nl]) && $arrLabels[$nl])
					$labels->$nl = $arrLabels[$nl];
			}
			
			add_post_type_support($this->postType, 'thumbnail');
		});
		
		add_filter('admin_post_thumbnail_html', function($content) use($description) {
			global $post;
			
			if ($post->post_type != $this->postType)
				return $content;
			
			if (!$this->isFeaturedAdded)
				return $content;
			
			preg_match('/(img)/', $content, $matches);
			
			if (!count($matches))
				$content = str_replace('class="thickbox"', 'class="thickbox button"', $content);
			else
				$content = str_replace('id="remove-post-thumbnail"', 'id="remove-post-thumbnail" class="button"', $content);
			
			return $content . $description;
		});
		
		return $this;
	}
	
	/**
	 * Aggiunge un metabox con i campi del form.
	 *
	 * @dalla v0.18
	 *
	 * @accesso   pubblico
	 * @parametro string $metaBoxName Obbligatorio. Nome del metabox.
	 * @parametro array  $fields      Obbligatorio. Lista con i valori di settaggio dei campi.
	 * @ritorno   object Restituisce l'istanza in cui questo metodo viene richiamato.
	 */
	public function add($metaBoxName, $fields) {
		if (!$this->postType) {
			wp_die('
				Non è stato specificato il nome del "Post Type" nella classe <strong>'. get_class($this) .'</strong>!<br>
				<br>
				Richiamare prima il metodo <strong>$mbh->setPostType(\'&lt;nome del custom post type&gt;\')</strong>
			');
		}
		
		if (is_array($fields) && count($fields))
			$this->fields = array_merge($this->fields, $fields);
		
		add_action('add_meta_boxes', function() use($metaBoxName, $fields) {
			parent::_addMetaBox($metaBoxName, $fields);
			parent::_addMetaBoxWPImageUpload();
		});
		
		parent::_WPaddActionSave();
		
		return $this;
	}
	
	/**
	 * Ritorna un array con tutti i postmeta e i loro valori salvati precedentemente.
	 *
	 * @aggiornamento v0.21 Ora è possibile eseguire questa richiesta anche lato backend
	 * @dalla v0.1
	 *
	 * @accesso   pubblico
	 * @ritorno   array
	 */
	public function returnAllMeta($includeAllPosts = false) {
		return parent::_getPostMetaAsArrays($includeAllPosts);
	}
	
	/**
	 * Ritorna il nome esatto del meta key salvato sul DB
	 *
	 * @dalla v0.21
	 *
	 * @accesso   pubblico
	 * @parametro string $namePostMeta Facoltativo. Il nome del campo del Post Meta impostato. Se non viene passato nessun valore, ritorneranno tutti i metakey.
	 * @ritorno   string|array
	 */
	public function getMetaKey($namePostMeta = false) {
		return parent::_getMetaKeyFromNamePostMeta($namePostMeta);
	}
}