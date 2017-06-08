<?php

/**
 * MetaBoxesHandler
 * http://mbh.kmstudio.it/
 * 
 * È un utility per Wordpress per la gestione automatizzata di metabox da utilizzare con un custom post type, sia lato backend che frontend.
 * 
 * Attualmente supporta le seguenti tipologie di campi:
 * - input
 * - textarea
 * - media upload
 * - upload di immagini utilizzando le funzioni native di wordpress
 * - select e select optiongroup
 * - campi con html personalizzato
 * 
 * @versione                        0.18
 * @data prima versione             20 Maggio 2017
 * @data ultimo aggiornamento       7 Giugno 2017
 * 
 * @autore                          Giorgio Suadoni
 * 
 */

/**
=== COME USARE LA CLASSE ===

## Da istanziare prima di tutto:
$mbh = new MetaBoxesHandler;

## Impostare prima il nome del custom post type
$mbh->setPostType('catalogue');

## [Facoltativo] Per trasformare il Titolo del post in maiuscolo, minuscolo o con solo le iniziali maiuscole:
$mbh->transformTextPostTitleAs('uppercase');

## [Facoltativo] Rimuove il codice HTML dal Titolo del Post:
$mbh->removeHTMLInPostTitle();

## [Facoltativo] Aggiunge il supporto per l'immagine in evidenza (nativa di Wordpress)
$mbh->setFeatureSupport(array(
	'featured_image' => 'Immagine',
	'set_featured_image' => 'Imposta Immagine',
	'remove_featured_image' => 'Rimuovi Immagine',
	'use_featured_image' => 'Usa Immagine',
), '<p>Descrizione immagine in evidenza.</p>');

## Per aggiungere un metabox, occorre passargli al metodo "add()" un array contenente una lista di opzioni e relativi parametri.
   E' possibile richiamarlo più volte. Ad ogni richiamo verrà creato un metabox con tutti i campi del form.
$mbh->add('Generali', array(
	array(
		// Obbligatorio. Nome del postmeta per il salvataggio su DB. Utilizzarlo come array quando si configura l'opzione come campo html personalizzato
		'name' => string|array,
		
		// Obbligatorio. Nome dell'etichetta del campo
		'label' => string,
		
		// Obbligatorio (solo backend). Il tipo di campo da rendirizzare. Un valore tra (input|textarea|media-upload|wp-upload-image|select|custom-html)
		'type' => string,
		
		// Facoltativo (solo backend). Una serie di attributi HTML da aggiungere al campo ("chiave => valore")
		// funziona solo con input e textarea
		'type-attributes' => array,
		
		// Facoltativo (solo backend). Inserire una breve descrizione del campo da visualizzare nel form
		// non funziona con il tipo di campo custom-html
		'help-description' => string,
		
		// Facoltativo (default: none) (solo backend). Per la trasformazione del testo
		// un valore tra (none|capitalize|uppercase|lowercase). Funziona solo con input
		'text-transform' => string,
		
		// Facoltativo (default: false). True: salverà il postmeta in un record separato dagli altri
		'save-unique' => bool,
		
		// Facoltativo (default: false). True: rimuoverà tutti i tag HTML dal contenuto del campo
		'allow-html' => bool,
		
		// Obbligatorio se il tipo di campo è custom-html. Funzione anonima da eseguire.
		'custom-html' => closure,
	),

	// Alcuni esempi:
	array(
		'name' => 'original_title',
		'label' => 'Original Title',
		'type' => 'input',
		'type-attributes' => array(
			'class' => array('large-text', 'ftext'),
		),
		'help-description' => __('<strong>Facoltativo.</strong> Inserire il titolo originale del catalogo.', 'admin'),
		'text-transform' => 'capitalize',
	),
	array(
		'name' => 'producer',
		'label' => 'Produced By',
		'type' => 'textarea',
		'type-attributes' => array(
			'rows' => 5,
			'cols' => 80,
		),
		'help-description' => __('Inserire ogni voce separata da un ritorno a capo.', 'admin'),
		'required' => true, // Aggiunta nella versione 0.14
	),
	array(
		'name' => 'cast',
		'label' => 'Cast',
		'type' => 'textarea',
		'type-attributes' => array(
			'rows' => 10,
			'cols' => 80,
		),
		'help-description' => __('Inserire ogni voce separata da un ritorno a capo.', 'admin'),
		'save-unique' => true,
	),
	array(
		'name' => 'image_preview_home',
		'label' => 'Anteprima in Home',
		'type' => 'wp-upload-image',
		'image-support-size' => 'poster_orizontal', // Dalla funzione add_image_size('poster_orizontal', 1024, 577, true);
		'help-description' => '<i>Visibile in Homepage.</i><br>Dimensioni minime: 1024x577 pixel',
	),
	array(
		'name' => 'country',
		'label' => 'Country',
		'type' => 'select',
		'select-options' => array( // Come option group
			'Europe' => array(
				'Italy' => 'italy',
				'Spain' => 'spain',
			),
			'America' => array(
				'Anguilla' => 'anguilla',
				'Cile' => 'cile',
			),
		),
	),

	// Dalla v0.13 è possibile inserire più postmeta in una singola opzione.
	// Questi verrano stampati a cascata nella 2° colonna della tabella. Esempio:
	array(
		'label' => '', // Obbligatorio. String. Nome dell'etichetta del campo. E' richiesto soltanto questo parametro!
		'more-elements' => array(
			// A seguire i parametri sono esattamente come una singola opzione
			array(
				'name' => 'trailer_url',
				'label' => 'URL',
				'type' => 'input',
				'help-description' => 'Inserire l\'ID del video da Youtube o Vimeo.',
			),
			array(
				'name' => 'trailer_container',
				'label' => 'Container type',
				'type' => 'select',
				'select-options' => array(
					'Youtube' => 'youtube',
					'Vimeo' => 'vimeo',
				),
				'help-description' => 'Selezionare il contenitore da cui fa riferimento il video',
			),
		),
	),
	
	// Dalla v0.18 è possibile inserire un campo con codice html personalizzato
	array(
		'name' => array(
			'hd_notes',
			'hd',
		),
		'label' => 'HD',
		'type' => 'custom-html',
		'custom-html' => function($fields, $values) {
			return '
			<input type="checkbox" name="'. $fields['hd'] .'" value="1"'. ($values['hd'] == '1' ? ' checked="checked"' : '') .'>
			<input type="text" name="'. $fields['hd_notes'] .'" class="regular-text" placeholder="Notes" autocomplete="off" value="'. $values['hd_notes'] .'">
			';
		},
	),
));

$mbh->addMetaBox('Altre info', array(
	// Alcuni esempi:
	array(
		'name' => 'note',
		'label' => 'Note',
		'type' => 'texarea',
		'type-attributes' => array(
			'cols' => 30,
			'rows' => 30,
		),
		'help-description' => 'Campo note',
	),
));

## Solo lato frontend: ritorna un array con tutti i postmeta e i loro valori salvati precedentemente.
$allFields = $mbh->returnAllMeta();
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
	 * @dalla v0.1
	 *
	 * @accesso pubblico
	 */
	public function __construct($lang = false) {
		parent::__construct($lang);
		
		wp_enqueue_script('mbh-base', BASE_MBH_URI .'/js/mbh-base.js', array('jquery'), filemtime(BASE_MBH .'/js/mbh-base.js'), true);
		wp_localize_script('mbh-base', 'mbh_vars', array(
			'save_confirm_delete' => parent::_getTranslate('save_confirm_delete'),
			'save_confirm_mod' => parent::_getTranslate('save_confirm_mod'),
			'select_media_image' => parent::_getTranslate('select_media_image'),
			'use_this_image' => parent::_getTranslate('use_this_image'),
			'change_image' => parent::_getTranslate('change_image'),
			'set_image' => parent::_getTranslate('set_image'),
		));
		
		wp_register_script('wp-upload-image', BASE_MBH_URI .'/js/wp-upload-image.js', array('jquery'), filemtime(BASE_MBH .'/js/wp-upload-image.js'), true);
		wp_register_script('custom-attachment', BASE_MBH_URI .'/js/custom-attachment.js', array('jquery'), filemtime(BASE_MBH .'/js/custom-attachment.js'), true);
		
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
	 * Solo lato frontend: ritorna un array con tutti i postmeta e i loro valori salvati precedentemente.
	 *
	 * @aggiornamento v0.16
	 * @dalla v0.1
	 *
	 * @accesso   pubblico
	 * @ritorno   array
	 */
	public function returnAllMeta() {
		if (!is_admin())
			return parent::_getPostMetaAsArrays();
	}
}